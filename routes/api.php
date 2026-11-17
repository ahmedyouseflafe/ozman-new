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
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\NetworkController;
use App\Http\Controllers\Api\V1\ShopSettingsController;
use App\Http\Controllers\Api\V1\TryOnController;
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
    // A new shop signs up through a distributor's or marketer's referral QR.
    Route::get('/auth/shop-referral', [AuthController::class, 'shopReferral'])->middleware('throttle:20,1,auth.shop-referral')->name('auth.shop-referral');
    Route::post('/auth/register-shop', [AuthController::class, 'registerShop'])->middleware('throttle:5,1,auth.register-shop')->name('auth.register-shop');
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
        Route::get('/real-estate/compare', [RealEstateController::class, 'compare'])->name('real-estate.compare');
        Route::post('/real-estate/alerts', [RealEstateController::class, 'alert'])->middleware('throttle:5,1,real-estate.alert')->name('real-estate.alert');
        Route::get('/shops/{shop:id}/booking', [SalonController::class, 'form'])->name('salon.form');
        Route::get('/shops/{shop:id}/booking/availability', [SalonController::class, 'availability'])->name('salon.availability');
        // Website links opened in the app, the raffle card check and the marketer's wheel.
        Route::get('/links/resolve', [LinkController::class, 'resolve'])->name('links.resolve');
        Route::post('/raffle/check', [NetworkController::class, 'raffle'])->middleware('throttle:10,1,raffle.check')->name('raffle.check');
        Route::get('/marketer-wheel/{code}', [NetworkController::class, 'wheel'])->name('marketer-wheel');
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

        // The customers' shopping assistant.
        Route::get('/assistant', [\App\Http\Controllers\CustomerAssistantController::class, 'show'])->name('assistant');
        Route::post('/assistant', [\App\Http\Controllers\CustomerAssistantController::class, 'send'])->middleware('throttle:10,1,assistant.send')->name('assistant.send');
        Route::post('/assistant/reset', [\App\Http\Controllers\CustomerAssistantController::class, 'reset'])->name('assistant.reset');

        // Real estate companies: properties with photos, and services with sections and options.
        Route::get('/merchant/shops/{shop:id}/social', [\App\Http\Controllers\Api\V1\SocialController::class, 'index'])->name('merchant.social');
        Route::post('/merchant/shops/{shop:id}/social/connect/{platform}', [\App\Http\Controllers\Api\V1\SocialController::class, 'connect'])->middleware('throttle:20,1')->name('merchant.social.connect');
        Route::post('/merchant/shops/{shop:id}/social/caption', [\App\Http\Controllers\Api\V1\SocialController::class, 'caption'])->middleware('throttle:20,1')->name('merchant.social.caption');
        Route::post('/merchant/shops/{shop:id}/social/posts', [\App\Http\Controllers\Api\V1\SocialController::class, 'store'])->middleware('throttle:20,1')->name('merchant.social.posts.store');
        Route::delete('/merchant/social-posts/{post}', [\App\Http\Controllers\Api\V1\SocialController::class, 'destroy'])->name('merchant.social.posts.destroy');
        Route::get('/merchant/shops/{shop:id}/ads', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'index'])->name('merchant.ads');
        Route::post('/merchant/shops/{shop:id}/ads', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'storeAd'])->middleware('throttle:30,1')->name('merchant.ads.store');
        Route::post('/merchant/ads/{ad}', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'updateAd'])->middleware('throttle:60,1')->name('merchant.ads.update');
        Route::delete('/merchant/ads/{ad}', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'destroyAd'])->name('merchant.ads.destroy');
        Route::post('/merchant/shops/{shop:id}/screens', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'storeScreen'])->middleware('throttle:30,1')->name('merchant.screens.store');
        Route::post('/merchant/screens/{screen}', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'updateScreen'])->middleware('throttle:60,1')->name('merchant.screens.update');
        Route::delete('/merchant/screens/{screen}', [\App\Http\Controllers\Api\V1\ShopAdsController::class, 'destroyScreen'])->name('merchant.screens.destroy');
        Route::get('/merchant/shops/{shop:id}/outlets', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'outlets'])->name('merchant.outlets');
        Route::post('/merchant/shops/{shop:id}/outlets', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'storeOutlet'])->middleware('throttle:60,1')->name('merchant.outlets.store');
        Route::post('/merchant/outlets/{outlet}', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'updateOutlet'])->middleware('throttle:60,1')->name('merchant.outlets.update');
        Route::delete('/merchant/outlets/{outlet}', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'destroyOutlet'])->name('merchant.outlets.destroy');
        Route::post('/me/network/distributors/{distributor}/marketers', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'storeMarketer'])->middleware('throttle:30,1')->name('network.marketers.store');
        Route::post('/me/network/marketers/{marketer}', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'updateMarketer'])->middleware('throttle:60,1')->name('network.marketers.update');
        Route::delete('/me/network/marketers/{marketer}', [\App\Http\Controllers\Api\V1\KremboManageController::class, 'destroyMarketer'])->name('network.marketers.destroy');
        Route::get('/merchant/shops/{shop:id}/customers', [\App\Http\Controllers\Api\V1\MerchantCustomersController::class, 'index'])->name('merchant.customers');
        Route::get('/merchant/shops/{shop:id}/properties', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'properties'])->name('merchant.properties');
        Route::post('/merchant/shops/{shop:id}/properties', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'storeProperty'])->middleware('throttle:30,1,merchant.properties')->name('merchant.properties.store');
        Route::get('/merchant/properties/{property}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'property'])->name('merchant.properties.show');
        Route::post('/merchant/properties/{property}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'updateProperty'])->middleware('throttle:60,1,merchant.properties')->name('merchant.properties.update');
        Route::delete('/merchant/properties/{property}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'destroyProperty'])->name('merchant.properties.destroy');
        Route::delete('/merchant/property-images/{image}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'destroyPropertyImage'])->name('merchant.property-images.destroy');
        Route::patch('/merchant/property-images/{image}/cover', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'coverPropertyImage'])->name('merchant.property-images.cover');
        Route::get('/merchant/shops/{shop:id}/services', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'services'])->name('merchant.services');
        Route::post('/merchant/shops/{shop:id}/services', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'storeService'])->middleware('throttle:30,1,merchant.services')->name('merchant.services.store');
        Route::get('/merchant/services/{service}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'service'])->name('merchant.services.show');
        Route::post('/merchant/services/{service}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'updateService'])->middleware('throttle:60,1,merchant.services')->name('merchant.services.update');
        Route::delete('/merchant/services/{service}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'destroyService'])->name('merchant.services.destroy');
        Route::delete('/merchant/service-images/{image}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'destroyServiceImage'])->name('merchant.service-images.destroy');
        Route::post('/merchant/shops/{shop:id}/service-categories', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'storeCategory'])->name('merchant.service-categories.store');
        Route::post('/merchant/service-categories/{category}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'updateCategory'])->name('merchant.service-categories.update');
        Route::delete('/merchant/service-categories/{category}', [\App\Http\Controllers\Api\V1\RealEstateManageController::class, 'destroyCategory'])->name('merchant.service-categories.destroy');

        // Reward wheels: the signup wheel and the purchase wheels.
        Route::get('/merchant/shops/{shop:id}/wheels', [\App\Http\Controllers\Api\V1\RewardWheelsController::class, 'index'])->name('merchant.wheels');
        Route::post('/merchant/shops/{shop:id}/wheels/signup', [\App\Http\Controllers\Api\V1\RewardWheelsController::class, 'saveSignup'])->middleware('throttle:30,1,merchant.wheels')->name('merchant.wheels.signup');
        Route::post('/merchant/shops/{shop:id}/wheels/purchase', [\App\Http\Controllers\Api\V1\RewardWheelsController::class, 'storePurchase'])->middleware('throttle:30,1,merchant.wheels')->name('merchant.wheels.purchase.store');
        Route::post('/merchant/wheels/{wheel}', [\App\Http\Controllers\Api\V1\RewardWheelsController::class, 'updatePurchase'])->middleware('throttle:30,1,merchant.wheels')->name('merchant.wheels.purchase.update');
        Route::delete('/merchant/wheels/{wheel}', [\App\Http\Controllers\Api\V1\RewardWheelsController::class, 'destroyPurchase'])->name('merchant.wheels.purchase.destroy');

        // The shop owner's AI assistant (confirming a change is in the session group below).
        Route::get('/merchant/shops/{shop:id}/assistant', [\App\Http\Controllers\ShopAssistantController::class, 'show'])->name('merchant.assistant');
        Route::post('/merchant/shops/{shop:id}/assistant', [\App\Http\Controllers\ShopAssistantController::class, 'send'])->middleware('throttle:15,1,merchant.assistant.send')->name('merchant.assistant.send');
        Route::post('/merchant/shops/{shop:id}/assistant/reset', [\App\Http\Controllers\ShopAssistantController::class, 'reset'])->name('merchant.assistant.reset');
        Route::post('/merchant/assistant-actions/{action}/cancel', [\App\Http\Controllers\ShopAssistantController::class, 'cancel'])->name('merchant.assistant.cancel');

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

        // Photo try-on, the owner's shop details, and agents/distributors/marketers.
        Route::post('/products/{product:id}/try-on', [TryOnController::class, 'create'])->middleware('throttle:6,60,try-on')->name('try-on.create');
        Route::get('/shops/{shop:id}/try-on/{job}', [TryOnController::class, 'status'])->whereUuid('job')->middleware('throttle:60,1,try-on.status')->name('try-on.status');
        Route::get('/merchant/shops/{shop:id}/settings', [ShopSettingsController::class, 'show'])->name('merchant.settings');
        Route::post('/merchant/shops/{shop:id}/settings', [ShopSettingsController::class, 'update'])->middleware('throttle:20,1,merchant.settings')->name('merchant.settings.update');
        Route::get('/me/network', [NetworkController::class, 'mine'])->name('network.mine');
        Route::get('/me/network/orders', [NetworkController::class, 'orders'])->name('network.orders');

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
        Route::post('/merchant/assistant-actions/{action}/confirm', [\App\Http\Controllers\ShopAssistantController::class, 'confirm'])->name('merchant.assistant.confirm');
        // The marketer's wheel keeps its fair draw in a session, like the website.
        Route::post('/marketer-wheel/{code}/spin', [NetworkController::class, 'spin'])->withoutMiddleware([ApiAuthenticated::class])->name('marketer-wheel.spin');
    });
});
