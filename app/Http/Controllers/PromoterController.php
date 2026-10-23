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
        abort_unless($request->user()->isSuperAdmin() || (int) $profile->user_id === (int) $request->user()->id, 403);
    }

    public function accounts(Request $request)
    {
        $this->admin($request);

        return view('promoters.accounts', ['profiles' => PromoterProfile::with('user')->withCount('posts')->latest()->paginate(20)]);
    }

    public function createAccount(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['display_name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', 'unique:promoter_profiles,slug'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:10', 'max:128']]);
        $profile = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['display_name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'promoter', 'is_active' => true]);

            return PromoterProfile::create(['user_id' => $user->id, 'slug' => $data['slug'], 'display_name' => $data['display_name'], 'is_active' => true]);
        });

        return redirect()->route('promoter.manage', $profile)->with('status', 'تم إنشاء حساب المروّجة. الدخول بالبريد وكلمة المرور اللذين حددتهما.');
    }

    public function updateAccount(Request $request, PromoterProfile $profile)
    {
        $this->admin($request);
        $data = $request->validate(['is_active' => ['required', 'boolean'], 'password' => ['nullable', 'string', 'min:10', 'max:128']]);
        DB::transaction(function () use ($profile, $data) {
            $profile->update(['is_active' => $data['is_active']]);
            $fields = ['is_active' => $data['is_active']];
            if (! empty($data['password'])) {
                $fields += ['password' => $data['password'], 'remember_token' => null];
            }
            $profile->user->forceFill($fields)->save();
        });

        return back()->with('status', 'تم تحديث الحساب.');
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

        return view('promoters.editor', ['profile' => $profile,
            'posts' => $profile->posts()->with('shop')->latest()->paginate(12),
            'shops' => Shop::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
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
            $profile->update(['display_name' => $data['display_name'], 'bio' => $data['bio'] ?? null, ...($path ? ['avatar' => $path] : [])]);
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
            $profile->posts()->create(['kind' => $data['kind'], 'type' => str_starts_with($request->file('media')->getMimeType(), 'video/') ? 'video' : 'image',
                'shop_id' => $data['shop_id'] ?? null, 'caption' => $data['caption'] ?? null, 'media' => $media, 'poster' => $poster,
                'is_active' => true, 'expires_at' => $data['kind'] === 'story' ? now()->addDay() : null]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$media, $poster]));
            throw $e;
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

    public function directory()
    {
        return response()->view('promoters.directory', ['profiles' => PromoterProfile::published()->withCount(['posts' => fn ($q) => $q->visible()->where('kind', 'post')])->latest()->paginate(24)])->header('Cache-Control', 'no-store');
    }

    public function show(string $slug)
    {
        $profile = PromoterProfile::published()->where('slug', $slug)->firstOrFail();
        $visible = $profile->posts()->visible()->with('shop');
        $posts = (clone $visible)->where('kind', 'post')->latest()->paginate(12);
        $stories = (clone $visible)->where('kind', 'story')->oldest('id')->get();
        $videos = (clone $visible)->where('kind', 'post')->where('type', 'video')->latest()->limit(30)->get();
        $shopCount = (clone $visible)->whereHas('shop', fn ($q) => $q->where('is_active', true))->distinct()->count('shop_id');

        return response()->view('promoters.profile', compact('profile', 'posts', 'stories', 'videos', 'shopCount'))->header('Cache-Control', 'no-store');
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
