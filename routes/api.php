<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){

    // for only admin
    Route::middleware('role:1')->group(function () {
        Route::get('/auth/admin', [AuthController::class, 'getAdminUser']);
        Route::post('/auth/admin/restaurant/new', [RestaurantController::class, 'store']);
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
