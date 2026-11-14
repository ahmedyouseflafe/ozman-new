<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\CommunityAccountController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PromoterController as SitePromoters;
use App\Http\Controllers\PromoterGiftController;
use App\Models\{PromoterGift, PromoterGiftSend, PromoterPost, PromoterProfile, PushDevice, Shop, User};
use App\Services\{FirebaseMessagingService, LoyaltyPoints, PromoterLevel};
use Illuminate\Validation\ValidationException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Promoters (creators) in the app: the directory, a creator's page, following, gifts from loyalty
 * points, applying to become one, and the creator's own page. Writes go through the website's code.
 */
class PromoterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort = $request->query('sort') === 'latest' ? 'latest' : 'reach';
        $query = PromoterProfile::published()->withCount(['followers', 'guestFollowers', 'posts' => fn ($q) => $q->visible()->where('kind', 'post')])
            ->when($request->filled('q'), fn ($q) => $q->where('display_name', 'like', '%'.addcslashes((string) $request->query('q'), '%_\\').'%'));
        $query = $sort === 'reach' ? $query->orderByRaw(PromoterLevel::scoreSql().' desc')->latest('id') : $query->latest();
        $page = $query->paginate(24);

        // The newest photo/video of each creator, as story bubbles at the top.
        $recent = $request->integer('page', 1) > 1 ? collect() : PromoterPost::query()->visible()
            ->whereHas('profile', fn ($q) => $q->published())
            ->where(fn ($q) => $q->where('type', 'image')->orWhereNotNull('poster'))
            ->with('profile')->latest('id')->limit(60)->get()->unique('promoter_profile_id')->take(12)->values();

        return response()->json([
            'data' => collect($page->items())->map(fn (PromoterProfile $profile) => $this->card($profile)),
            'stories' => $recent->map(fn (PromoterPost $post) => ['profile' => $this->card($post->profile)] + $this->post($post)),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, string $slug, LoyaltyPoints $loyalty): JsonResponse
    {
        $profile = PromoterProfile::published()->where('slug', $slug)->first()
            ?? PromoterProfile::published()->whereKey(DB::table('promoter_profile_slugs')->where('slug', $slug)->value('promoter_profile_id'))->firstOrFail();
        $user = $request->user();
        $mine = $profile->canBeManagedBy($user);
        // One visit per person (or phone) per day; the creator and admins don't count.
        if (! $mine && Cache::add('promoter_visit:'.$profile->id.':'.($user?->id ?? $request->ip()).':'.today()->toDateString(), 1, now()->endOfDay())) {
            PromoterProfile::query()->whereKey($profile->id)->increment('page_views');
            $profile->page_views = (int) $profile->page_views + 1;
        }
        $profile->loadCount(['followers', 'guestFollowers']);
        $visible = $profile->posts()->visible()->with('shop');
        $supporters = PromoterGiftSend::query()->where('promoter_profile_id', $profile->id)->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(points) as total_points, COUNT(*) as gifts_count')
            ->groupBy('user_id')->orderByDesc('total_points')->limit(5)->with('sender')->get();
        $shopIds = (clone $visible)->whereHas('shop', fn ($q) => $q->where('is_active', true))
            ->selectRaw('shop_id, MAX(id) as latest')->groupBy('shop_id')->orderByDesc('latest')->limit(12)->pluck('shop_id');
        $shops = Shop::whereIn('id', $shopIds)->get()->sortBy(fn ($shop) => $shopIds->search($shop->id))->values();

        return response()->json(['profile' => $this->card($profile) + [
            'bio' => $profile->bio,
            'share_url' => $profile->publicUrl(),
            'page_views' => (int) $profile->page_views,
            'shop_visits' => (int) $profile->posts()->sum('shop_clicks') + (int) $profile->archived_shop_clicks,
            'following' => $user ? $profile->followers()->where('users.id', $user->id)->exists() : false,
            'mine' => $mine,
        ],
            'stories' => (clone $visible)->where('kind', 'story')->oldest('id')->get()->map(fn ($post) => $this->post($post)),
            'posts' => (clone $visible)->where('kind', 'post')->latest()->limit(60)->get()->map(fn ($post) => $this->post($post)),
            'shops' => $shops->map(fn (Shop $shop) => Presenter::shop($shop)),
            // First names only, like the website.
            'supporters' => $supporters->map(fn ($row) => ['name' => strtok((string) $row->sender?->name, ' ') ?: '—', 'points' => (int) $row->total_points]),
            'gifting' => [
                'gifts' => PromoterGift::available()->get(['id', 'name', 'icon', 'points']),
                'can_gift' => ! $mine && $loyalty->eligibleCustomer($user),
                'balance' => $loyalty->eligibleCustomer($user) ? $loyalty->balance($user) : null,
            ],
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function follow(Request $request, PromoterProfile $profile): JsonResponse
    {
        abort_unless($profile->isPublished(), 404);
        abort_if($profile->isOwnedBy($request->user()), 422, 'ما بتقدر تتابع صفحتك.');
        DB::table('promoter_followers')->upsert([
            'promoter_profile_id' => $profile->id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
        ], ['promoter_profile_id', 'user_id'], ['updated_at']);

        return response()->json(['following' => true, 'followers' => $profile->followerTotal()]);
    }

    public function unfollow(Request $request, PromoterProfile $profile): JsonResponse
    {
        abort_unless($profile->isPublished(), 404);
        $profile->followers()->detach($request->user()->id);

        return response()->json(['following' => false, 'followers' => $profile->followerTotal()]);
    }

    public function gift(Request $request, PromoterProfile $profile, PromoterGiftController $gifts): JsonResponse
    {
        return app()->call([$gifts, 'store'], ['request' => $request, 'profile' => $profile]);
    }

    /** Counts a visit to the shop from a creator's post (once per person a day) and says which shop to open. */
    public function visitShop(Request $request, PromoterPost $post): JsonResponse
    {
        abort_unless($post->isVisible() && $post->shop?->is_active, 404);
        if (! $post->profile->canBeManagedBy($request->user())
            && Cache::add('promoter_shop_visit:'.$post->id.':'.($request->user()?->id ?? $request->ip()).':'.today()->toDateString(), 1, now()->endOfDay())) {
            PromoterPost::query()->whereKey($post->id)->increment('shop_clicks');
        }

        return response()->json(['shop' => Presenter::shop($post->shop)]);
    }

    /** A customer applies; the page waits for the admin, exactly like on the website. */
    public function apply(Request $request, CommunityAccountController $accounts): JsonResponse
    {
        abort_unless($request->user()->isCustomer(), 403, 'هاد الحساب مش حساب زبون.');
        app()->call([$accounts, 'becomePromoter'], ['r' => $request]);
        if (! $request->user()->fresh()->isPromoter()) {
            throw ValidationException::withMessages(['username' => 'اسم المستخدم مأخوذ كرابط صفحة. جرّب اسم ثاني.']);
        }

        return response()->json(['message' => 'وصل طلبك كمروّج 🎉 بيوصلك إيميل أول ما الإدارة تقبله.', 'user' => Presenter::user($request->user()->fresh())]);
    }

    // ── The creator's own page ──────────────────────────────────────────────

    public function mine(Request $request): JsonResponse
    {
        $profile = $this->own($request->user());
        $profile->loadCount(['followers', 'guestFollowers']);
        $gifts = $profile->giftSends()->with('user:id,name')->latest('id')->limit(30)->get();

        return response()->json(['profile' => $this->card($profile) + [
            'bio' => $profile->bio, 'share_url' => $profile->publicUrl(),
            'pending' => $profile->isPending(), 'active' => (bool) $profile->is_active,
            'page_views' => (int) $profile->page_views, 'gift_points' => (int) $profile->gift_points,
            'shop_visits' => (int) $profile->posts()->sum('shop_clicks') + (int) $profile->archived_shop_clicks,
        ],
            'posts' => $profile->posts()->with('shop')->latest()->limit(100)->get()->map(fn ($post) => $this->post($post, true)),
            'gifts' => $gifts->map(fn ($send) => ['icon' => $send->gift_icon, 'name' => $send->gift_name, 'points' => (int) $send->points,
                'from' => strtok((string) $send->user?->name, ' ') ?: '—', 'at' => $send->created_at?->toIso8601String()]),
            'shops' => Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function updateProfile(Request $request, SitePromoters $site): JsonResponse
    {
        $profile = $this->own($request->user());
        app()->call([$site, 'updateProfile'], ['request' => $request, 'profile' => $profile]);

        return response()->json(['profile' => $this->card($profile->fresh()), 'message' => 'تم حفظ الملف الشخصي.']);
    }

    public function storePost(Request $request, SitePromoters $site, FirebaseMessagingService $firebase): JsonResponse
    {
        $profile = $this->own($request->user());
        abort_if($profile->isPending(), 422, 'صفحتك لسا بانتظار موافقة الإدارة.');
        $request->headers->set('Accept', 'application/json');
        app()->call([$site, 'storePost'], ['request' => $request, 'profile' => $profile]);
        $post = $profile->posts()->latest('id')->firstOrFail();
        $this->tellFollowers($profile, $post, $firebase);

        return response()->json(['post' => $this->post($post->load('shop'), true),
            'message' => $post->kind === 'story' ? 'تم نشر الستوري بصفحتك لمدة 24 ساعة.' : 'تم نشر المحتوى بصفحتك.'], 201);
    }

    public function updatePost(Request $request, PromoterPost $post, SitePromoters $site): JsonResponse
    {
        $this->own($request->user(), $post->profile);
        $request->merge(['is_active' => $request->boolean('is_active', (bool) $post->is_active), 'caption' => $request->input('caption', $post->caption),
            'shop_id' => $request->exists('shop_id') ? $request->input('shop_id') : $post->shop_id]);
        app()->call([$site, 'updatePost'], ['request' => $request, 'post' => $post]);

        return response()->json(['post' => $this->post($post->fresh('shop'), true), 'message' => 'تم تحديث المنشور.']);
    }

    public function destroyPost(Request $request, PromoterPost $post, SitePromoters $site): JsonResponse
    {
        $this->own($request->user(), $post->profile);
        app()->call([$site, 'deletePost'], ['request' => $request, 'post' => $post]);

        return response()->json(['message' => 'تم حذف المنشور.']);
    }

    private function own(User $user, ?PromoterProfile $profile = null): PromoterProfile
    {
        $profile ??= $user->promoterProfile;
        abort_unless($profile && $profile->canBeManagedBy($user), 403, 'هاد الحساب ما إله صفحة مروّج.');

        return $profile;
    }

    /** The app's followers hear about a new post on their phones too (the website uses web push). */
    private function tellFollowers(PromoterProfile $profile, PromoterPost $post, FirebaseMessagingService $firebase): void
    {
        try {
            $tokens = PushDevice::whereIn('user_id', $profile->followers()->where('users.is_active', true)->pluck('users.id'))->pluck('token');
            if ($tokens->isNotEmpty()) {
                $firebase->sendToTokens($tokens, $profile->display_name.($post->kind === 'story' ? ' نزّل ستوري جديدة' : ' نزّل منشور جديد'),
                    $post->caption ? mb_strimwidth($post->caption, 0, 90, '…') : 'افتح صفحته وشوف الجديد.', $profile->publicUrl(),
                    ['type' => 'promoter_post', 'slug' => $profile->slug, 'post_id' => $post->id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function card(PromoterProfile $profile): array
    {
        $level = $profile->level();

        return [
            'id' => $profile->id, 'slug' => $profile->slug, 'name' => $profile->display_name,
            'avatar' => $profile->avatar ? route('promoters.avatar', $profile) : null,
            'followers' => $profile->followerTotal(),
            'posts_count' => isset($profile->posts_count) ? (int) $profile->posts_count : null,
            'level' => ['key' => $level['key'], 'name' => $level['name'], 'icon' => $level['icon'], 'progress' => $level['progress'],
                'next' => $level['next'] ? ['name' => $level['next']['name'], 'icon' => $level['next']['icon']] : null],
        ];
    }

    private function post(PromoterPost $post, bool $owner = false): array
    {
        return [
            'id' => $post->id, 'kind' => $post->kind, 'type' => $post->type, 'caption' => $post->caption,
            'media' => route('promoters.media', [$post, 'media']),
            'poster' => $post->poster ? route('promoters.media', [$post, 'poster']) : null,
            'shop' => $post->shop?->is_active ? ['id' => $post->shop->id, 'name' => $post->shop->name] : null,
            'created_at' => $post->created_at?->toIso8601String(),
            'expires_at' => $post->expires_at?->toIso8601String(),
        ] + ($owner ? ['is_active' => (bool) $post->is_active, 'shop_clicks' => (int) $post->shop_clicks, 'shop_id' => $post->shop_id] : []);
    }
}
