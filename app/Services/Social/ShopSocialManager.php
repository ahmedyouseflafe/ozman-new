<?php

namespace App\Services\Social;

use App\Models\{Product, Shop, SocialPost, User};
use Illuminate\Http\Request;
use Illuminate\Support\{Carbon, Collection, Str};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The shop's social media: its connected accounts, Claude's captions, and publishing or scheduling posts through Zernio.
 * The dashboard page and the app both use this, so the rules are the same.
 */
class ShopSocialManager
{
    public function __construct(private Zernio $zernio, private CaptionWriter $writer) {}

    public function configured(): bool
    {
        return $this->zernio->configured();
    }

    public function writerReady(): bool
    {
        return $this->writer->configured();
    }

    /** @return array{accounts: array, suggestions: array, error: ?string} */
    public function accounts(Shop $shop): array
    {
        $accounts = [];
        $bestTimes = [];
        $error = null;
        if ($this->zernio->configured() && $shop->zernio_profile_id) {
            try {
                $accounts = $this->zernio->accounts($shop->zernio_profile_id);
                $bestTimes = $this->zernio->bestTimes($shop->zernio_profile_id);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return ['accounts' => $accounts, 'suggestions' => $this->suggestions($bestTimes), 'error' => $error];
    }

    public function posts(Shop $shop): Collection
    {
        return $this->refreshStatuses(SocialPost::query()->where('shop_id', $shop->id)->with('product')->latest()->limit(40)->get());
    }

    /** The OAuth page where the owner signs in to the platform; it comes back to $redirectUrl. */
    public function connectUrl(Shop $shop, string $platform, string $redirectUrl): string
    {
        abort_unless(in_array($platform, Zernio::PLATFORMS, true), 404);

        return $this->zernio->connectUrl($platform, $this->zernio->ensureProfile($shop), $redirectUrl);
    }

    public function caption(Shop $shop, Request $request): string
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('shop_id', $shop->id)],
            'language' => ['required', Rule::in(['ar', 'he', 'en'])],
            'idea' => ['nullable', 'string', 'max:500'],
        ]);
        if (empty($data['product_id']) && blank($data['idea'] ?? null)) {
            throw ValidationException::withMessages(['idea' => __('اختار منتج أو اكتب فكرة المنشور.')]);
        }

        return $this->writer->write($shop, isset($data['product_id']) ? Product::find($data['product_id']) : null, $data['language'], $data['idea'] ?? null);
    }

    /**
     * Publishes now or schedules the post on the shop's own accounts.
     *
     * @return array{0: SocialPost, 1: ?Carbon} the post and when it's scheduled (null = now)
     *
     * @throws ValidationException|RuntimeException when Zernio refuses it (the post is kept as failed)
     */
    public function publish(Shop $shop, User $user, Request $request): array
    {
        $data = $request->validate([
            'caption' => ['required', 'string', 'max:2000'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime', 'max:102400'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('shop_id', $shop->id)],
            'add_link' => ['nullable', 'boolean'],
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*' => ['string', 'max:64'],
            'when' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_at' => ['nullable', 'required_if:when,schedule', 'date_format:Y-m-d\TH:i'],
        ], [
            'accounts.required' => __('اختار حساب واحد على الأقل.'),
            'scheduled_at.required_if' => __('اختار وقت النشر.'),
            'media.*.mimetypes' => __('الملف لازم يكون صورة (JPG أو PNG أو WebP) أو فيديو MP4.'),
            'media.*.max' => __('الملف أكبر من 100 ميغا.'),
        ]);
        if (! $shop->zernio_profile_id) {
            throw ValidationException::withMessages(['accounts' => __('اختار حساب واحد على الأقل.')]);
        }

        // Only the shop's own connected accounts can be targeted.
        $owned = collect($this->zernio->accounts($shop->zernio_profile_id))->keyBy('id');
        $targets = collect($data['accounts'])->unique()->map(fn ($id) => $owned->get($id))->filter(fn ($a) => $a && $a['active'])->values();
        if ($targets->isEmpty() || $targets->count() !== count(array_unique($data['accounts']))) {
            throw ValidationException::withMessages(['accounts' => __('واحد من الحسابات مش مربوط بهالمحل.')]);
        }

        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;
        $uploads = collect($request->file('media', []));
        $videos = $uploads->filter(fn ($f) => str_starts_with((string) $f->getMimeType(), 'video/'));
        if ($videos->isNotEmpty() && $uploads->count() > 1) {
            throw ValidationException::withMessages(['media' => __('ارفع فيديو واحد لحاله، أو لحد 10 صور.')]);
        }
        // Your own photos or video win; otherwise the product's photo from the menu.
        $mediaItems = $uploads->map(function ($file) use ($shop) {
            $path = 'storage/'.$file->store('social/'.$shop->id, 'public');

            return ['type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image', 'url' => asset($path)];
        })->values()->all();
        if (! $mediaItems && $product && ($image = $this->imageUrl($product->main_image))) {
            $mediaItems = [['type' => 'image', 'url' => $image]];
        }
        if (! $mediaItems && $targets->contains(fn ($a) => in_array($a['platform'], ['instagram', 'tiktok'], true))) {
            throw ValidationException::withMessages(['media' => __('إنستغرام وتيك توك بدهم صورة أو فيديو: ارفع صورة أو اختار منتج إله صورة.')]);
        }
        $media = $mediaItems[0]['url'] ?? null;

        $caption = trim($data['caption']);
        // Facebook makes links clickable; Instagram and TikTok show them as plain text,
        // so there the post points to the link in the account's bio instead.
        $custom = [];
        if ($request->boolean('add_link')) {
            $link = $product ? route('share.product', $product) : $shop->publicUrl();
            $plain = $caption;
            $caption = $plain."\n\n".__('🛒 اطلب من هون:').' '.$link;
            foreach (['instagram', 'tiktok'] as $platform) {
                $custom[$platform] = $plain."\n\n".__('🛒 للطلب: الرابط بالبايو 👆');
            }
        }

        $timezone = config('services.zernio.timezone');
        $when = $data['when'] === 'schedule' ? Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], $timezone) : null;
        if ($when && $when->isPast()) {
            throw ValidationException::withMessages(['scheduled_at' => __('وقت النشر لازم يكون بالمستقبل.')]);
        }

        $post = SocialPost::create([
            'shop_id' => $shop->id, 'product_id' => $product?->id, 'user_id' => $user->id,
            'caption' => $caption, 'media_url' => $media, 'status' => 'publishing',
            'targets' => $targets->map(fn ($a) => ['id' => $a['id'], 'platform' => $a['platform'], 'username' => $a['username']])->all(),
            'scheduled_for' => $when?->copy()->utc(),
        ]);

        try {
            $result = $this->zernio->createPost(array_filter([
                'content' => $caption,
                'mediaItems' => $mediaItems ?: null,
                'platforms' => $targets->map(fn ($a) => array_filter(['platform' => $a['platform'], 'accountId' => $a['id'], 'customContent' => $custom[$a['platform']] ?? null]))->all(),
                'publishNow' => $when ? null : true,
                'scheduledFor' => $when?->copy()->utc()->toIso8601ZuluString(),
                'timezone' => $timezone,
                'profileId' => $shop->zernio_profile_id,
                'metadata' => ['ozman_post_id' => $post->id, 'shop_id' => $shop->id],
            ], fn ($v) => $v !== null));
        } catch (RuntimeException $e) {
            $post->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 500)]);

            throw $e;
        }

        $post->update(['zernio_post_id' => data_get($result, 'post._id'), 'status' => (string) data_get($result, 'post.status', $when ? 'scheduled' : 'published')]);

        return [$post, $when];
    }

    /** @throws RuntimeException when Zernio can't cancel a scheduled post */
    public function cancel(Shop $shop, SocialPost $post): void
    {
        abort_unless($post->shop_id === $shop->id, 404);
        if ($post->zernio_post_id && $post->status === 'scheduled') {
            $this->zernio->deletePost($post->zernio_post_id);
        }
        $post->update(['status' => 'cancelled']);
    }

    /** Posts still publishing (or past their time) get their real status from Zernio. */
    private function refreshStatuses(Collection $posts): Collection
    {
        if (! $this->zernio->configured()) {
            return $posts;
        }
        $posts->filter(fn ($p) => $p->zernio_post_id && ($p->status === 'publishing' || ($p->status === 'scheduled' && $p->scheduled_for?->isPast())))
            ->take(10)->each(function (SocialPost $post) {
                try {
                    $status = (string) data_get($this->zernio->getPost($post->zernio_post_id), 'post.status', '');
                } catch (RuntimeException) {
                    return;
                }
                if ($status !== '' && $status !== $post->status) {
                    $post->update(['status' => $status]);
                }
            });

        return $posts;
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

    public function imageUrl(?string $path): ?string
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
