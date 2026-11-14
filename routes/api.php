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
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\PromoterController;
use App\Http\Controllers\Api\V1\RealEstateController;
use App\Http\Controllers\Api\V1\RestaurantManageController;
use App\Http\Controllers\Api\V1\SalonController;
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

        // Free market and creators: anyone can browse; a login is only needed to act.
        Route::get('/market/meta', [MarketController::class, 'meta'])->name('market.meta');
        Route::get('/market', [MarketController::class, 'index'])->name('market.index');
        Route::get('/market/{listing}', [MarketController::class, 'show'])->name('market.show');
        Route::get('/promoters', [PromoterController::class, 'index'])->name('promoters.index');
        Route::get('/promoters/{slug}', [PromoterController::class, 'show'])->name('promoters.show');
        // Electronics compare, krembo outlets, restaurant table QR, service companies, salon booking form.
        Route::get('/shops/{shop:id}/compare', [CatalogController::class, 'compare'])->name('electronics.compare');
        Route::get('/shops/{shop:id}/network', [CatalogController::class, 'network'])->name('krembo.network');
        Route::get('/restaurants/{slug}/tables/{code}', [RestaurantManageController::class, 'table'])->name('restaurant.table');
        Route::get('/real-estate/companies/{shop:id}', [RealEstateController::class, 'company'])->name('real-estate.company');
        Route::get('/real-estate/services/{service}', [RealEstateController::class, 'service'])->name('real-estate.service');
        Route::get('/real-estate/properties', [RealEstateController::class, 'properties'])->name('real-estate.properties');
        Route::get('/real-estate/properties/{property}', [RealEstateController::class, 'property'])->name('real-estate.property');
        Route::get('/shops/{shop:id}/booking', [SalonController::class, 'form'])->name('salon.form');
        Route::get('/shops/{shop:id}/booking/availability', [SalonController::class, 'availability'])->name('salon.availability');
        Route::post('/promoter-posts/{post}/visit-shop', [PromoterController::class, 'visitShop'])->name('promoters.visit-shop');
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

        // Free market: selling and buying.
        Route::post('/market/{listing}/order', [MarketController::class, 'order'])->middleware('throttle:10,1,market.order')->name('market.order');
        Route::get('/me/market/listings', [MarketController::class, 'mine'])->name('market.mine');
        Route::post('/me/market/listings', [MarketController::class, 'store'])->middleware('throttle:20,1,market.store')->name('market.store');
        Route::post('/me/market/listings/{listing}', [MarketController::class, 'update'])->middleware('throttle:30,1,market.update')->name('market.update');
        Route::patch('/me/market/listings/{listing}/toggle', [MarketController::class, 'toggle'])->name('market.toggle');
        Route::delete('/me/market/listings/{listing}', [MarketController::class, 'destroy'])->name('market.destroy');
        Route::get('/me/market/purchases', [MarketController::class, 'purchases'])->name('market.purchases');
        Route::get('/me/market/sales', [MarketController::class, 'sales'])->name('market.sales');
        Route::patch('/me/market/orders/{order}', [MarketController::class, 'updateOrder'])->middleware('throttle:30,1,market.orders')->name('market.orders.update');

        // Creators: following and gifts, applying, and the creator's own page.
        Route::put('/promoters/{profile}/follow', [PromoterController::class, 'follow'])->middleware('throttle:20,1,promoters.follow')->name('promoters.follow');
        Route::delete('/promoters/{profile}/follow', [PromoterController::class, 'unfollow'])->middleware('throttle:20,1,promoters.unfollow')->name('promoters.unfollow');
        Route::post('/promoters/{profile}/gifts', [PromoterController::class, 'gift'])->middleware('throttle:20,1,promoters.gift')->name('promoters.gift');
        Route::post('/me/promoter/apply', [PromoterController::class, 'apply'])->middleware('throttle:5,1,promoters.apply')->name('promoters.apply');
        Route::get('/me/promoter', [PromoterController::class, 'mine'])->name('promoters.mine');
        Route::post('/me/promoter/profile', [PromoterController::class, 'updateProfile'])->middleware('throttle:20,1,promoters.profile')->name('promoters.profile');
        Route::post('/me/promoter/posts', [PromoterController::class, 'storePost'])->middleware('throttle:20,1,promoters.posts.store')->name('promoters.posts.store');
        Route::patch('/me/promoter/posts/{post}', [PromoterController::class, 'updatePost'])->name('promoters.posts.update');
        Route::delete('/me/promoter/posts/{post}', [PromoterController::class, 'destroyPost'])->name('promoters.posts.destroy');

        // Salon bookings, service requests and property inquiries.
        Route::post('/shops/{shop:id}/booking', [SalonController::class, 'store'])->middleware('throttle:10,1,salon.book')->name('salon.book');
        Route::get('/me/appointments', [SalonController::class, 'mine'])->name('salon.mine');
        Route::post('/real-estate/services/{service}/request', [RealEstateController::class, 'requestService'])->middleware('throttle:10,1,real-estate.request')->name('real-estate.request');
        Route::post('/real-estate/properties/{property}/inquiry', [RealEstateController::class, 'inquiry'])->middleware('throttle:10,1,real-estate.inquiry')->name('real-estate.inquiry');

        // Owners: salon bookings, restaurant tables and drivers, service-company inbox.
        Route::get('/merchant/shops/{shop:id}/appointments', [SalonController::class, 'index'])->name('merchant.appointments');
        Route::patch('/merchant/appointments/{appointment}', [SalonController::class, 'status'])->name('merchant.appointments.status');
        Route::get('/merchant/shops/{shop:id}/tables', [RestaurantManageController::class, 'tables'])->name('merchant.tables');
        Route::post('/merchant/shops/{shop:id}/tables', [RestaurantManageController::class, 'storeTable'])->name('merchant.tables.store');
        Route::delete('/merchant/tables/{table}', [RestaurantManageController::class, 'destroyTable'])->name('merchant.tables.destroy');
        Route::get('/merchant/shops/{shop:id}/drivers', [RestaurantManageController::class, 'drivers'])->name('merchant.drivers');
        Route::post('/merchant/shops/{shop:id}/drivers', [RestaurantManageController::class, 'storeDriver'])->middleware('throttle:10,1,merchant.drivers.store')->name('merchant.drivers.store');
        Route::patch('/merchant/drivers/{driver}', [RestaurantManageController::class, 'toggleDriver'])->name('merchant.drivers.toggle');
        Route::patch('/merchant/orders/{order:id}/driver', [RestaurantManageController::class, 'assign'])->name('merchant.orders.driver');
        Route::get('/merchant/shops/{shop:id}/inbox', [RealEstateController::class, 'inbox'])->name('merchant.inbox');
        Route::patch('/merchant/service-requests/{serviceRequest}', [RealEstateController::class, 'updateRequest'])->name('merchant.service-requests.update');
        Route::patch('/merchant/leads/{lead}', [RealEstateController::class, 'updateLead'])->name('merchant.leads.update');

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
