<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLike;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductLikeController extends Controller
{
    public const COOKIE = 'ozman_like_visitor';

    /** Counts for the products on the page, and which of them this visitor already likes. */
    public function state(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->take(300)->values();
        if ($ids->isEmpty()) {
            return response()->json(['counts' => (object) [], 'liked' => []]);
        }

        $counts = Product::query()->whereIn('id', $ids)->pluck('likes_count', 'id');
        $liked = $this->scope(ProductLike::query()->whereIn('product_id', $counts->keys()), $request)?->pluck('product_id') ?? collect();

        return response()->json(['counts' => (object) $counts->all(), 'liked' => $liked->values()]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->shop?->is_active, 404);
        $visitor = $request->user() ? null : $this->visitorToken($request);
        $owner = $request->user()
            ? ['user_id' => $request->user()->id]
            : ['visitor_hash' => hash('sha256', $visitor)];

        $liked = DB::transaction(function () use ($product, $owner) {
            $existing = ProductLike::query()->where('product_id', $product->id)->where($owner)->lockForUpdate()->first();
            if ($existing) {
                $existing->delete();
                Product::whereKey($product->id)->where('likes_count', '>', 0)->decrement('likes_count');

                return false;
            }
            try {
                ProductLike::create(['product_id' => $product->id, ...$owner]);
            } catch (QueryException) {
                return true; // A double tap raced us; it is already liked.
            }
            Product::whereKey($product->id)->increment('likes_count');

            return true;
        });

        $response = response()->json(['liked' => $liked, 'count' => (int) Product::whereKey($product->id)->value('likes_count')]);
        if ($visitor) {
            $response->withCookie(Cookie::make(self::COOKIE, $visitor, 60 * 24 * 730, null, null, null, true, false, 'lax'));
        }

        return $response;
    }

    private function scope($query, Request $request)
    {
        if ($user = $request->user()) {
            return $query->where('user_id', $user->id);
        }
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || strlen($token) !== 40) {
            return null;
        }

        return $query->where('visitor_hash', hash('sha256', $token));
    }

    private function visitorToken(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && strlen($token) === 40 ? $token : Str::random(40);
    }
}
