<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Merch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\MerchStoreRequest;
use Carbon\Carbon;

class MerchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $queryMerch = Merch::where('company_id', $companyId)->with(['merchTranslations', 'allergies']);

        $params = $request["search"];

        $queryMerch->where(function ($query) use ($params) {
            if ($params["name"]) {
                $query->whereHas('merchTranslations', function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['name'] . '%');
                });
            }

            if (!empty($params["allergyIds"])) {
                $query->orWhereHas('allergies', function ($allergyQuery) use ($params) {
                    $allergyQuery->whereIn('allergy_id', $params['allergyIds']);
                });
            }
        });

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
                        'name' => $merch->merchTranslations
                            ->where('language_id', 1)
                            ->first()
                            ->name,
                        'allergyNames' => $merch->allergies->pluck('name')->toArray(),
                        'price' => $merch->price,
                        'updatedAt' => Carbon::parse($merch->updated_at)->format('Y年m月d日'),
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

                $image = $validated['merch']['imgData'];
                $path = Storage::disk('s3')->putFile('wakashachi-app/merches', $image);
                $imageUrl = config('filesystems.disks.s3.url') . '/' . $path;

                //Merchテーブルへの追加です
                $merch = Merch::create([
                    'img_url' => $imageUrl,
                    'company_id' => $company_id,
                    'price' => $validated['merch']['price'],
                ]);

                //MerchTranslationテーブルへの追加です
                foreach ($validated['merch']['translations'] as $translation) {
                    $merch->merchTranslations()->create([
                        'name' => $translation['name'],
                        'language_id' => $translation['languageId'],
                    ]);
                }

                $merch->allergies()->attach($validated['merch']['allergyIds']);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '商品の追加に失敗しました',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '商品の追加に成功しました',
        ]);
    }

    public function update(MerchStoreRequest $request, $id)
    {
        $company_id = Auth::user()->company_id;
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $company_id, $id) {
                $merch = Merch::find($id);
                $image = $validated['merch']['imgData'];
                $path = Storage::disk('s3')->putFile('wakashachi-app/merches', $image);
                $imageUrl = config('filesystems.disks.s3.url') . '/' . $path;

                $merch->update([
                    'img_url' => $imageUrl,
                    'company_id' => $company_id,
                    'price' => $validated['merch']['price'],
                ]);

                foreach ($validated['merch']['translations'] as $translation) {
                    $merch->merchTranslations()->update([
                        'name' => $translation['name'],
                        'language_id' => $translation['languageId'],
                    ]);
                }

                $merch->allergies()->sync($validated['merch']['allergyIds']);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '商品の更新に失敗しました',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '商品の更新に成功しました',
        ]);
    }

    public function destrory(Request $request)
    {
        $merchIds = $request['ids'];
        Merch::whereIn("id", $merchIds)->delete();

        return response()->json([
            'success' => true,
            'message' => '正常に削除されました！',
        ]);
    }
}
