<?php

namespace App\Http\Controllers;

use App\Http\Services\TranslatorService;
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
            'source' => 'required|string|size:2',
            'target' => 'required|string|size:2',
        ]);

        try {
            $results = $this->translatorService->compareTranslations(
                $request->input('text'),
                $request->input('source'),
                $request->input('target')
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
