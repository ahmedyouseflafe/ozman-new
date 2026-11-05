<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Models\MarketOrder;
use App\Services\MarketImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Listings publish at once; the admin watches them here and can stop or delete any of them. */
class MarketAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->admin($request);
        $status = in_array($request->query('status'), ['active', 'hidden', 'sold', 'removed'], true) ? $request->query('status') : null;
        $q = trim((string) $request->query('q', ''));
        $listings = MarketListing::with(['images', 'seller:id,name,username,email,phone'])->withCount('orders')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhereHas('seller', fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))))
            ->latest('id')->paginate(30)->withQueryString();
        $stats = [
            'active' => MarketListing::where('status', 'active')->count(),
            'sellers' => MarketListing::distinct('user_id')->count('user_id'),
            'open_orders' => MarketOrder::whereIn('status', ['pending', 'accepted'])->count(),
            'completed' => MarketOrder::where('status', 'completed')->count(),
        ];

        return response()->view('market.admin', compact('listings', 'stats', 'status', 'q'))->header('Cache-Control', 'private, no-store');
    }

    public function moderate(Request $request, MarketListing $listing)
    {
        $this->admin($request);
        $remove = $request->validate(['action' => ['required', 'in:remove,restore']])['action'] === 'remove';
        $listing->update(['status' => $remove ? MarketListing::REMOVED : ($listing->quantity > 0 ? MarketListing::ACTIVE : MarketListing::SOLD)]);

        return back()->with('status', $remove ? __('تم إيقاف المنتج وإخفاؤه من المتجر.') : __('رجع المنتج للمتجر.'));
    }

    public function destroy(Request $request, MarketListing $listing, MarketImage $images)
    {
        $this->admin($request);
        $paths = $listing->images()->pluck('path')->all();
        DB::transaction(function () use ($listing) {
            $listing->orders()->where('status', 'pending')->update(['status' => 'cancelled']);
            $listing->delete();
        });
        $images->delete($paths);

        return back()->with('status', __('تم حذف المنتج نهائياً.'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->isSuperAdmin(), 403);
    }
}
