<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryAssignedController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentTransactionController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
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

        Route::get('/auth/admin/riders', [RiderController::class, 'index']);
        Route::post('/auth/admin/rider/new', [RiderController::class, 'store']);
        Route::get('/auth/admin/rider/{user}', [RiderController::class, 'edit']);
        Route::post('/auth/admin/rider/{user}', [RiderController::class, 'update']);
        Route::delete('/auth/admin/riders/{user}', [RiderController::class, 'destroy']);

        Route::get('/auth/admin/tags', [TagController::class, 'index']);
        Route::post('/auth/admin/tags/new', [TagController::class, 'store']);
        Route::get('/auth/admin/tags/{tag}', [TagController::class, 'edit']);
        Route::put('/auth/admin/tags/{tag}', [TagController::class, 'update']);
        Route::delete('/auth/admin/tags/{tag}', [TagController::class, 'destroy']);

        Route::put('/auth/admin/orders/{order}', [OrderController::class, 'updatePaymentStatus']);

        Route::get('/auth/admin/payments', [PaymentMethodController::class, 'index']);
        Route::post('/auth/admin/payments/new', [PaymentMethodController::class, 'store']);
        Route::get('/auth/admin/payments/{paymentMethod}', [PaymentMethodController::class, 'edit']);
        Route::put('/auth/admin/payments/{paymentMethod}', [PaymentMethodController::class, 'update']);
        Route::delete('/auth/admin/payments/{paymentMethod}', [PaymentMethodController::class, 'destroy']);

        Route::get('/auth/admin/transactions', [PaymentTransactionController::class, 'index']);
        Route::delete('/auth/admin/transactions/{paymentTransaction}', [PaymentTransactionController::class, 'destroy']);

        Route::get('/auth/admin/orders/weekly-stats', [OrderController::class, 'getWeeklyStats']);
        Route::patch('/auth/admin/orders/update-settle', [OrderController::class, 'settleRiderOrders']);
        Route::patch('/auth/admin/orders/update-shop-settle', [OrderController::class, 'settleRestaurantOrders']);

        Route::get('/auth/admin/delivery-assigned-history', [DeliveryAssignedController::class, 'index']);
        Route::delete('/auth/admin/delivery-assigned-history/{orderRiderOffer}', [DeliveryAssignedController::class, 'destroy']);

        Route::get('/auth/admin/customers', [CustomerController::class, 'index']);

    });

    Route::middleware('role:2')->group(function() {
        Route::get('/auth/restaurant/categories', [CategoryController::class, 'all']);
        Route::post('/auth/restaurant/menus/new', [MenuController::class, 'store']);
        Route::get('/auth/restaurant/{id}/menus', [MenuController::class, 'findByRestaurantId']);
        Route::get('/auth/restaurant/menus/{menu}', [MenuController::class, 'edit']);
        Route::put('/auth/restaurant/menus/{menu}', [MenuController::class, 'update']);
        Route::delete('/auth/restaurant/menus/{menu}', [MenuController::class, 'destroy']);

        Route::get('/auth/restaurant/tags', [TagController::class, 'all']);

        Route::get('/auth/restaurant/{id}/orders', [OrderController::class, 'getIncomingOrders']);
        Route::put('/auth/restaurant/orders/{order}', [OrderController::class, 'updateOrderStatus']);
        Route::post('/auth/restaurant/orders/merchant/new', [OrderController::class, 'storeMerchantOrder']);

        Route::get('/auth/restaurant/customers', [CustomerController::class, 'shopCustomers']);

        Route::put('/auth/restaurant/{user}/update-status', [RestaurantController::class, 'updateShopStatus']);
    });

    Route::middleware('role:3')->group(function() {
        Route::post('/auth/orders/new', [OrderController::class, 'store']);
        Route::post('/auth/orders/express/new', [OrderController::class, 'storeExpressOrder']);
        Route::post('/auth/order-items/new', [OrderItemController::class, 'store']);

        Route::post('/auth/payment-transaction/new', [PaymentTransactionController::class, 'store']);

        Route::get('auth/customer/current-orders', [CustomerController::class, 'currentOrders']);
        Route::get('auth/customer/orders/{order}', [CustomerController::class, 'currentOrderDetails']);

        Route::get('/auth/user-level', [CustomerController::class, 'getUserLevel']);

        // Route::put('/auth/user/update-profile', [CustomerController::class, 'updateProfile']);
    });

    Route::middleware('role:4')->group(function() {
        Route::put('auth/rider/toggle-status', [RiderController::class, 'toggleStatus']);
        Route::put('auth/rider/update-location', [RiderController::class, 'updateLocation']);
        Route::post("auth/rider/accept-order/{orderId}", [RiderController::class, 'acceptOrder']);
        Route::get('auth/rider/current-active-orders', [RiderController::class, 'currentActiveOrders']);
        Route::put('auth/rider/update-delivery-status/{order}', [RiderController::class, 'updateDeliveryStatus']);
        
        Route::get('auth/rider/nearest-riders', [RiderController::class, 'nearestRiders']);
        Route::post('auth/rider/order-handover/{order}', [OrderController::class, 'orderHandOver']);
        Route::post('auth/rider/order-release/{order}', [OrderController::class, 'releaseOrder']);
    });

    Route::middleware('role:4,1')->group(function() {
        Route::get('auth/rider/order-history', [RiderController::class, 'completedOrders']);
    });

    Route::middleware('role:2,1')->group(function() {
        Route::get('/auth/restaurant/order-history', [RestaurantController::class, 'completedOrders']);
    });

    Route::put('/auth/user/update-profile', [CustomerController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::patch('/auth/user/save-fcm-token', [UserController::class, 'saveFcmToken']);
});

Route::get('/tags', [TagController::class, 'all']);

Route::get('/restaurants', [RestaurantController::class, 'findbyCustomer']);
Route::get('/restaurants/{id}/menus', [MenuController::class, 'findRestaurantMenusByCustomer']);
Route::get('/restaurants/{user}', [RestaurantController::class, 'findById']);

Route::get('/payments', [PaymentMethodController::class, 'all']);

// Route::post('/orders/new', [OrderController::class, 'store']);
// Route::post('/orders/express/new', [OrderController::class, 'storeExpressOrder']);
// Route::post('/order-items/new', [OrderItemController::class, 'store']);

// Route::post('/payment-transaction/new', [PaymentTransactionController::class, 'store']);

Route::put('/menus/{menu}/update-available-count', [MenuController::class, 'updateAvailableCount']);

// Route::get('auth/customer/current-orders', [CustomerController::class, 'currentOrders']);
// Route::get('auth/customer/orders/{order}', [CustomerController::class, 'currentOrderDetails']);

Route::post('/auth/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/auth/customer/login', [CustomerAuthController::class, 'login']);
Route::post('/auth/customer/logout', [CustomerAuthController::class, 'logout']);

    // Google OAuth Routes
Route::get('/auth/google/redirect', [CustomerAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [CustomerAuthController::class, 'handleGoogleCallback']);



Broadcast::routes(['middleware' => ['auth:sanctum']]);
