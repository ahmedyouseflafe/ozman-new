<?php

use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DistributorController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\ShopController;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/front/shops/{shop}', [FrontController::class, 'index'])->name('front.shop');

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
    Route::get('/dashboard', function () {
        if (auth()->user()?->isShopOwner() && auth()->user()->shops()->exists()) {
            return redirect()->route('shops.show', auth()->user()->shops()->first());
        }

        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/dashboard/main', function () {
        if (auth()->user()?->isShopOwner() && auth()->user()->shops()->exists()) {
            return redirect()->route('shops.show', auth()->user()->shops()->first());
        }

        return view('admin.dashboard_main');
    })->name('dashboard.main');

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

    Route::get('/settings', function () {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.settings');
    })->name('settings');

    Route::post('/admin-notifications/read-all', function () {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

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
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.users');
    })->name('users');
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['ar', 'he'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');
