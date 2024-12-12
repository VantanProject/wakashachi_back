<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Model\Allergy;
use App\Http\Controllers\MerchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

Route::apiResource('merch', MerchController::class);


