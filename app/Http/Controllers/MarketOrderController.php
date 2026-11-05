<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Models\MarketOrder;
use App\Models\User;
use App\Notifications\MarketOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Orders in "المتجر الحر": a member orders, the seller accepts / rejects / completes. */
class MarketOrderController extends Controller
{
    public function store(Request $request, MarketListing $listing)
    {
        $buyer = $request->user();
        abort_unless($buyer?->is_active && $buyer->earnsPoints(), 403, __('الطلب من المتجر الحر لحسابات العملاء والمروّجين.'));
        abort_unless($listing->isVisible(), 404);
        abort_if((int) $listing->user_id === (int) $buyer->id, 403, __('ما بتقدر تطلب منتجك.'));
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'city' => ['required', 'string', 'min:2', 'max:60'],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['buyer_phone.regex' => __('رقم الجوال لازم يكون أرقام بس.')]);
        if ((int) $data['quantity'] > $listing->quantity) {
            return back()->withInput()->withErrors(['quantity' => __('الكمية المتوفرة :count بس.', ['count' => $listing->quantity])]);
        }

        // Price is always the listing's current price, never a number from the form.
        $order = MarketOrder::create([
            ...$data,
            'order_number' => 'M'.now()->format('ymd').strtoupper(Str::random(5)),
            'market_listing_id' => $listing->id, 'seller_id' => $listing->user_id, 'buyer_id' => $buyer->id,
            'listing_title' => $listing->title, 'unit_price' => $listing->price,
            'total' => round((float) $listing->price * (int) $data['quantity'], 2), 'status' => MarketOrder::PENDING,
        ]);
        if (! $buyer->phone) {
            $buyer->forceFill(['phone' => $data['buyer_phone']])->save();
        }
        $this->notify($listing->seller, $order, 'new');

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
    public function update(Request $request, MarketOrder $order)
    {
        abort_unless($request->user()?->is_active && (int) $order->seller_id === (int) $request->user()->id, 403);
        $action = $request->validate(['action' => ['required', 'in:accept,reject,complete,cancel']])['action'];
        $allowed = ['accept' => [MarketOrder::PENDING], 'reject' => [MarketOrder::PENDING], 'complete' => [MarketOrder::ACCEPTED], 'cancel' => [MarketOrder::ACCEPTED]];

        $error = DB::transaction(function () use ($order, $action, $allowed) {
            $order = MarketOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, $allowed[$action], true)) {
                return __('حالة الطلب تغيّرت. حدّث الصفحة.');
            }
            $listing = $order->market_listing_id ? MarketListing::query()->lockForUpdate()->find($order->market_listing_id) : null;
            if ($action === 'accept') {
                // Stock is taken when the seller accepts, so two buyers can't get the last piece.
                if (! $listing || $listing->quantity < $order->quantity) {
                    return __('الكمية المتوفرة ما بتكفي لهالطلب. عدّل الكمية بالمنتج أو ارفض الطلب.');
                }
                $left = $listing->quantity - $order->quantity;
                $listing->update(['quantity' => $left, 'status' => $left === 0 && $listing->status === MarketListing::ACTIVE ? MarketListing::SOLD : $listing->status]);
                $order->update(['status' => MarketOrder::ACCEPTED, 'stock_taken' => true]);
            } elseif ($action === 'cancel') {
                if ($order->stock_taken && $listing) {
                    $listing->update(['quantity' => min(999, $listing->quantity + $order->quantity),
                        'status' => $listing->status === MarketListing::SOLD ? MarketListing::ACTIVE : $listing->status]);
                }
                $order->update(['status' => MarketOrder::CANCELLED, 'stock_taken' => false]);
            } else {
                $order->update(['status' => $action === 'reject' ? MarketOrder::REJECTED : MarketOrder::COMPLETED]);
            }

            return null;
        });
        if ($error) {
            return back()->withErrors(['order' => $error]);
        }
        $order->refresh();
        $this->notify($order->buyer, $order, $order->status);

        return back()->with('status', __('تم تحديث الطلب :number: :status.', ['number' => $order->order_number, 'status' => $order->statusLabel()]));
    }

    /** Buyer: cancel while the seller hasn't answered yet. */
    public function cancel(Request $request, MarketOrder $order)
    {
        abort_unless($request->user()?->is_active && (int) $order->buyer_id === (int) $request->user()->id, 403);
        $changed = MarketOrder::query()->whereKey($order->id)->where('status', MarketOrder::PENDING)->update(['status' => MarketOrder::CANCELLED]);
        if (! $changed) {
            return back()->withErrors(['order' => __('البائع رد على الطلب. تواصل معه لإلغائه.')]);
        }
        $order->refresh();
        $this->notify($order->seller, $order, 'buyer_cancelled');

        return back()->with('status', __('تم إلغاء الطلب.'));
    }

    private function notify(?User $user, MarketOrder $order, string $event): void
    {
        if (! $user?->is_active) {
            return;
        }
        try {
            $user->notify(new MarketOrderUpdate($order, $event));
        } catch (\Throwable $e) {
            // The order is saved either way; a mail outage must not undo it.
            report($e);
        }
    }

    private function member(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->earnsPoints(), 403, __('المتجر الحر لحسابات العملاء والمروّجين.'));
    }
}
