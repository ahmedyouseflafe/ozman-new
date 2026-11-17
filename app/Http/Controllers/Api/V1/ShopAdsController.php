<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\{AdvertisementController, Controller, ScreenController};
use App\Models\{Advertisement, MainScreen, Shop, User};
use Illuminate\Http\{JsonResponse, Request};

/**
 * The shop's ads (offers sent to followers and shown on its display) and its screens (the banners at the top or
 * bottom of its page) in the app. Every change runs through the dashboard's own code: validation, uploads, notifications.
 */
class ShopAdsController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);

        return response()->json([
            'ads' => $this->can($user, 'ads') ? Advertisement::query()->where('shop_id', $shop->id)->orderBy('sort_order')->latest()->get()
                ->map(fn ($ad) => $this->presentAd($ad))->values() : null,
            'screens' => $this->can($user, 'screens') ? MainScreen::query()->where('shop_id', $shop->id)->latest()->get()
                ->map(fn ($screen) => $this->presentScreen($screen))->values() : null,
            'display_url' => route('display.shop', $shop),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    // ── Ads ──────────────────────────────────────────────────────────────────

    public function storeAd(Request $request, Shop $shop, AdvertisementController $site): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        abort_unless($this->can($request->user(), 'ads.store'), 403);
        $request->merge(['shop_id' => $shop->id]);
        $before = (int) Advertisement::max('id');
        app()->call([$site, 'store'], ['request' => $request]);
        $ad = Advertisement::query()->where('shop_id', $shop->id)->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['ad' => $this->presentAd($ad), 'message' => __('تمت إضافة الإعلان بنجاح.')], 201);
    }

    public function updateAd(Request $request, Advertisement $ad, AdvertisementController $site): JsonResponse
    {
        $this->authorizeShop($request->user(), $ad->shop);
        abort_unless($this->can($request->user(), 'ads.update'), 403);
        $request->merge(['shop_id' => $ad->shop_id]);
        app()->call([$site, 'update'], ['request' => $request, 'ad' => $ad]);

        return response()->json(['ad' => $this->presentAd($ad->fresh()), 'message' => __('تم تحديث الإعلان بنجاح.')]);
    }

    public function destroyAd(Request $request, Advertisement $ad, AdvertisementController $site): JsonResponse
    {
        $this->authorizeShop($request->user(), $ad->shop);
        abort_unless($this->can($request->user(), 'ads.destroy'), 403);
        app()->call([$site, 'destroy'], ['ad' => $ad]);

        return response()->json(['message' => __('تم حذف الإعلان بنجاح.')]);
    }

    // ── Screens ──────────────────────────────────────────────────────────────

    public function storeScreen(Request $request, Shop $shop, ScreenController $site): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $request->merge(['shop_id' => $shop->id]);
        $request->attributes->set('dashboard_route', 'screens.store');
        $before = (int) MainScreen::max('id');
        app()->call([$site, 'store'], ['request' => $request]);
        $screen = MainScreen::query()->where('shop_id', $shop->id)->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['screen' => $this->presentScreen($screen), 'message' => __('تمت إضافة شاشة رئيسية بنجاح.')], 201);
    }

    public function updateScreen(Request $request, MainScreen $screen, ScreenController $site): JsonResponse
    {
        abort_unless($screen->shop, 404);
        $this->authorizeShop($request->user(), $screen->shop);
        $request->merge(['shop_id' => $screen->shop_id]);
        $request->attributes->set('dashboard_route', 'screens.update');
        app()->call([$site, 'update'], ['request' => $request, 'screen' => $screen]);

        return response()->json(['screen' => $this->presentScreen($screen->fresh()), 'message' => __('تم تحديث الشاشة بنجاح.')]);
    }

    public function destroyScreen(Request $request, MainScreen $screen, ScreenController $site): JsonResponse
    {
        abort_unless($screen->shop, 404);
        $this->authorizeShop($request->user(), $screen->shop);
        $request->attributes->set('dashboard_route', 'screens.destroy');
        app()->call([$site, 'destroy'], ['screen' => $screen]);

        return response()->json(['message' => __('تم حذف الشاشة بنجاح.')]);
    }

    private function presentAd(Advertisement $ad): array
    {
        return [
            'id' => $ad->id, 'title' => $ad->title, 'description' => $ad->description, 'type' => $ad->type,
            'media' => $ad->media, 'media_url' => $this->mediaUrl($ad->type, $ad->media),
            'duration' => (int) $ad->duration, 'sort_order' => (int) $ad->sort_order, 'is_active' => (bool) $ad->is_active,
        ];
    }

    private function presentScreen(MainScreen $screen): array
    {
        return [
            'id' => $screen->id, 'title' => $screen->title, 'type' => $screen->type,
            'media' => $screen->media, 'media_url' => $this->mediaUrl($screen->type, $screen->media),
            'placement' => $screen->placement ?: 'top', 'duration' => (int) $screen->duration, 'is_active' => (bool) $screen->is_active,
        ];
    }

    private function mediaUrl(string $type, ?string $media): ?string
    {
        if (! $media || $type === 'youtube' || str_starts_with($media, 'http')) {
            return $media;
        }

        return asset(ltrim($media, '/'));
    }

    private function can(User $user, string $route): bool
    {
        return $user->isSuperAdmin() || $user->canAccessRouteName($route);
    }

    private function authorizeShop(User $user, ?Shop $shop): void
    {
        abort_unless($shop, 404);
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
