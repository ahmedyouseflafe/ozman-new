<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Shop, VisitorRegistration};
use Illuminate\Http\{JsonResponse, Request};

/** The customers who registered with the shop (through its page or signup wheel), like the dashboard's customers page. */
class MerchantCustomersController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        // The shop's own owner always sees its customers; staff need the dashboard's customers permission.
        abort_unless($user->isSuperAdmin() || (int) $shop->user_id === (int) $user->id || $user->canAccessRouteName('restaurant.customers.index'), 403);
        $search = trim((string) ($request->validate(['search' => ['nullable', 'string', 'max:120']])['search'] ?? ''));

        $query = VisitorRegistration::query()->where('shop_id', $shop->id)->where('type', 'customer');
        $page = (clone $query)->when($search !== '', function ($query) use ($search) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('residence_address', 'like', "%{$search}%"));
        })->latest()->orderByDesc('id')->paginate(30);

        return response()->json([
            'total' => (clone $query)->count(),
            'data' => collect($page->items())->map(fn (VisitorRegistration $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->residence_address,
                'map_link' => $c->map_link ?: ($c->latitude && $c->longitude ? "https://maps.google.com/?q={$c->latitude},{$c->longitude}" : null),
                'created_at' => $c->created_at?->toIso8601String(),
            ])->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ], 200, ['Cache-Control' => 'no-store']);
    }
}
