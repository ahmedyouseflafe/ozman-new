<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Models\SocialPost;
use App\Services\Social\CaptionWriter;
use App\Services\Social\Zernio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/** The shop's social media manager: connect accounts, let Claude write posts, publish or schedule them. */
class ShopSocialController extends Controller
{
    public function __construct(private Zernio $zernio, private CaptionWriter $writer) {}

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
        $accounts = [];
        $bestTimes = [];
        $apiError = null;
        if ($this->zernio->configured() && $shop->zernio_profile_id) {
            try {
                $accounts = $this->zernio->accounts($shop->zernio_profile_id);
                $bestTimes = $this->zernio->bestTimes($shop->zernio_profile_id);
            } catch (RuntimeException $e) {
                $apiError = $e->getMessage();
            }
        }

        return view('admin.social.index', [
            'shop' => $shop,
            'shops' => $this->accessibleShops(),
            'configured' => $this->zernio->configured(),
            'writerReady' => $this->writer->configured(),
            'accounts' => $accounts,
            'suggestions' => $this->suggestions($bestTimes),
            'apiError' => $apiError,
            'products' => Product::query()->where('shop_id', $shop->id)->where('is_active', true)->latest()->limit(300)->get(),
            'posts' => SocialPost::query()->where('shop_id', $shop->id)->with('product')->latest()->limit(40)->get(),
            'timezone' => config('services.zernio.timezone'),
        ]);
    }

    public function connect(Request $request, string $platform): RedirectResponse
    {
        abort_unless(in_array($platform, Zernio::PLATFORMS, true), 404);
        $shop = $this->shop($request);
        try {
            $profile = $this->zernio->ensureProfile($shop);
            $url = $this->zernio->connectUrl($platform, $profile, route('social.callback', ['shop_id' => $shop->id]));
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

    public function caption(Request $request): JsonResponse
    {
        $shop = $this->shop($request);
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('shop_id', $shop->id)],
            'language' => ['required', Rule::in(['ar', 'he', 'en'])],
            'idea' => ['nullable', 'string', 'max:500'],
        ]);
        abort_if(empty($data['product_id']) && blank($data['idea'] ?? null), 422, __('اختار منتج أو اكتب فكرة المنشور.'));
        try {
            $caption = $this->writer->write($shop, isset($data['product_id']) ? Product::find($data['product_id']) : null, $data['language'], $data['idea'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('الكاتب الذكي مش متاح هلقيت: :error', ['error' => $e->getMessage()])], 503);
        }

        return response()->json(['caption' => $caption]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $this->shop($request);
        $data = $request->validate([
            'caption' => ['required', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('shop_id', $shop->id)],
            'add_link' => ['nullable', 'boolean'],
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*' => ['string', 'max:64'],
            'when' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_at' => ['nullable', 'required_if:when,schedule', 'date_format:Y-m-d\TH:i'],
        ], [
            'accounts.required' => __('اختار حساب واحد على الأقل.'),
            'scheduled_at.required_if' => __('اختار وقت النشر.'),
        ]);
        abort_unless($shop->zernio_profile_id, 422);

        // Only the shop's own connected accounts can be targeted.
        $owned = collect($this->zernio->accounts($shop->zernio_profile_id))->keyBy('id');
        $targets = collect($data['accounts'])->unique()->map(fn ($id) => $owned->get($id))->filter(fn ($a) => $a && $a['active'])->values();
        if ($targets->isEmpty() || $targets->count() !== count(array_unique($data['accounts']))) {
            throw ValidationException::withMessages(['accounts' => __('واحد من الحسابات مش مربوط بهالمحل.')]);
        }

        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;
        $media = $product ? $this->imageUrl($product->main_image) : null;
        if (! $media && $targets->contains(fn ($a) => in_array($a['platform'], ['instagram', 'tiktok'], true))) {
            throw ValidationException::withMessages(['product_id' => __('إنستغرام وتيك توك بدهم صورة: اختار منتج إله صورة.')]);
        }

        $caption = trim($data['caption']);
        if ($product && $request->boolean('add_link')) {
            $caption .= "\n\n".__('🛒 اطلب من هون:').' '.route('share.product', $product);
        }

        $timezone = config('services.zernio.timezone');
        $when = $data['when'] === 'schedule' ? Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], $timezone) : null;
        if ($when && $when->isPast()) {
            throw ValidationException::withMessages(['scheduled_at' => __('وقت النشر لازم يكون بالمستقبل.')]);
        }

        $post = SocialPost::create([
            'shop_id' => $shop->id, 'product_id' => $product?->id, 'user_id' => $request->user()->id,
            'caption' => $caption, 'media_url' => $media, 'status' => 'publishing',
            'targets' => $targets->map(fn ($a) => ['id' => $a['id'], 'platform' => $a['platform'], 'username' => $a['username']])->all(),
            'scheduled_for' => $when?->copy()->utc(),
        ]);

        try {
            $result = $this->zernio->createPost(array_filter([
                'content' => $caption,
                'mediaItems' => $media ? [['type' => 'image', 'url' => $media]] : null,
                'platforms' => $targets->map(fn ($a) => ['platform' => $a['platform'], 'accountId' => $a['id']])->all(),
                'publishNow' => $when ? null : true,
                'scheduledFor' => $when?->copy()->utc()->toIso8601ZuluString(),
                'timezone' => $timezone,
                'profileId' => $shop->zernio_profile_id,
                'metadata' => ['ozman_post_id' => $post->id, 'shop_id' => $shop->id],
            ], fn ($v) => $v !== null));
        } catch (RuntimeException $e) {
            $post->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 500)]);

            return $this->back($shop)->withErrors(['social' => __('ما انتشر المنشور: :error', ['error' => $e->getMessage()])]);
        }

        $post->update(['zernio_post_id' => data_get($result, 'post._id'), 'status' => (string) data_get($result, 'post.status', $when ? 'scheduled' : 'published')]);

        return $this->back($shop)->with('status', $when
            ? __('تمام! المنشور مجدول :time.', ['time' => $when->translatedFormat('l j/n H:i')])
            : __('تم إرسال المنشور للنشر 🚀'));
    }

    public function destroy(Request $request, SocialPost $post): RedirectResponse
    {
        $shop = $this->shop($request);
        abort_unless($post->shop_id === $shop->id, 404);
        if ($post->zernio_post_id && $post->status === 'scheduled') {
            try {
                $this->zernio->deletePost($post->zernio_post_id);
            } catch (RuntimeException $e) {
                return $this->back($shop)->withErrors(['social' => __('ما قدرنا نلغي المنشور: :error', ['error' => $e->getMessage()])]);
            }
        }
        $post->update(['status' => 'cancelled']);

        return $this->back($shop)->with('status', __('انلغى المنشور.'));
    }

    /** Next dates for the shop's best hours, or sensible defaults while there is no history yet. */
    private function suggestions(array $bestTimes): array
    {
        $tz = config('services.zernio.timezone');
        $now = now($tz);
        $slots = collect($bestTimes)->map(fn ($s) => ['day' => (int) ($s['day_of_week'] ?? 0), 'hour' => (int) ($s['hour'] ?? 19), 'learned' => true]);
        if ($slots->isEmpty()) {
            // Evenings and lunch are when local customers scroll and order most.
            $slots = collect([[$now->dayOfWeek, 13], [$now->dayOfWeek, 19], [$now->dayOfWeek, 21], [($now->dayOfWeek + 1) % 7, 19]])
                ->map(fn ($p) => ['day' => $p[0], 'hour' => $p[1], 'learned' => false]);
        }

        return $slots->map(function ($s) use ($now) {
            $at = $now->copy()->startOfDay()->addDays(($s['day'] - $now->dayOfWeek + 7) % 7)->setTime($s['hour'], 0);
            if ($at->lte($now->copy()->addMinutes(10))) {
                $at->addWeek();
            }

            return ['value' => $at->format('Y-m-d\TH:i'), 'label' => $at->translatedFormat('l H:i'), 'learned' => $s['learned']];
        })->unique('value')->sortBy('value')->values()->all();
    }

    private function imageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Same as the storefronts: uploads are saved as "storage/products/…" and served with asset().
        return asset(ltrim($path, '/'));
    }
}
