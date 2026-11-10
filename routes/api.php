<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Middleware\ApiAuthenticated;
use Illuminate\Support\Facades\Route;

/*
| Mobile app API (Flutter). Served under /api/v1. Stateless: Bearer tokens, no session or CSRF.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');

    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/home', [CatalogController::class, 'home'])->name('home');
        Route::get('/shops', [CatalogController::class, 'shops'])->name('shops.index');
        Route::get('/shops/{shop:id}', [CatalogController::class, 'shop'])->name('shops.show');
        Route::get('/shops/{shop:id}/products', [CatalogController::class, 'products'])->name('shops.products');
        Route::get('/products/{product:id}', [CatalogController::class, 'product'])->name('products.show');
    });

    Route::middleware([ApiAuthenticated::class, 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/devices', [OrderController::class, 'registerDevice'])->name('devices.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:id}', [OrderController::class, 'show'])->name('orders.show');
    });

    // Placing an order reuses the website's checkout, which keeps a little state in a session.
    Route::middleware([ApiAuthenticated::class, \Illuminate\Session\Middleware\StartSession::class, 'throttle:20,1'])->group(function () {
        Route::post('/shops/{shop:id}/restaurant-orders', [OrderController::class, 'storeRestaurant'])->name('orders.restaurant');
    });
});
