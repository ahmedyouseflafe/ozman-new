<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Models\MarketOrder;
use App\Services\FreeMarket;
use Illuminate\Http\Request;

/** Orders in "المتجر الحر": a member orders, the seller accepts / rejects / completes. */
class MarketOrderController extends Controller
{
    public function store(Request $request, MarketListing $listing, FreeMarket $market)
    {
        abort_unless($request->user(), 403);
        $order = $market->placeOrder($request->user(), $listing, $request->all());

        return redirect()->route('market.purchases')->with('status', __('وصل طلبك للبائع ✅ رقم الطلب :number. بيوصلك إيميل لما يرد.', ['number' => $order->order_number]));
    }

    public function sales(Request $request)
    {
        $this->member($request);
        $orders = MarketOrder::where('seller_id', $request->user()->id)->with(['buyer:id,name,username', 'listing.images'])
            ->orderByRaw("case status when 'pending' then 0 when 'accepted' then 1 else 2 end")->latest('id')->paginate(20);

        return response()->view('market.sales', compact('orders'))->header('Cache-Control', 'private, no-store');
    }

    public function purchases(Request $request)
    {
        $this->member($request);
        $orders = MarketOrder::where('buyer_id', $request->user()->id)->with(['seller:id,name,username,phone', 'listing.images'])->latest('id')->paginate(20);

        return response()->view('market.purchases', compact('orders'))->header('Cache-Control', 'private, no-store');
    }

    /** Seller: accept, reject, complete or cancel an order. */
    public function update(Request $request, MarketOrder $order, FreeMarket $market)
    {
        abort_unless($request->user(), 403);
        $action = $request->validate(['action' => ['required', 'in:accept,reject,complete,cancel']])['action'];
        $order = $market->sellerAction($request->user(), $order, $action);

        return back()->with('status', __('تم تحديث الطلب :number: :status.', ['number' => $order->order_number, 'status' => $order->statusLabel()]));
    }

    /** Buyer: cancel while the seller hasn't answered yet. */
    public function cancel(Request $request, MarketOrder $order, FreeMarket $market)
    {
        abort_unless($request->user(), 403);
        $market->buyerCancel($request->user(), $order);

        return back()->with('status', __('تم إلغاء الطلب.'));
    }

    private function member(Request $request): void
    {
        if (! ($request->user()?->is_active && $request->user()->earnsPoints())) {
            // Admins and staff browse the market but don't buy or sell in it.
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('market.index')->with('status', __('البيع والطلب بالمتجر الحر لحسابات العملاء والمروّجين.')));
        }
    }
}
