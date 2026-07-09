<?php

use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DistributorController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\FrontOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RaffleCardController;
use App\Http\Controllers\RewardWheelController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\TextToSpeechController;
use App\Http\Controllers\VisitorRegistrationAdminController;
use App\Http\Controllers\VisitorRegistrationController;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/market', [FrontController::class, 'index'])->name('front.home');
Route::get('/front/shops/{shop}', [FrontController::class, 'index'])->name('front.shop');
Route::get('/stores/{shop:slug}', [FrontController::class, 'index'])->name('front.shop.slug');
Route::get('/distributor-stores/{distributor}', [FrontController::class, 'distributor'])->name('front.distributor');
Route::get('/marketer-stores/{marketer:tracking_code}', [FrontController::class, 'marketer'])->name('front.marketer');
Route::view('/customer-login', 'front.customer_login')->name('customer.login');
Route::get('/tts/hebrew', [TextToSpeechController::class, 'hebrew'])->name('tts.hebrew');
Route::get('/tts/arabic', [TextToSpeechController::class, 'arabic'])->name('tts.arabic');
Route::post('/visitor-registrations', [VisitorRegistrationController::class, 'store'])->name('visitor-registrations.store');
Route::post('/front-orders', [FrontOrderController::class, 'store'])->name('front-orders.store');
Route::post('/raffle/check', [RaffleCardController::class, 'check'])->name('raffle.check');
Route::get('/front-orders/{order}/qr.svg', [FrontOrderController::class, 'qr'])->name('front-orders.qr');
Route::post('/front-orders/{order}/spin-reward', [FrontOrderController::class, 'spinReward'])->name('front-orders.spinReward');
Route::patch('/front-orders/{order}/reward', [FrontOrderController::class, 'reward'])->name('front-orders.reward');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/display', [ScreenController::class, 'mainDisplay'])->name('display.main');
Route::get('/display/shop/{shop}', [ScreenController::class, 'shopDisplay'])->name('display.shop');

Route::middleware(['auth', 'admin.access'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::get('/dashboard/main', function () {
        if (auth()->user()?->isShopOwner() && auth()->user()->shops()->exists()) {
            return redirect()->route('shops.show', auth()->user()->shops()->first());
        }

        if (auth()->user()?->isAgent() || auth()->user()?->isDistributor()) {
            return redirect()->route('products');
        }

        return view('admin.dashboard_main');
    })->name('dashboard.main');

    Route::post('/translations/suggest', [TranslationController::class, 'suggest'])->name('translations.suggest');

    Route::get('/ads', [AdvertisementController::class, 'index'])->name('ads');
    Route::get('/ads/create', [AdvertisementController::class, 'create'])->name('ads.create');
    Route::post('/ads/store', [AdvertisementController::class, 'store'])->name('ads.store');
    Route::get('/ads/{ad}/edit', [AdvertisementController::class, 'edit'])->name('ads.edit');
    Route::put('/ads/{ad}', [AdvertisementController::class, 'update'])->name('ads.update');
    Route::delete('/ads/{ad}', [AdvertisementController::class, 'destroy'])->name('ads.destroy');
    Route::get('/ads/{ad}', [AdvertisementController::class, 'show'])->name('ads.show');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents');
    Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
    Route::post('/agents/store', [AgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent}/edit', [AgentController::class, 'edit'])->name('agents.edit');
    Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
    Route::get('/agents/{agent}/permissions', [AgentController::class, 'editPermissions'])->name('agents.permissions.edit');
    Route::put('/agents/{agent}/permissions', [AgentController::class, 'updatePermissions'])->name('agents.permissions.update');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories/store', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/distributors', [DistributorController::class, 'index'])->name('distributors');
    Route::get('/distributors/create', [DistributorController::class, 'create'])->name('distributors.create');
    Route::post('/distributors/store', [DistributorController::class, 'store'])->name('distributors.store');
    Route::get('/distributors/{distributor}/edit', [DistributorController::class, 'edit'])->name('distributors.edit');
    Route::put('/distributors/{distributor}', [DistributorController::class, 'update'])->name('distributors.update');
    Route::delete('/distributors/{distributor}', [DistributorController::class, 'destroy'])->name('distributors.destroy');
    Route::get('/distributor-marketers', [DistributorController::class, 'marketersIndex'])->name('distributors.marketers.index');
    Route::post('/distributors/{distributor}/marketers', [DistributorController::class, 'storeMarketer'])->name('distributors.marketers.store');
    Route::get('/distributors/{distributor}/permissions', [DistributorController::class, 'editPermissions'])->name('distributors.permissions.edit');
    Route::put('/distributors/{distributor}/permissions', [DistributorController::class, 'updatePermissions'])->name('distributors.permissions.update');
    Route::get('/distributor-marketers/{marketer}/permissions', [DistributorController::class, 'editMarketerPermissions'])->name('distributors.marketers.permissions.edit');
    Route::put('/distributor-marketers/{marketer}/permissions', [DistributorController::class, 'updateMarketerPermissions'])->name('distributors.marketers.permissions.update');
    Route::put('/distributor-marketers/{marketer}', [DistributorController::class, 'updateMarketer'])->name('distributors.marketers.update');
    Route::patch('/distributor-marketers/{marketer}/commission', [DistributorController::class, 'updateMarketerCommission'])->name('distributors.marketers.commission.update');
    Route::delete('/distributor-marketers/{marketer}', [DistributorController::class, 'destroyMarketer'])->name('distributors.marketers.destroy');
    Route::get('/distributors/{distributor}', [DistributorController::class, 'show'])->name('distributors.show');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/preview/storefront', [FrontController::class, 'dashboardPreview'])->name('products.preview');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/screens', [ScreenController::class, 'index'])->name('screens');
    Route::get('/screens/create', [ScreenController::class, 'create'])->name('screens.create');
    Route::post('/screens/store', [ScreenController::class, 'store'])->name('screens.store');
    Route::get('/screens/{screen}/edit', [ScreenController::class, 'edit'])->name('screens.edit');
    Route::put('/screens/{screen}', [ScreenController::class, 'update'])->name('screens.update');
    Route::delete('/screens/{screen}', [ScreenController::class, 'destroy'])->name('screens.destroy');
    Route::get('/screens/{screen}', [ScreenController::class, 'show'])->name('screens.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('/settings/system', [SettingsController::class, 'updateSystem'])->name('settings.system.update');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');

    Route::post('/admin-notifications/read-all', function () {
        abort_unless(auth()->user()?->isSuperAdmin() || (auth()->user()?->isEmployee() && auth()->user()?->canAccessRouteName('admin.notifications.readAll')), 403);

        AdminNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    })->name('admin.notifications.readAll');

    Route::get('/shops', [ShopController::class, 'index'])->name('shops');
    Route::get('/shops/create', [ShopController::class, 'create'])->name('shops.create');
    Route::get('/shops/ozman', [ShopController::class, 'ozman'])->name('shops.ozman');
    Route::post('/shops/store', [ShopController::class, 'store'])->name('shops.store');
    Route::get('/shops/{shop}/edit', [ShopController::class, 'edit'])->name('shops.edit');
    Route::put('/shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');
    Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('shops.show');

    Route::get('/users', function () {
        abort_unless(
            auth()->user()?->isSuperAdmin()
                || (auth()->user()?->isEmployee() && auth()->user()?->canAccessRouteName('users')),
            403
        );

        return view('admin.users');
    })->name('users');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/employees/{employee}/permissions', [EmployeeController::class, 'editPermissions'])->name('employees.permissions.edit');
    Route::put('/employees/{employee}/permissions', [EmployeeController::class, 'updatePermissions'])->name('employees.permissions.update');

    Route::get('/visitor-registrations', [VisitorRegistrationAdminController::class, 'index'])
        ->name('visitor-registrations.index');

    Route::get('/front-orders', [FrontOrderController::class, 'index'])
        ->name('front-orders.index');
    Route::patch('/front-orders/{order}/status', [FrontOrderController::class, 'status'])
        ->name('front-orders.status');

    Route::get('/raffle-cards', [RaffleCardController::class, 'index'])
        ->name('raffle-cards.index');
    Route::post('/raffle-cards', [RaffleCardController::class, 'store'])
        ->name('raffle-cards.store');
    Route::post('/raffle-cards/live-draw/random', [RaffleCardController::class, 'randomLiveDraw'])
        ->name('raffle-cards.live-draw.random');
    Route::put('/raffle-cards/settings', [RaffleCardController::class, 'updateSettings'])
        ->name('raffle-cards.settings');
    Route::put('/raffle-cards/{card}', [RaffleCardController::class, 'update'])
        ->name('raffle-cards.update');
    Route::delete('/raffle-cards/{card}', [RaffleCardController::class, 'destroy'])
        ->name('raffle-cards.destroy');

    Route::get('/reward-wheels/customer-signup', [RewardWheelController::class, 'edit'])
        ->name('reward-wheels.customer-signup.edit');
    Route::put('/reward-wheels/customer-signup', [RewardWheelController::class, 'update'])
        ->name('reward-wheels.customer-signup.update');
    Route::get('/reward-wheels/purchase', [RewardWheelController::class, 'purchaseIndex'])
        ->name('reward-wheels.purchase.index');
    Route::post('/reward-wheels/purchase', [RewardWheelController::class, 'purchaseStore'])
        ->name('reward-wheels.purchase.store');
    Route::get('/reward-wheels/purchase/{wheel}/edit', [RewardWheelController::class, 'purchaseEdit'])
        ->name('reward-wheels.purchase.edit');
    Route::put('/reward-wheels/purchase/{wheel}', [RewardWheelController::class, 'purchaseUpdate'])
        ->name('reward-wheels.purchase.update');
    Route::delete('/reward-wheels/purchase/{wheel}', [RewardWheelController::class, 'purchaseDestroy'])
        ->name('reward-wheels.purchase.destroy');
    Route::get('/reward-wheels/marketer/settings', [RewardWheelController::class, 'marketerEdit'])
        ->name('reward-wheels.marketer.edit');
    Route::put('/reward-wheels/marketer/settings', [RewardWheelController::class, 'marketerUpdate'])
        ->name('reward-wheels.marketer.update');
    Route::get('/reward-wheels/marketer/play', [RewardWheelController::class, 'marketerPlay'])
        ->name('reward-wheels.marketer.play');
    Route::post('/reward-wheels/marketer/unlock', [RewardWheelController::class, 'marketerUnlock'])
        ->name('reward-wheels.marketer.unlock');
    Route::post('/reward-wheels/marketer/spin', [RewardWheelController::class, 'marketerSpin'])
        ->name('reward-wheels.marketer.spin');
    Route::post('/reward-wheels/marketer/reset', [RewardWheelController::class, 'marketerReset'])
        ->name('reward-wheels.marketer.reset');
    Route::get('/reward-wheels/marketer/direct/settings', [RewardWheelController::class, 'marketerDirectEdit'])
        ->name('reward-wheels.marketer.direct.edit');
    Route::put('/reward-wheels/marketer/direct/settings', [RewardWheelController::class, 'marketerDirectUpdate'])
        ->name('reward-wheels.marketer.direct.update');
    Route::get('/reward-wheels/marketer/direct/play', [RewardWheelController::class, 'marketerDirectPlay'])
        ->name('reward-wheels.marketer.direct.play');
    Route::post('/reward-wheels/marketer/direct/spin', [RewardWheelController::class, 'marketerDirectSpin'])
        ->name('reward-wheels.marketer.direct.spin');
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['ar', 'he', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');
