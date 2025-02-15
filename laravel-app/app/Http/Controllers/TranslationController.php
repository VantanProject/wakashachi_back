<?php

namespace App\Http\Controllers;

use App\Services\Translation\TranslatorService;
use App\Http\Requests\CompareTranslationRequest;

class TranslationController extends Controller
{
    protected $translatorService;

    public function __construct(TranslatorService $translatorService)
    {
        $this->translatorService = $translatorService;
    }


    public function compareTranslation(CompareTranslationRequest $request)
    {
        $validated = $request->validated();

        try {
            $results = $this->translatorService->translate(
                $validated['text'],
                $validated['sourceId'],
                $validated['targetId']
            );

            return response()->json([
                'success' => true,

                'results' => $results['results']  // resultsの中身を直接返す
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
