<?php

namespace App\Http\Controllers;

use App\Models\{Shop, ShopReview};
use Illuminate\View\View;

/** A shop's reviews on the website. Customers write them in the app after their order arrives. */
class ShopReviewPageController extends Controller
{
    public function show(Shop $shop): View
    {
        abort_unless($shop->is_active, 404);

        return view('front.shop_reviews', [
            'shop' => $shop,
            'summary' => ShopReview::summaryFor($shop->id),
            'stars' => ShopReview::starsFor($shop->id),
            'reviews' => ShopReview::where('shop_id', $shop->id)->where('is_visible', true)->with('user:id,name')
                ->latest()->orderByDesc('id')->simplePaginate(20),
        ]);
    }
}
