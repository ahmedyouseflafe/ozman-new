<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Models\SocialPost;
use App\Services\Social\ShopSocialManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/** The shop's social media manager: connect accounts, let Claude write posts, publish or schedule them. */
class ShopSocialController extends Controller
{
    public function __construct(private ShopSocialManager $social) {}

    private function shop(Request $request): Shop
    {
        $shops = $this->accessibleShops();
        $id = $request->integer('shop_id') ?: (int) $request->session()->get('current_shop_id') ?: $this->firstAccessibleShopId();
        $shop = $shops->firstWhere('id', $id) ?: $shops->first();
        abort_unless($shop, 404);

        return $shop;
    }

    private function back(Shop $shop): RedirectResponse
    {
        return redirect()->route('social.index', ['shop_id' => $shop->id]);
    }

    public function index(Request $request): View
    {
        $shop = $this->shop($request);
        $accounts = $this->social->accounts($shop);

        return view('admin.social.index', [
            'shop' => $shop,
            'shops' => $this->accessibleShops(),
            'configured' => $this->social->configured(),
            'writerReady' => $this->social->writerReady(),
            'accounts' => $accounts['accounts'],
            'suggestions' => $accounts['suggestions'],
            'apiError' => $accounts['error'],
            'products' => Product::query()->where('shop_id', $shop->id)->where('is_active', true)->latest()->limit(300)->get(),
            'posts' => $this->social->posts($shop),
            'timezone' => config('services.zernio.timezone'),
        ]);
    }

    public function connect(Request $request, string $platform): RedirectResponse
    {
        abort_unless(in_array($platform, \App\Services\Social\Zernio::PLATFORMS, true), 404);
        $shop = $this->shop($request);
        try {
            $url = $this->social->connectUrl($shop, $platform, route('social.callback', ['shop_id' => $shop->id]));
        } catch (RuntimeException $e) {
            return $this->back($shop)->withErrors(['social' => __('ما قدرنا نفتح الربط: :error', ['error' => $e->getMessage()])]);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $shop = $this->shop($request);
        if ($request->filled('error')) {
            return $this->back($shop)->withErrors(['social' => __('الربط ما كمل: :error', ['error' => Str::limit((string) ($request->query('error_message') ?: $request->query('error')), 160)])]);
        }

        return $this->back($shop)->with('status', __('تم ربط الحساب 🎉 هلقيت بتقدر تنشر عليه.'));
    }

    /** Where the app's OAuth tab lands after connecting: there's no session there, so it only says to go back to the app. */
    public function appCallback(Request $request): \Illuminate\Http\Response
    {
        $failed = $request->filled('error');

        return response()->view('front.social_connected', [
            'failed' => $failed,
            'error' => $failed ? Str::limit((string) ($request->query('error_message') ?: $request->query('error')), 160) : null,
        ]);
    }

    public function caption(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        try {
            $caption = $this->social->caption($shop, $request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('الكاتب الذكي مش متاح هلقيت: :error', ['error' => $e->getMessage()])], 503);
        }

        return response()->json(['caption' => $caption]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $this->shop($request);
        try {
            [, $when] = $this->social->publish($shop, $request->user(), $request);
        } catch (RuntimeException $e) {
            return $this->back($shop)->withErrors(['social' => __('ما انتشر المنشور: :error', ['error' => $e->getMessage()])]);
        }

        return $this->back($shop)->with('status', $when
            ? __('تمام! المنشور مجدول :time.', ['time' => $when->translatedFormat('l j/n H:i')])
            : __('تم إرسال المنشور للنشر 🚀'));
    }

    public function destroy(Request $request, SocialPost $post): RedirectResponse
    {
        $shop = $this->shop($request);
        abort_unless($post->shop_id === $shop->id, 404);
        try {
            $this->social->cancel($shop, $post);
        } catch (RuntimeException $e) {
            return $this->back($shop)->withErrors(['social' => __('ما قدرنا نلغي المنشور: :error', ['error' => $e->getMessage()])]);
        }

        return $this->back($shop)->with('status', __('انلغى المنشور.'));
    }
}
