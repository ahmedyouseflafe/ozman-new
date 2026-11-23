<?php

namespace App\View\Composers;

use App\Models\{Agent, MarketOrder, PromoterProfile, RewardWheel, Shop, ShopReview};
use Illuminate\View\View;

/**
 * Small pieces of data some views need from the database, prepared here instead of inside the views.
 * Each method is registered for its views in AppServiceProvider; each does exactly what the view used to do inline.
 */
class PageDataComposers
{
    /** Admin overview: how many promoter sign-ups wait for the super admin. */
    public function adminDashboard(View $view): void
    {
        $view->with('pendingPromoterCount', auth()->user()?->isSuperAdmin() ? PromoterProfile::pending()->count() : 0);
    }

    /** Community layout: the seller's waiting market orders and (super admin) waiting promoters. */
    public function communityLayout(View $view): void
    {
        $me = auth()->user();
        $view->with([
            'pendingSales' => $me?->earnsPoints() ? MarketOrder::where('seller_id', $me->id)->where('status', 'pending')->count() : 0,
            'pendingPromoters' => $me?->isSuperAdmin() ? PromoterProfile::pending()->count() : 0,
        ]);
    }

    /** Free-market member tabs: the badge on "طلبات وصلتني". */
    public function marketNav(View $view): void
    {
        $me = auth()->user();
        $view->with('pendingSales', $me?->earnsPoints() ? MarketOrder::where('seller_id', $me->id)->where('status', 'pending')->count() : 0);
    }

    /** Products list and product page: the agent records of the signed-in agent. */
    public function agentIds(View $view): void
    {
        $user = auth()->user();
        $view->with('currentUserAgentIds', $user?->isAgent() ? \App\Support\AgentAccess::idsFor($user) : []);
    }

    /** Storefront share button: the shop's star rating. */
    public function shopRating(View $view): void
    {
        $shop = $view->getData()['shop'] ?? null;
        $view->with('shopRating', $shop instanceof Shop ? ShopReview::summaryFor($shop->id) : ['count' => 0, 'average' => 0]);
    }

    /** Restaurant menu: its active purchase-amount wheels (with at least two active segments). */
    public function purchaseWheels(View $view): void
    {
        $shop = $view->getData()['shop'] ?? null;
        $view->with('purchaseWheels', $shop instanceof Shop
            ? RewardWheel::where('shop_id', $shop->id)->where('wheel_type', 'purchase_amount')->where('is_active', true)
                ->whereHas('segments', fn ($q) => $q->where('is_active', true), '>=', 2)
                ->with(['segments' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->orderBy('min_order_total')->get()
            : collect());
    }

    /** Restaurant menu: does the shop have a sign-up wheel (shows the customer welcome). */
    public function restaurantMenu(View $view): void
    {
        $shop = $view->getData()['shop'] ?? null;
        $view->with('hasSignupWheel', $shop instanceof Shop && RewardWheel::where('key', 'shop_signup_'.$shop->id)->exists());
    }

    /** Purchase-wheel settings: the shop's sign-up wheel, to copy from. */
    public function purchaseWheelSettings(View $view): void
    {
        $shopId = $view->getData()['selectedShopId'] ?? null;
        $view->with('signupSource', RewardWheel::where('shop_id', $shopId)->where('key', 'shop_signup_'.$shopId)
            ->with(['segments' => fn ($q) => $q->orderBy('sort_order')])->first());
    }
}
