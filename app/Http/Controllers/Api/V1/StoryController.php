<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ShopStoryController;
use App\Models\{Shop, ShopStory, ShopStoryView, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;

/**
 * Shop stories in the app: the same 24-hour stories the website shows. Publishing and deleting go
 * through the website's own controller, so the limits, storage and the "new offer" push are identical.
 */
class StoryController extends Controller
{
    /** Live stories grouped by shop, for the strip on the home screen. */
    public function feed(): JsonResponse
    {
        $stories = ShopStory::where('expires_at', '>', now())
            ->whereHas('shop', fn ($query) => $query->where('is_active', true))
            ->with('shop:id,name,slug,catalog_type,logo')->orderBy('id')->get();

        return response()->json(['data' => $stories->groupBy('shop_id')->map(function ($items) {
            $shop = $items->first()->shop;

            return [
                'shop' => ['id' => $shop->id, 'name' => $shop->name, 'type' => $shop->catalog_type, 'logo' => Presenter::media($shop->logo)],
                'stories' => $items->map(fn (ShopStory $story) => $this->present($story))->values(),
            ];
        })->values()], 200, ['Cache-Control' => 'no-store']);
    }

    /** Counts one viewer per account; the owner looking at their own story isn't counted. */
    public function view(Request $request, ShopStory $story): JsonResponse
    {
        $story->loadMissing('shop:id,user_id,is_active');
        abort_unless($story->expires_at->isFuture() && $story->shop?->is_active, 404);
        $user = $request->user();
        if ($user->isSuperAdmin() || (int) $story->shop->user_id === (int) $user->id) {
            return response()->json(['recorded' => false]);
        }
        $now = now();
        ShopStoryView::query()->upsert([[
            'shop_story_id' => $story->id, 'user_id' => $user->id,
            'viewer_key' => hash('sha256', 'user:'.$user->id), 'viewer_name' => Str::limit((string) $user->name, 255, ''),
            'source' => 'app', 'viewed_at' => $now, 'last_viewed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]], ['shop_story_id', 'viewer_key'], ['user_id', 'viewer_name', 'source', 'last_viewed_at', 'updated_at']);

        return response()->json(['recorded' => true]);
    }

    /** The owner's live stories for one shop, with how many people watched each. */
    public function mine(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeOwner($request->user(), $shop);
        $stories = ShopStory::where('shop_id', $shop->id)->where('expires_at', '>', now())->withCount('views')->latest()->get();

        return response()->json(['data' => $stories->map(fn (ShopStory $story) => $this->present($story) + ['views' => (int) $story->views_count])]);
    }

    public function store(Request $request, Shop $shop, ShopStoryController $stories): JsonResponse
    {
        $this->authorizeOwner($request->user(), $shop);
        $request->merge(['shop_id' => $shop->id]);
        app()->call([$stories, 'store'], ['request' => $request]);
        $story = ShopStory::where('shop_id', $shop->id)->latest('id')->firstOrFail();

        return response()->json(['story' => $this->present($story) + ['views' => 0], 'message' => 'تم نشر الستوري لمدة 24 ساعة.'], 201);
    }

    public function destroy(Request $request, ShopStory $story, ShopStoryController $stories): JsonResponse
    {
        $this->authorizeOwner($request->user(), $story->shop()->firstOrFail());
        app()->call([$stories, 'destroy'], ['request' => $request, 'story' => $story]);

        return response()->json(['message' => 'تم حذف الستوري.']);
    }

    /** The website lets the shop's owner (or the admin) publish; staff accounts don't. */
    private function authorizeOwner(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || ($user->isShopOwner() && (int) $shop->user_id === (int) $user->id), 403);
    }

    private function present(ShopStory $story): array
    {
        return [
            'id' => $story->id, 'type' => $story->type, 'caption' => $story->caption,
            'src' => route('shop-stories.media', $story),
            'created_at' => $story->created_at?->toIso8601String(),
            'expires_at' => $story->expires_at->toIso8601String(),
        ];
    }
}
