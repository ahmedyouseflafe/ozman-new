<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopStory;
use App\Models\FrontOrder;
use App\Models\PushDevice;
use App\Models\ShopStoryView;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopStoryController extends Controller
{
    private function managedShops(Request $request)
    {
        abort_unless($request->user()->is_active && ($request->user()->isSuperAdmin() || $request->user()->isShopOwner()), 403);
        return Shop::query()->when(! $request->user()->isSuperAdmin(),
            fn ($query) => $query->where('user_id', $request->user()->id));
    }

    public function index(Request $request)
    {
        $shops = $this->managedShops($request)->orderBy('name')->get();
        abort_if($shops->isEmpty(), 403);
        $stories = ShopStory::whereIn('shop_id', $shops->pluck('id'))
            ->with(['shop', 'views' => fn ($query) => $query->with('user:id,name')->latest('last_viewed_at')->limit(100)])
            ->withCount('views')
            ->latest()
            ->paginate(20);
        return view('admin.shop_stories.index', compact('shops', 'stories'));
    }

    public function store(Request $request, WebPushService $webPush)
    {
        $data = $request->validate([
            'shop_id' => ['required', 'integer'],
            'caption' => ['nullable', 'string', 'max:300'],
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:20480'],
        ]);
        $shop = $this->managedShops($request)->findOrFail($data['shop_id']);
        $file = $request->file('media');
        $path = $file->store('shop-stories', 'local');
        abort_unless($path, 500);
        try {
            $story = ShopStory::create([
                'shop_id' => $shop->id, 'caption' => $data['caption'] ?? null,
                'media' => $path, 'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                'expires_at' => now()->addHours(24),
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        $webPush->sendOfferToAll(
            $shop,
            'عرض جديد من '.$shop->name,
            $story->caption ?: 'افتح الستوري لمشاهدة آخر العروض.',
            $shop->publicUrl(),
            ['type' => 'shop_story', 'story_id' => $story->id],
        );

        return back()->with('status', 'تم نشر الستوري لمدة 24 ساعة.');
    }

    public function destroy(Request $request, ShopStory $story)
    {
        $this->managedShops($request)->findOrFail($story->shop_id);
        $path = $story->media;
        $story->delete();
        Storage::disk('local')->delete($path);
        return back()->with('status', 'تم حذف الستوري.');
    }

    public function feed()
    {
        $stories = ShopStory::where('expires_at', '>', now())
            ->whereHas('shop', fn ($query) => $query->where('is_active', true))
            ->with('shop:id,name,slug,catalog_type,logo,banner')->orderBy('id')->get();
        return response()->json($stories->groupBy('shop_id')->map(function ($items) {
            $shop = $items->first()->shop;
            return [
                'id' => $shop->id, 'title' => $shop->name, 'url' => $shop->publicUrl(),
                'logo' => asset($shop->logo ?: 'images/logo.jpg'),
                'stories' => $items->map(fn ($story) => [
                    'id' => $story->id, 'type' => $story->type, 'caption' => $story->caption,
                    'src' => route('shop-stories.media', $story),
                    'view_url' => route('shop-stories.view', $story),
                    'expires_at' => $story->expires_at->toIso8601String(),
                ])->values(),
            ];
        })->values())->header('Cache-Control', 'no-store');
    }

    public function media(ShopStory $story)
    {
        abort_unless($story->expires_at->isFuture() && $story->shop?->is_active, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($story->media), 404);
        return response()->file($disk->path($story->media), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function recordView(Request $request, ShopStory $story)
    {
        $story->loadMissing('shop:id,user_id,is_active');
        abort_unless($story->expires_at->isFuture() && $story->shop?->is_active, 404);

        $authenticatedUser = $request->user();
        if ($authenticatedUser && ($authenticatedUser->isSuperAdmin() || (int) $story->shop->user_id === (int) $authenticatedUser->id)) {
            return response()->json(['recorded' => false, 'reason' => 'owner_preview']);
        }

        $viewerCookie = (string) $request->cookie('ozman_story_viewer', '');
        if (! Str::isUuid($viewerCookie)) {
            $viewerCookie = (string) Str::uuid();
        }

        $appToken = $request->session()->get('app_push_token');
        $appToken = is_string($appToken) && $appToken !== '' ? $appToken : null;
        $identifiedUser = $authenticatedUser;
        if (! $identifiedUser && $appToken) {
            $deviceUserId = PushDevice::query()->where('token', $appToken)->value('user_id');
            $identifiedUser = $deviceUserId ? User::query()->find($deviceUserId) : null;
        }

        $source = str_contains((string) $request->userAgent(), 'OzmanApp/') ? 'app' : 'web';
        $viewerName = $identifiedUser?->name
            ?: $request->session()->get('restaurant_customer_names.'.$story->shop_id);
        if (! $viewerName && $appToken) {
            $viewerName = FrontOrder::query()
                ->where('shop_id', $story->shop_id)
                ->where('customer_push_token', $appToken)
                ->whereNotNull('customer_name')
                ->latest('id')
                ->value('customer_name');
        }
        $viewerName = $viewerName ?: ($source === 'app' ? 'زائر من التطبيق' : 'زائر من الموقع');
        $viewerKey = $identifiedUser
            ? hash('sha256', 'user:'.$identifiedUser->id)
            : ($appToken ? hash('sha256', 'device:'.$appToken) : hash('sha256', 'visitor:'.$viewerCookie));

        $timestamp = now();
        ShopStoryView::query()->upsert([[
            'shop_story_id' => $story->id,
            'user_id' => $identifiedUser?->id,
            'viewer_key' => $viewerKey,
            'viewer_name' => Str::limit($viewerName, 255, ''),
            'source' => $source,
            'viewed_at' => $timestamp,
            'last_viewed_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]], ['shop_story_id', 'viewer_key'], [
            'user_id', 'viewer_name', 'source', 'last_viewed_at', 'updated_at',
        ]);

        return response()->json(['recorded' => true])
            ->cookie('ozman_story_viewer', $viewerCookie, 525600, '/', null, $request->isSecure(), true, false, 'lax');
    }
}
