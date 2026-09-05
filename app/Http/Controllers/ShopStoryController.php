<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopStory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $stories = ShopStory::whereIn('shop_id', $shops->pluck('id'))->with('shop')->latest()->paginate(20);
        return view('admin.shop_stories.index', compact('shops', 'stories'));
    }

    public function store(Request $request)
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
            ShopStory::create([
                'shop_id' => $shop->id, 'caption' => $data['caption'] ?? null,
                'media' => $path, 'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                'expires_at' => now()->addHours(24),
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
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
}
