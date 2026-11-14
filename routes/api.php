<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\MerchantCatalogController;
use App\Http\Controllers\Api\V1\StoryController;
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
// Each limit names its own counter (the third value). Without a name, Laravel counts every
// limited request by the same person in one bucket, so a strict limit (e.g. 6 a minute) would
// trip after a few unrelated requests.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1,auth.login')->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1,auth.register')->name('auth.register');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1,auth.forgot')->name('auth.forgot');

    Route::middleware('throttle:120,1,api')->group(function () {
        Route::get('/home', [CatalogController::class, 'home'])->name('home');
        Route::get('/shops', [CatalogController::class, 'shops'])->name('shops.index');
        Route::get('/shops/{shop:id}', [CatalogController::class, 'shop'])->name('shops.show');
        Route::get('/shops/{shop:id}/products', [CatalogController::class, 'products'])->name('shops.products');
        Route::get('/products/{product:id}', [CatalogController::class, 'product'])->name('products.show');
        Route::get('/search', [CatalogController::class, 'search'])->name('search');
        Route::get('/stories', [StoryController::class, 'feed'])->name('stories.feed');
    });

    Route::middleware([ApiAuthenticated::class, 'throttle:120,1,api'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/delete-account', [AuthController::class, 'deleteAccount'])->middleware('throttle:5,1,auth.delete')->name('auth.delete');
        Route::post('/devices', [OrderController::class, 'registerDevice'])->name('devices.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:id}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/shops/{shop:id}/orders', [OrderController::class, 'storeShop'])->middleware('throttle:20,1,orders.shop')->name('orders.shop');
        Route::get('/orders/{order:id}/wheel', [OrderController::class, 'wheel'])->name('orders.wheel');
        Route::post('/orders/{order:id}/wheel/spin', [OrderController::class, 'spin'])->middleware('throttle:10,1,orders.wheel.spin')->name('orders.wheel.spin');
        Route::get('/me/points', [PointsController::class, 'index'])->name('me.points');
        Route::get('/me/likes', [CatalogController::class, 'liked'])->name('me.likes');
        Route::patch('/auth/me', [AccountController::class, 'update'])->name('account.update');
        Route::post('/auth/avatar', [AccountController::class, 'avatar'])->middleware('throttle:10,1,account.avatar')->name('account.avatar');
        Route::delete('/auth/avatar', [AccountController::class, 'destroyAvatar'])->name('account.avatar.destroy');
        Route::post('/auth/password', [AccountController::class, 'password'])->middleware('throttle:6,1,account.password')->name('account.password');
        Route::post('/stories/{story}/view', [StoryController::class, 'view'])->name('stories.view');
        Route::get('/shops/{shop:id}/signup-reward', [SignupRewardController::class, 'show'])->name('signup-reward.show');
        Route::post('/shops/{shop:id}/signup-reward/register', [SignupRewardController::class, 'register'])->middleware('throttle:10,1,signup-reward.register')->name('signup-reward.register');
        Route::post('/shops/{shop:id}/signup-reward/spin', [SignupRewardController::class, 'spin'])->middleware('throttle:10,1,signup-reward.spin')->name('signup-reward.spin');
        Route::post('/products/{product:id}/like', [\App\Http\Controllers\ProductLikeController::class, 'toggle'])->middleware('throttle:60,1,products.like')->name('products.like');

        // Shop owners: their shops, live orders and status changes.
        Route::get('/merchant/shops', [MerchantController::class, 'shops'])->name('merchant.shops');
        Route::get('/merchant/shops/{shop:id}/orders', [MerchantController::class, 'orders'])->name('merchant.orders');
        Route::patch('/merchant/shops/{shop:id}/availability', [MerchantController::class, 'availability'])->name('merchant.availability');
        Route::get('/merchant/orders/{order:id}', [MerchantController::class, 'show'])->name('merchant.orders.show');

        // The shop's stories, products and sections.
        Route::get('/merchant/shops/{shop:id}/stories', [StoryController::class, 'mine'])->name('merchant.stories');
        Route::post('/merchant/shops/{shop:id}/stories', [StoryController::class, 'store'])->middleware('throttle:20,1,merchant.stories.store')->name('merchant.stories.store');
        Route::delete('/merchant/stories/{story}', [StoryController::class, 'destroy'])->name('merchant.stories.destroy');
        Route::get('/merchant/shops/{shop:id}/products', [MerchantCatalogController::class, 'products'])->name('merchant.products');
        Route::post('/merchant/shops/{shop:id}/products', [MerchantCatalogController::class, 'store'])->middleware('throttle:60,1,merchant.products.store')->name('merchant.products.store');
        Route::get('/merchant/products/{product:id}', [MerchantCatalogController::class, 'show'])->name('merchant.products.show');
        Route::post('/merchant/products/{product:id}', [MerchantCatalogController::class, 'update'])->middleware('throttle:120,1,merchant.products.update')->name('merchant.products.update');
        Route::delete('/merchant/products/{product:id}', [MerchantCatalogController::class, 'destroy'])->name('merchant.products.destroy');
        Route::post('/merchant/shops/{shop:id}/categories', [MerchantCatalogController::class, 'storeCategory'])->name('merchant.categories.store');
        Route::patch('/merchant/categories/{category}', [MerchantCatalogController::class, 'updateCategory'])->name('merchant.categories.update');
        Route::delete('/merchant/categories/{category}', [MerchantCatalogController::class, 'destroyCategory'])->name('merchant.categories.destroy');

        // Delivery drivers.
        Route::get('/driver', [DriverController::class, 'index'])->name('driver.index');
        Route::patch('/driver/offers/{offer}/accept', [DriverController::class, 'accept'])->name('driver.offers.accept');
        Route::patch('/driver/offers/{offer}/reject', [DriverController::class, 'reject'])->name('driver.offers.reject');
        Route::patch('/driver/orders/{order:id}/status', [DriverController::class, 'status'])->name('driver.orders.status');
        Route::patch('/driver/orders/{order:id}/delivery-time', [DriverController::class, 'deliveryTime'])->name('driver.orders.time');
    });

    // Placing an order reuses the website's checkout, which keeps a little state in a session.
    Route::middleware([ApiAuthenticated::class, \Illuminate\Session\Middleware\StartSession::class, 'throttle:20,1,api-orders'])->group(function () {
        Route::post('/shops/{shop:id}/restaurant-orders', [OrderController::class, 'storeRestaurant'])->name('orders.restaurant');
        // Runs the website dashboards' status code, which renders a card and keeps a little session state.
        Route::patch('/merchant/orders/{order:id}/status', [MerchantController::class, 'status'])->name('merchant.orders.status');
    });
});
