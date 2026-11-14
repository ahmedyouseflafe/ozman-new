<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PointsController;
use App\Http\Controllers\Api\V1\SignupRewardController;
use App\Http\Middleware\ApiAuthenticated;
use Illuminate\Support\Facades\Route;

/*
| Mobile app API (Flutter). Served under /api/v1. Stateless: Bearer tokens, no session or CSRF.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('auth.forgot');

    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/home', [CatalogController::class, 'home'])->name('home');
        Route::get('/shops', [CatalogController::class, 'shops'])->name('shops.index');
        Route::get('/shops/{shop:id}', [CatalogController::class, 'shop'])->name('shops.show');
        Route::get('/shops/{shop:id}/products', [CatalogController::class, 'products'])->name('shops.products');
        Route::get('/products/{product:id}', [CatalogController::class, 'product'])->name('products.show');
        Route::get('/search', [CatalogController::class, 'search'])->name('search');
    });

    Route::middleware([ApiAuthenticated::class, 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/delete-account', [AuthController::class, 'deleteAccount'])->middleware('throttle:5,1')->name('auth.delete');
        Route::post('/devices', [OrderController::class, 'registerDevice'])->name('devices.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:id}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/shops/{shop:id}/orders', [OrderController::class, 'storeShop'])->middleware('throttle:20,1')->name('orders.shop');
        Route::get('/orders/{order:id}/wheel', [OrderController::class, 'wheel'])->name('orders.wheel');
        Route::post('/orders/{order:id}/wheel/spin', [OrderController::class, 'spin'])->middleware('throttle:10,1')->name('orders.wheel.spin');
        Route::get('/me/points', [PointsController::class, 'index'])->name('me.points');
        Route::get('/me/likes', [CatalogController::class, 'liked'])->name('me.likes');
        Route::get('/shops/{shop:id}/signup-reward', [SignupRewardController::class, 'show'])->name('signup-reward.show');
        Route::post('/shops/{shop:id}/signup-reward/register', [SignupRewardController::class, 'register'])->middleware('throttle:10,1')->name('signup-reward.register');
        Route::post('/shops/{shop:id}/signup-reward/spin', [SignupRewardController::class, 'spin'])->middleware('throttle:10,1')->name('signup-reward.spin');
        Route::post('/products/{product:id}/like', [\App\Http\Controllers\ProductLikeController::class, 'toggle'])->middleware('throttle:60,1')->name('products.like');

        // Shop owners: their shops, live orders and status changes.
        Route::get('/merchant/shops', [MerchantController::class, 'shops'])->name('merchant.shops');
        Route::get('/merchant/shops/{shop:id}/orders', [MerchantController::class, 'orders'])->name('merchant.orders');
        Route::patch('/merchant/shops/{shop:id}/availability', [MerchantController::class, 'availability'])->name('merchant.availability');
        Route::get('/merchant/orders/{order:id}', [MerchantController::class, 'show'])->name('merchant.orders.show');
    });

    // Placing an order reuses the website's checkout, which keeps a little state in a session.
    Route::middleware([ApiAuthenticated::class, \Illuminate\Session\Middleware\StartSession::class, 'throttle:20,1'])->group(function () {
        Route::post('/shops/{shop:id}/restaurant-orders', [OrderController::class, 'storeRestaurant'])->name('orders.restaurant');
        // Runs the website dashboards' status code, which renders a card and keeps a little session state.
        Route::patch('/merchant/orders/{order:id}/status', [MerchantController::class, 'status'])->name('merchant.orders.status');
    });
});
