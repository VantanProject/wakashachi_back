<?php

namespace App\Http\Controllers;

use App\Services\TranslatorService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected $translatorService;

    public function __construct(TranslatorService $translatorService)
    {
        $this->translatorService = $translatorService;
    }

    public function compareTranslations(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'sourceId' => 'required|string|in:1,2,3,4',
            'targetId' => 'required|string|in:1,2,3,4',
        ]);

        try {
            $results = $this->translatorService->compareTranslations(
                $request->input('text'),
                $request->input('sourceId'),
                $request->input('targetId')
            );

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
