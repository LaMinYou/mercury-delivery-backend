<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UserController;
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
        Route::get('/admin/users/role-count', [UserController::class, 'getRoleCounts']);
        Route::post('/auth/admin/restaurant/new', [RestaurantController::class, 'store']);
        Route::get('/auth/admin/restaurants', [RestaurantController::class, 'index']);
        Route::get('/auth/admin/restaurant/{user}', [RestaurantController::class, 'edit']);
        Route::post('/auth/admin/restaurants/{user}', [RestaurantController::class, 'update']);
        Route::post('/auth/admin/restaurants/updatestatus/{user}', [RestaurantController::class, 'handleStatus']);
        Route::delete('/auth/admin/restaurants/{user}', [RestaurantController::class, 'destroy']);
        Route::get('/auth/admin/menu-categories', [CategoryController::class, 'index']);
        Route::post('/auth/admin/menu-category/new', [CategoryController::class, 'store']);
        Route::get('/auth/admin/menu-categories/{category}', [CategoryController::class, 'edit']);
        Route::post('/auth/admin/menu-categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/auth/admin/menu-categories/{category}', [CategoryController::class, 'destroy']);

    });

    Route::middleware('role:2')->group(function() {
        Route::get('/auth/restaurant/categories', [CategoryController::class, 'all']);
        Route::post('/auth/restaurant/menus/new', [MenuController::class, 'store']);
        Route::get('/auth/restaurant/{id}/menus', [MenuController::class, 'findByRestaurantId']);
        Route::get('/auth/restaurant/menus/{menu}', [MenuController::class, 'edit']);
        Route::put('/auth/restaurant/menus/{menu}', [MenuController::class, 'update']);
        Route::delete('/auth/restaurant/menus/{menu}', [MenuController::class, 'destroy']);
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
