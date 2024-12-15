<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Merch;
use App\Models\MerchItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\MerchStoreRequest;

class MerchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $queryMerch = Merch::where('company_id', $companyId)->with(['merchItems', 'allergies']);

        $params = $request["search"];

        if ($params) {
            $queryMerch->where(function ($query) use ($params) {
                $query->whereHas('merchItems', function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['name'] . '%');
                });
                $query->orWhereHas('allergies', function ($allergyQuery) use ($params) {
                    $allergyQuery->whereIn('allergy_id', $params['allergyIds']);
                });
            });
        }

        $currentPage = $params['currentPage'];
        /**
         * paginateはPaginatorを返すが、内部アイテムはコレクションとして扱う必要があるため、
         * 型情報を補足することで、pluckやmapが適切に補完されるよう設定。
         *
         * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection $merches
         */
        $merches = $queryMerch->paginate(14, ['*'], 'page', $currentPage);
        $merchIds = $merches->pluck('id')->toArray();

        return response()->json(
            [
                'success' => true,
                'merches' => $merches->map(function ($merch) {
                    return [
                        'id' => $merch->id,
                        'name' => $merch->merchItems->first()->name,
                        'allergies' => $merch->allergies->pluck('id')->toArray(),
                    ];
                }),
                'ids' => $merchIds,
                'lastPage' => $merches->lastPage(),
            ]
        );
    }

    public function store(MerchStoreRequest $request)
    {
        $company_id = Auth::user()->company_id;
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $company_id) {

                $image = $validated['merch']['img_data'];
                $path = Storage::disk('s3')->putFile('wakashachi-app/merches', $image);
                $imageUrl = config('filesystems.disks.s3.url') . '/' . $path;

                //Merchテーブルへの追加です
                $merch = Merch::create([
                    'img_url' => $imageUrl,
                    'company_id' => $company_id,
                    'price' => $validated['merch']['price'],
                ]);

                //MerchItemテーブルへの追加です
                foreach ($validated['merch']['items'] as $item) {
                    $merch->merchItems()->create([
                        'name' => $item['name'],
                        'detail' => $item['detail'],
                        'language_id' => $item['language_id'],
                    ]);
                }

                $merch->allergies()->attach($validated['merch']['allergyIds']);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '商品の追加に失敗しました',
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '商品の追加に成功しました',
        ]);
    }
}
