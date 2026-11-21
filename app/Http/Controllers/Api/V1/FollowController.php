<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Shop, ShopFollow};
use App\Services\ShopFollows;
use Illuminate\Http\{JsonResponse, Request};

/** Following shops: the button on a shop page, and the list of followed shops. */
class FollowController extends Controller
{
    public function __construct(private ShopFollows $follows) {}

    public function show(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active, 404);

        return response()->json(['following' => $this->follows->follows($request->user(), $shop), 'followers' => $this->follows->count($shop)]);
    }

    public function update(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active, 404);
        $follow = (bool) $request->validate(['follow' => ['required', 'boolean']])['follow'];
        $this->follows->set($request->user(), $shop, $follow);

        return response()->json([
            'following' => $follow,
            'followers' => $this->follows->count($shop),
            'message' => $follow ? __('رح يوصلك إشعار لما :shop ينزّل عرض جديد.', ['shop' => $shop->name]) : __('ما رح توصلك عروض :shop بعد هيك.', ['shop' => $shop->name]),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $shops = Shop::where('is_active', true)->withRating()
            ->whereIn('id', ShopFollow::where('user_id', $request->user()->id)->select('shop_id'))->orderBy('name')->get();

        return response()->json(['data' => $shops->map(fn (Shop $shop) => Presenter::shop($shop))]);
    }
}
