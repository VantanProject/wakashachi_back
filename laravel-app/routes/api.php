<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\AuthController;
use App\Models\Allergy;
use App\Http\Controllers\TranslationController;

Route::post('/login', [AuthController::class, 'index']);

Route::post('/compare-translations', [TranslationController::class, 'compareTranslations']);

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/token', function (Request $request) {
        return response()->json([
            "success" => true,
            "token" => $request->user()->createToken("token")->plainTextToken,
        ]);
    });

    Route::apiResource('merch', MerchController::class)->except(['destrory']);
    Route::delete('merch', [MerchController::class, 'destrory']);

    Route::apiResource('menu', MenuController::class)->except(['destrory']);
    Route::delete('menu', [MenuController::class,'destrory']);

    Route::get("/allergy/select", function() {
        $allergies = Allergy::get()->map(function ($query) {
            return [
                "value" => $query->id,
                "label" => $query->name,
            ];
        });
        return response()->json([
            "success" => true,
            "allergies" => $allergies,
        ]);
    });



});
