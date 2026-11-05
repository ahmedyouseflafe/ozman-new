<?php

namespace App\Http\Controllers;

use App\Models\PromoterPost;
use App\Models\PromoterProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PromoterController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->isSuperAdmin(), 403);
    }

    private function manage(Request $request, PromoterProfile $profile): void
    {
        abort_unless($profile->canBeManagedBy($request->user()), 403);
    }

    public function accounts(Request $request)
    {
        $this->admin($request);

        return view('promoters.accounts', [
            'pending' => PromoterProfile::pending()->with('user')->oldest()->get(),
            'profiles' => PromoterProfile::whereNotNull('approved_at')->with('user')->withCount('posts')->latest()->paginate(20),
        ]);
    }

    public function createAccount(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['display_name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', 'unique:promoter_profiles,slug', 'unique:promoter_profile_slugs,slug'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['nullable', 'string', 'min:10', 'max:128']]);
        $profile = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['display_name'], 'email' => $data['email'], 'password' => $data['password'] ?? \Illuminate\Support\Str::random(64), 'role' => 'promoter', 'is_active' => true]);

            $profile = PromoterProfile::create(['user_id' => $user->id, 'slug' => $data['slug'], 'display_name' => $data['display_name'], 'is_active' => true, 'approved_at' => now()]);
            DB::table('promoter_profile_slugs')->insert(['slug' => $profile->slug, 'promoter_profile_id' => $profile->id]);

            return $profile;
        });

        return redirect()->route('promoter.accounts')->with('status', 'تم إنشاء الحساب. انسخ رابط الدعوة وأرسله لصاحب الحساب ليختار كلمة مروره.')
            ->with('invitation_url', PromoterInvitationController::issue($profile->user));
    }

    public function updateAccount(Request $request, PromoterProfile $profile)
    {
        $this->admin($request);
        $data = $request->validate(['is_active' => ['required', 'boolean'], 'password' => ['nullable', 'string', 'min:10', 'max:128'],
            'email' => ['sometimes', 'required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($profile->user_id)]]);
        $emailChanged = isset($data['email']) && strcasecmp($data['email'], (string) $profile->user->email) !== 0;
        DB::transaction(function () use ($profile, $data, $emailChanged) {
            $profile->update(['is_active' => $data['is_active']]);
            $fields = ['is_active' => $data['is_active']];
            if ($emailChanged) {
                // Old remember-me sessions and invitation links belonged to the previous address.
                $fields += ['email' => $data['email'], 'remember_token' => null];
                DB::table('password_reset_tokens')->where('email', $profile->user->email)->delete();
            }
            if (! empty($data['password'])) {
                $fields += ['password' => $data['password'], 'remember_token' => null];
            }
            $profile->user->forceFill($fields)->save();
            if (! $data['is_active'] || ! empty($data['password']) || $emailChanged) {
                DB::table('promoter_invitations')->where('user_id', $profile->user_id)->delete();
            }
        });

        return back()->with('status', 'تم تحديث الحساب.');
    }

    /** Accept a promoter who signed up: the page goes live and they get an email. */
    public function approveApplication(Request $request, PromoterProfile $profile)
    {
        $this->admin($request);
        abort_unless($profile->isPending() && $profile->user?->isPromoter(), 404);
        DB::transaction(function () use ($profile) {
            $profile->update(['approved_at' => now(), 'is_active' => true]);
            $profile->user->forceFill(['is_active' => true])->save();
        });
        $this->notifyApplicant($profile->user, true);

        return back()->with('status', __('تم قبول :name. صفحته صارت ظاهرة ووصله إيميل.', ['name' => $profile->display_name]));
    }

    /** Decline: the page and its reserved link are removed; the person keeps a customer account. */
    public function rejectApplication(Request $request, PromoterProfile $profile)
    {
        $this->admin($request);
        abort_unless($profile->isPending() && $profile->user?->isPromoter(), 404);
        $user = $profile->user;
        $paths = array_filter([$profile->avatar]);
        DB::transaction(function () use ($profile, $user) {
            DB::table('promoter_profile_slugs')->where('promoter_profile_id', $profile->id)->delete();
            $profile->delete();
            $user->forceFill(['role' => 'customer'])->save();
        });
        Storage::disk('local')->delete($paths);
        $this->notifyApplicant($user, false);

        return back()->with('status', __('تم رفض الطلب. الحساب ضل كحساب عميل.'));
    }

    private function notifyApplicant(User $user, bool $approved): void
    {
        try {
            $user->notify(new \App\Notifications\PromoterApplicationDecided($approved));
        } catch (\Throwable $e) {
            // The decision is saved either way; a mail outage must not undo it.
            report($e);
        }
    }

    public function dashboard(Request $request)
    {
        if ($request->user()->isSuperAdmin()) {
            return redirect()->route('promoter.accounts');
        }

        return redirect()->route('promoter.manage', $request->user()->promoterProfile);
    }

    public function editor(Request $request, PromoterProfile $profile)
    {
        $this->manage($request, $profile);

        $profile->loadCount(['followers', 'guestFollowers']);
        // Supporters and followers are shown by first name only, like on the public page.
        $gifts = $profile->giftSends()->with('user:id,name')->latest('id')->paginate(10, ['*'], 'gifts');
        $followers = $profile->followers()->select('users.id', 'users.name', 'users.avatar_path', 'users.role')
            ->orderByPivot('created_at', 'desc')->paginate(20, ['*'], 'followers');

        return view('promoters.editor', ['profile' => $profile,
            'posts' => $profile->posts()->with('shop')->latest()->paginate(12),
            'shops' => Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gifts' => $gifts, 'followers' => $followers,
            'shopVisits' => (int) $profile->posts()->sum('shop_clicks') + (int) $profile->archived_shop_clicks]);
    }

    public function updateProfile(Request $request, PromoterProfile $profile)
    {
        $this->manage($request, $profile);
        $data = $request->validate(['display_name' => ['required', 'string', 'max:100'], 'bio' => ['nullable', 'string', 'max:600'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $path = $request->file('avatar')?->store('promoters/avatars', 'local');
        abort_if($request->hasFile('avatar') && ! $path, 500, 'تعذّر حفظ الصورة.');
        $old = $profile->avatar;
        try {
            DB::transaction(function () use ($profile, $data, $path) {
                $profile->update(['display_name' => $data['display_name'], 'bio' => $data['bio'] ?? null, ...($path ? ['avatar' => $path] : [])]);
                app(\App\Services\PromoterProfileUrl::class)->sync($profile);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
        if ($path && $old) {
            Storage::disk('local')->delete($old);
        }

        return back()->with('status', 'تم حفظ الملف الشخصي.');
    }

    public function deleteAvatar(Request $request, PromoterProfile $profile)
    {
        $this->manage($request, $profile);
        $path = $profile->avatar;
        $profile->update(['avatar' => null]);
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('status', 'تم حذف صورة الملف الشخصي.');
    }

    public function storePost(Request $request, PromoterProfile $profile)
    {
        $this->manage($request, $profile);
        $data = $request->validate(['kind' => ['required', Rule::in(['story', 'post'])], 'caption' => ['nullable', 'string', 'max:2000'],
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('is_active', true)],
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:20480'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $media = null;
        $poster = null;
        try {
            $media = $request->file('media')->store('promoters/posts', 'local');
            $poster = $request->file('poster')?->store('promoters/posters', 'local');
            abort_unless($media, 500);
            abort_if($request->hasFile('poster') && ! $poster, 500, 'تعذّر حفظ غلاف الفيديو.');
            DB::transaction(function () use ($profile, $data, $request, $media, $poster) {
            $post = $profile->posts()->create(['kind' => $data['kind'], 'type' => str_starts_with($request->file('media')->getMimeType(), 'video/') ? 'video' : 'image',
                'shop_id' => $data['shop_id'] ?? null, 'caption' => $data['caption'] ?? null, 'media' => $media, 'poster' => $poster,
                'is_active' => true, 'expires_at' => $data['kind'] === 'story' ? now()->addDay() : null]);
            app(\App\Services\PromoterFollowerNotifications::class)->publish($post);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$media, $poster]));
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['published' => true, 'url' => $profile->publicUrl()], 201);
        }

        return back()->with('status', $data['kind'] === 'story' ? 'تم نشر الستوري بصفحتك لمدة 24 ساعة.' : 'تم نشر المحتوى بصفحتك.');
    }

    public function updatePost(Request $request, PromoterPost $post)
    {
        $this->manage($request, $post->profile);
        $data = $request->validate(['caption' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean'],
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('is_active', true)]]);
        $post->update($data);

        return back()->with('status', 'تم تحديث المنشور.');
    }

    public function deletePost(Request $request, PromoterPost $post)
    {
        $this->manage($request, $post->profile);
        $paths = array_filter([$post->media, $post->poster]);
        $post->delete();
        Storage::disk('local')->delete($paths);

        return back()->with('status', 'تم حذف المنشور.');
    }

    public function directory(Request $request)
    {
        // Default: the pages with the most reach first, so shops see who customers actually visit.
        $sort = $request->query('sort') === 'latest' ? 'latest' : 'reach';
        $query = PromoterProfile::published()->withCount(['followers', 'guestFollowers', 'posts' => fn ($q) => $q->visible()->where('kind', 'post')]);
        $query = $sort === 'reach'
            ? $query->orderByRaw(\App\Services\PromoterLevel::scoreSql().' desc')->latest('id')
            : $query->latest();

        // Latest visible photos/videos from published pages, shown in the hero so visitors see real stories first.
        $recent = PromoterPost::query()->visible()
            ->whereHas('profile', fn ($q) => $q->published())
            ->where(fn ($q) => $q->where('type', 'image')->orWhereNotNull('poster'))
            // One story bubble per creator, newest first.
            ->with('profile')->latest('id')->limit(60)->get()->unique('promoter_profile_id')->take(12)->values();

        return response()->view('promoters.directory', ['profiles' => $query->paginate(24)->withQueryString(), 'sort' => $sort, 'recent' => $recent])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, string $slug)
    {
        $profile = PromoterProfile::published()->where('slug', $slug)->first();
        if (! $profile) {
            $profileId = DB::table('promoter_profile_slugs')->where('slug', $slug)->value('promoter_profile_id');
            $profile = PromoterProfile::published()->whereKey($profileId)->firstOrFail();

            return redirect()->to($profile->publicUrl(), 302)->header('Cache-Control', 'no-store');
        }
        $canManage = $profile->canBeManagedBy($request->user());
        $this->countVisit($request, $profile, $canManage);
        $profile->loadCount(['followers', 'guestFollowers']);
        $following = $request->user()
            ? $profile->followers()->where('users.id', $request->user()->id)->exists()
            : app(\App\Services\GuestFollows::class)->isFollowing($request, $profile);
        $visible = $profile->posts()->visible()->with('shop');
        $posts = (clone $visible)->where('kind', 'post')->latest()->paginate(12);
        $stories = (clone $visible)->where('kind', 'story')->oldest('id')->get();
        $videos = (clone $visible)->where('kind', 'post')->where('type', 'video')->latest()->limit(30)->get();
        $shopCount = (clone $visible)->whereHas('shop', fn ($q) => $q->where('is_active', true))->distinct()->count('shop_id');
        $shops = $canManage ? Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();

        // Shops this promoter talks about: newest post per shop gives the tracked "visit" link.
        $shopRows = (clone $visible)->whereHas('shop', fn ($q) => $q->where('is_active', true))
            ->selectRaw('shop_id, COUNT(*) as posts_count, MAX(id) as latest_post_id')
            ->groupBy('shop_id')->orderByDesc('latest_post_id')->limit(12)->get();
        $latestPosts = PromoterPost::query()->with('shop')->whereKey($shopRows->pluck('latest_post_id'))->get()->keyBy('id');
        $featuredShops = $shopRows->map(fn ($row) => ['post' => $latestPosts[$row->latest_post_id] ?? null, 'posts_count' => (int) $row->posts_count])
            ->filter(fn ($row) => $row['post']?->shop)->values();
        $shopVisits = (int) $profile->posts()->sum('shop_clicks') + (int) $profile->archived_shop_clicks;
        // Top supporters by gift points; only first names are shown publicly.
        $supporters = \App\Models\PromoterGiftSend::query()->where('promoter_profile_id', $profile->id)->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(points) as total_points, COUNT(*) as gifts_count')
            ->groupBy('user_id')->orderByDesc('total_points')->limit(5)->with('sender')->get();

        $loyalty = app(\App\Services\LoyaltyPoints::class);
        $gifting = [
            'gifts' => \App\Models\PromoterGift::available()->get(['id', 'name', 'icon', 'points']),
            'canGift' => ! $canManage && $loyalty->eligibleCustomer($request->user()),
            'balance' => $loyalty->eligibleCustomer($request->user()) ? $loyalty->balance($request->user()) : null,
        ];

        return response()->view('promoters.profile', compact('profile', 'posts', 'stories', 'videos', 'shopCount', 'following', 'canManage', 'shops', 'gifting', 'featuredShops', 'shopVisits', 'supporters'))->header('Cache-Control', 'private, no-store');
    }

    /** One visit per visitor session per day; the promoter and admins don't count. */
    private function countVisit(Request $request, PromoterProfile $profile, bool $canManage): void
    {
        if ($canManage || preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp/i', (string) $request->userAgent())) {
            return;
        }
        $key = 'promoter_visits.'.$profile->id;
        if ($request->session()->get($key) === now()->toDateString()) {
            return;
        }
        $request->session()->put($key, now()->toDateString());
        PromoterProfile::query()->whereKey($profile->id)->increment('page_views');
        $profile->page_views = (int) $profile->page_views + 1;
    }

    /** Counts one shop visit per visitor session per post, then opens the shop. */
    public function visitShop(Request $request, PromoterPost $post)
    {
        abort_unless($post->isVisible() && $post->shop?->is_active, 404);
        $key = 'promoter_shop_visits.'.$post->id;
        if (! $request->session()->has($key) && ! $post->profile->canBeManagedBy($request->user())
            && ! preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp/i', (string) $request->userAgent())) {
            $request->session()->put($key, true);
            PromoterPost::query()->whereKey($post->id)->increment('shop_clicks');
        }

        return redirect()->away($post->shop->publicUrl());
    }

    public function avatar(Request $request, PromoterProfile $profile)
    {
        abort_unless($profile->isPublished() || $this->canPreview($request, $profile), 404);

        return $this->file($profile->avatar);
    }

    private function canPreview(Request $request, PromoterProfile $profile): bool
    {
        $user = $request->user();

        return $user?->is_active && ($user->isSuperAdmin() || ($user->isPromoter() && $profile->is_active && $profile->user_id === $user->id));
    }

    public function media(Request $request, PromoterPost $post, string $part = 'media')
    {
        abort_unless($post->isVisible() || $this->canPreview($request, $post->profile), 404);
        abort_unless(in_array($part, ['media', 'poster'], true), 404);

        return $this->file($post->$part);
    }

    private function file(?string $path)
    {
        abort_unless($path && str_starts_with($path, 'promoters/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
