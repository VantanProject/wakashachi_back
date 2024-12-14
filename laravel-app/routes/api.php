<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchController;
use App\Http\Controllers\AuthController;
use App\Models\Allergy;

Route::post('/login', [AuthController::class, 'index']);

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/token', function (Request $request) {
        return response()->json([
            "success" => true,
            "token" => $request->user()->createToken("token")->plainTextToken,
        ]);
    });

    Route::apiResource('merch', MerchController::class);

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