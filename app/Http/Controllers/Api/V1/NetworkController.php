<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RaffleCardController;
use App\Http\Controllers\RewardWheelController;
use App\Models\{Agent, Distributor, DistributorMarketer, FrontOrder, Shop, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\{JsonResponse, Request};

/**
 * Agents, distributors and marketers in the app: their links and the shops and people under them,
 * the marketer's wheel, and the raffle card check that anyone can use.
 */
class NetworkController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = self::roleOf($user);
        abort_unless($role, 403, 'هاد الحساب مش وكيل ولا موزع ولا مسوّق.');
        $monthStart = now()->startOfMonth();

        $data = match ($role) {
            'marketer' => ['marketers' => DistributorMarketer::where('user_id', $user->id)->with('distributor.shop')->get()
                ->map(fn ($marketer) => $this->marketer($marketer, $monthStart, true))],
            'distributor' => ['distributors' => Distributor::where('user_id', $user->id)->with(['shop', 'marketers' => fn ($q) => $q->latest()])->get()
                ->map(fn ($distributor) => [
                    'id' => $distributor->id, 'name' => $distributor->name, 'company' => $distributor->shop?->name, 'is_active' => (bool) $distributor->is_active,
                    'store_url' => route('front.distributor', $distributor),
                    'shops' => $this->shops(Shop::where('distributor_id', $distributor->id)),
                    'marketers' => $distributor->marketers->map(fn ($marketer) => $this->marketer($marketer, $monthStart, false))->values(),
                ])],
            'agent' => ['agents' => Agent::where('user_id', $user->id)->with(['shop', 'distributors' => fn ($q) => $q->withCount(['marketers', 'linkedShops'])->latest()])->get()
                ->map(fn ($agent) => [
                    'id' => $agent->id, 'name' => $agent->name, 'company' => $agent->shop?->name,
                    'distributors' => $agent->distributors->map(fn ($distributor) => [
                        'id' => $distributor->id, 'name' => $distributor->name, 'phone' => $distributor->phone, 'address' => $distributor->address,
                        'is_active' => (bool) $distributor->is_active, 'marketers_count' => (int) $distributor->marketers_count,
                        'shops_count' => (int) $distributor->linked_shops_count, 'store_url' => route('front.distributor', $distributor),
                    ])->values(),
                ])],
        };

        return response()->json(['role' => $role] + $data, 200, ['Cache-Control' => 'no-store']);
    }

    /** Orders that came through the marketer (or the distributor's network), like the website's orders page. */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = self::roleOf($user);
        abort_unless(in_array($role, ['marketer', 'distributor'], true), 403);
        $data = $request->validate(['status' => ['nullable', 'string', 'max:30'], 'search' => ['nullable', 'string', 'max:100']]);
        $scope = function ($query) use ($user, $role) {
            if ($role === 'marketer') {
                return $query->whereIn('front_orders.distributor_marketer_id', $user->distributorMarketerProfiles()->where('is_active', true)->pluck('id'));
            }
            $ids = $user->distributorProfiles()->pluck('id');

            return $query->where(fn ($q) => $q->whereIn('front_orders.distributor_id', $ids)
                ->orWhereHas('distributorMarketer', fn ($m) => $m->whereIn('distributor_id', $ids)));
        };
        $search = trim((string) ($data['search'] ?? ''));
        $orders = $scope(FrontOrder::query())->with(['shop:id,name', 'distributorMarketer:id,name,commission_rate'])
            ->when(array_key_exists((string) ($data['status'] ?? ''), FrontOrder::statusOptions()), fn ($q) => $q->where('status', $data['status']))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhereHas('distributorMarketer', fn ($m) => $m->where('name', 'like', "%{$search}%"))))
            ->latest()->limit(100)->get();
        $commission = (float) $scope(FrontOrder::query())
            ->leftJoin('distributor_marketers', 'front_orders.distributor_marketer_id', '=', 'distributor_marketers.id')
            ->sum(DB::raw('COALESCE(front_orders.marketer_commission_amount, front_orders.total * distributor_marketers.commission_rate / 100, 0)'));

        return response()->json([
            'data' => $orders->map(fn (FrontOrder $order) => [
                'id' => $order->id, 'number' => $order->order_number, 'shop' => $order->shop?->name,
                'customer' => $order->customer_name, 'status' => $order->status, 'status_label' => $order->statusLabel(),
                'total' => Presenter::price($order->total), 'marketer' => $order->distributorMarketer?->name,
                'commission' => Presenter::price($order->marketer_commission_amount
                    ?? ($order->distributorMarketer?->commission_rate !== null ? (float) $order->total * (float) $order->distributorMarketer->commission_rate / 100 : null)),
                'created_at' => $order->created_at?->toIso8601String(),
            ]),
            'statuses' => collect(FrontOrder::statusOptions())->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'count' => $scope(FrontOrder::query())->count(),
            'commission_total' => round($commission, 2),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public static function roleOf(User $user): ?string
    {
        return match (true) {
            $user->isMarketer() => 'marketer',
            $user->isDistributor() => 'distributor',
            $user->isAgent() => 'agent',
            default => null,
        };
    }

    /** The marketer's in-person wheel (the same one the marketer link opens). */
    public function wheel(string $code, RewardWheelController $wheels): JsonResponse
    {
        $marketer = DistributorMarketer::where('tracking_code', $code)->with('distributor.shop')->firstOrFail();
        abort_unless($marketer->is_active && $marketer->distributor?->is_active, 404);
        $wheel = $wheels->marketerDirectWheel()->load('segments');
        abort_unless($wheel->is_active, 404, 'العجلة موقفة حالياً.');

        return response()->json([
            'title' => $wheel->title,
            'marketer' => ['name' => $marketer->name, 'company' => $marketer->distributor?->shop?->name],
            'segments' => $wheel->segments->where('is_active', true)->values()->map(fn ($segment) => [
                'label' => $segment->label, 'color' => $segment->color ?: '#08DEF4',
                'image' => $segment->gift_image ? asset($segment->gift_image) : null,
            ]),
        ]);
    }

    public function spin(Request $request, string $code, RewardWheelController $wheels): JsonResponse
    {
        $marketer = DistributorMarketer::where('tracking_code', $code)->firstOrFail();

        return $wheels->publicMarketerDirectSpin($request, $marketer);
    }

    /** Scratch-card check: winner, already used, or entered into the live draw (same as the website box). */
    public function raffle(Request $request, RaffleCardController $raffle): JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->isShopOwner()) {
            $request->merge(['customer' => array_filter([
                'name' => data_get($request->input('customer'), 'name') ?: $user->name,
                'phone' => data_get($request->input('customer'), 'phone') ?: $user->phone,
                'whatsapp' => data_get($request->input('customer'), 'whatsapp'),
                'address' => data_get($request->input('customer'), 'address'),
            ])]);
        }

        return $raffle->check($request);
    }

    private function marketer(DistributorMarketer $marketer, $monthStart, bool $withShops): array
    {
        return [
            'id' => $marketer->id, 'name' => $marketer->name, 'phone' => $marketer->phone, 'is_active' => (bool) $marketer->is_active,
            'code' => $marketer->tracking_code, 'commission_rate' => $marketer->commission_rate !== null ? (float) $marketer->commission_rate : null,
            'distributor' => $marketer->distributor?->name, 'company' => $marketer->distributor?->shop?->name,
            'store_url' => route('front.marketer', ['marketer' => $marketer->tracking_code]),
            'wheel_url' => route('front.marketer.direct-wheel', ['marketer' => $marketer->tracking_code]),
            'shops_count' => $marketer->shops()->count(),
            'orders_month' => $marketer->frontOrders()->where('created_at', '>=', $monthStart)->where('status', '!=', 'cancelled')->count(),
            'sales_month' => Presenter::price($marketer->frontOrders()->where('created_at', '>=', $monthStart)->where('status', '!=', 'cancelled')->sum('total')) ?? 0,
        ] + ($withShops ? ['shops' => $this->shops($marketer->shops())] : []);
    }

    private function shops($query): array
    {
        return $query->latest()->limit(100)->get(['id', 'name', 'city', 'catalog_type', 'logo', 'is_active', 'created_at'])
            ->map(fn ($shop) => ['id' => $shop->id, 'name' => $shop->name, 'city' => $shop->city, 'type' => $shop->catalog_type,
                'logo' => Presenter::media($shop->logo), 'is_active' => (bool) $shop->is_active, 'joined' => $shop->created_at?->toDateString()])->all();
    }
}
