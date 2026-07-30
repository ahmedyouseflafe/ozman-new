<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\DistributorMarketer;
use App\Models\FrontOrder;
use App\Models\RewardWheel;
use App\Models\RewardWheelSegment;
use App\Models\Shop;
use App\Models\VisitorRegistration;
use App\Rules\ValidPhoneNumber;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FrontOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $authenticatedShop = $request->user()?->isShopOwner()
            ? $request->user()->shops()
                ->whereKey((int) $request->session()->get('merchant_shop_id'))
                ->where('is_active', true)
                ->with(['distributor', 'distributorMarketer.distributor'])
                ->first()
            : null;

        if (! $authenticatedShop && $request->user()?->isShopOwner()) {
            $authenticatedShop = $request->user()->shops()
                ->where('is_active', true)
                ->with(['distributor', 'distributorMarketer.distributor'])
                ->first();
        }

        $validated = $request->validate([
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'distributor_marketer_id' => ['nullable', 'integer', 'exists:distributor_marketers,id'],
            'marketing_source' => ['nullable', 'string', 'max:40'],
            'reward_wheel_id' => ['nullable', 'integer', 'exists:reward_wheels,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:60', new ValidPhoneNumber()],
            'customer_whatsapp' => ['nullable', 'string', 'max:60', new ValidPhoneNumber()],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_link' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'string', 'max:120'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.img' => ['nullable', 'string', 'max:500'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'order_channel' => ['required', 'in:whatsapp,instant_payment'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'visitor_type' => ['required', 'in:customer,merchant'],
            'merchant_registration_token' => ['nullable', 'string', 'size:64'],
        ]);

        if ($authenticatedShop) {
            $linkedDistributor = $authenticatedShop->distributor
                ?: $authenticatedShop->distributorMarketer?->distributor;

            abort_unless($linkedDistributor?->is_active, 403, 'حساب المتجر غير مرتبط بموزع فعال.');

            $validated['shop_id'] = $authenticatedShop->id;
            $validated['distributor_id'] = $linkedDistributor->id;
            $validated['distributor_marketer_id'] = $authenticatedShop->distributor_marketer_id;
            $validated['marketing_source'] = $authenticatedShop->distributor_marketer_id
                ? 'marketer'
                : 'merchant_account';
            $validated['reward_wheel_id'] = null;
        } elseif ($validated['visitor_type'] === 'merchant') {
            abort_unless(filled($validated['merchant_registration_token'] ?? null), 403, 'يجب تسجيل الدخول بحساب المتجر أو اعتماد طلب التسجيل.');

            $approvedMerchant = VisitorRegistration::query()
                ->where('type', 'merchant')
                ->where('status', 'approved')
                ->where('public_token', $validated['merchant_registration_token'])
                ->exists();

            abort_unless($approvedMerchant, 403, 'طلب صاحب المتجر ما زال قيد المراجعة. تواصل معنا عبر واتساب لإكمال الاعتماد.');
            $validated['reward_wheel_id'] = null;
        }

        unset($validated['visitor_type'], $validated['merchant_registration_token']);

        if (! empty($validated['reward_wheel_id'])) {
            $wheel = RewardWheel::query()
                ->whereKey($validated['reward_wheel_id'])
                ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
                ->where('shop_id', $validated['shop_id'] ?? 0)
                ->where('is_active', true)
                ->first();
            $orderTotal = (float) ($validated['total'] ?? 0);
            $wheelMatchesTotal = $wheel
                && $orderTotal >= (float) $wheel->min_order_total
                && ($wheel->max_order_total === null || $orderTotal <= (float) $wheel->max_order_total);

            abort_unless($wheelMatchesTotal, 422, 'عجلة الشراء لا تخص هذا المتجر أو لا تناسب قيمة الطلب.');
        }

        $marketer = null;

        if ($authenticatedShop) {
            $marketer = $authenticatedShop->distributorMarketer?->is_active
                ? $authenticatedShop->distributorMarketer
                : null;
        } elseif (! empty($validated['distributor_marketer_id'])) {
            $marketer = DistributorMarketer::query()
                ->whereKey($validated['distributor_marketer_id'])
                ->where('is_active', true)
                ->with('distributor')
                ->firstOrFail();

            $validated['distributor_id'] = $marketer->distributor_id;
            $validated['marketing_source'] = 'marketer';
        } elseif (! empty($validated['shop_id'])) {
            $shopMarketer = Shop::query()
                ->whereKey($validated['shop_id'])
                ->with('distributorMarketer.distributor')
                ->first()
                ?->distributorMarketer;

            if ($shopMarketer?->is_active && $shopMarketer->distributor?->is_active) {
                $marketer = $shopMarketer;
                $validated['distributor_marketer_id'] = $marketer->id;
                $validated['distributor_id'] = $marketer->distributor_id;
                $validated['marketing_source'] = 'marketer';
            }
        }

        $total = (float) ($validated['total'] ?? 0);
        $commissionRate = $marketer ? (float) $marketer->commission_rate : null;
        $commissionAmount = $commissionRate !== null
            ? round($total * max($commissionRate, 0) / 100, 2)
            : null;

        $order = FrontOrder::create([
            ...$validated,
            'order_number' => $this->makeOrderNumber(),
            'subtotal' => $validated['subtotal'] ?? 0,
            'discount' => $validated['discount'] ?? 0,
            'total' => $total,
            'marketer_commission_rate' => $commissionRate,
            'marketer_commission_amount' => $commissionAmount,
            'payment_status' => $validated['order_channel'] === 'instant_payment'
                ? 'instant_payment_submitted'
                : 'whatsapp_order',
        ]);

        $this->notify(
            'front_order_created',
            'طلب جديد من الواجهة',
            sprintf(
                '%s - %s - %s',
                $order->customer_name,
                number_format((float) $order->total, 2) . ' شيكل',
                $order->channelLabel()
            ),
            $order
        );

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_qr_url' => route('front-orders.qr', $order),
            'order_lookup_url' => route('front-orders.index', ['search' => $order->order_number]),
        ]);
    }

    public function qr(FrontOrder $order): Response
    {
        $qrCodeSvg = (new Writer(new ImageRenderer(
            new RendererStyle(320, 2),
            new SvgImageBackEnd()
        )))->writeString(route('front-orders.index', ['search' => $order->order_number]));

        return response($qrCodeSvg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function reward(Request $request, FrontOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'reward_label' => ['required', 'string', 'max:255'],
            'reward_discount_value' => ['nullable', 'numeric', 'min:0'],
            'reward_discount_type' => ['nullable', 'string', 'max:30'],
            'reward_gift_image' => ['nullable', 'string', 'max:500'],
            'reward_color' => ['nullable', 'string', 'max:30'],
            'reward_won_at' => ['nullable', 'date'],
        ]);

        $order->update([
            'reward_label' => $validated['reward_label'],
            'reward_discount_value' => $validated['reward_discount_value'] ?? null,
            'reward_discount_type' => $validated['reward_discount_type'] ?? null,
            'reward_gift_image' => $validated['reward_gift_image'] ?? null,
            'reward_color' => $validated['reward_color'] ?? null,
            'reward_won_at' => $validated['reward_won_at'] ?? now(),
        ]);

        $this->notify(
            'front_order_reward_won',
            'هدية عجلة الشراء',
            sprintf('%s ربح: %s', $order->order_number, $order->reward_label),
            $order
        );

        return response()->json(['ok' => true]);
    }

    public function status(Request $request, FrontOrder $order)
    {
        abort_if(filled($order->order_type), 422, 'طلبات المطعم تُدار من لوحة المطعم فقط.');
        abort_if($request->user()?->isMarketer(), 403);
        abort_unless($request->user()?->isShopOwner() || $this->canAccessCurrentRoute(), 403);
        abort_unless($this->canAccessOrder($request, $order), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(FrontOrder::statusOptions()))],
        ]);

        $order->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $order->status,
                'label' => $order->statusLabel(),
                'class' => $order->statusClass(),
            ]);
        }

        return back()->with('status', 'تم تحديث حالة الطلب بنجاح.');
    }

    public function spinReward(FrontOrder $order): JsonResponse
    {
        if ($order->reward_label) {
            return response()->json([
                'ok' => true,
                'reward' => $this->orderRewardPayload($order),
                'segment_index' => $this->segmentIndexForOrderReward($order),
            ]);
        }

        $wheel = RewardWheel::query()
            ->whereKey($order->reward_wheel_id)
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('shop_id', $order->shop_id)
            ->where('is_active', true)
            ->with(['segments' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
            ])
            ->first();

        $orderTotal = (float) $order->total;
        $wheelMatchesTotal = $wheel
            && $orderTotal >= (float) $wheel->min_order_total
            && ($wheel->max_order_total === null || $orderTotal <= (float) $wheel->max_order_total);

        if (! $wheelMatchesTotal || $wheel->segments->count() < 2) {
            return response()->json(['message' => 'لا توجد عجلة مناسبة لهذا الطلب.'], 422);
        }

        $segment = $this->nextSegmentForWheel($wheel);

        $order->update([
            'reward_label' => $segment->label,
            'reward_discount_value' => $segment->discount_value,
            'reward_discount_type' => $segment->discount_type,
            'reward_gift_image' => $segment->discount_type === 'gift' && $segment->gift_image ? asset($segment->gift_image) : null,
            'reward_color' => $segment->color,
            'reward_won_at' => now(),
        ]);

        $this->notify(
            'front_order_reward_won',
            'هدية عجلة الشراء',
            sprintf('%s ربح: %s', $order->order_number, $order->reward_label),
            $order
        );

        $segmentIndex = $wheel->segments->search(fn($item) => $item->id === $segment->id);

        return response()->json([
            'ok' => true,
            'reward' => $this->orderRewardPayload($order),
            'segment_index' => $segmentIndex === false ? 0 : (int) $segmentIndex,
        ]);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isShopOwner() || $this->canAccessCurrentRoute(), 403);

        $channel = $request->query('channel');
        $orderStatus = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $accessibleShopIds = (!$user?->isSuperAdmin() && !$user?->isMarketer() && !$user?->isDistributor())
            ? $user?->accessibleShopIds() ?? []
            : [];

        if ($user?->isShopOwner()) {
            $currentShopId = (int) $request->session()->get('current_shop_id', 0);
            $restaurant = Shop::query()
                ->whereIn('id', $accessibleShopIds)
                ->where('catalog_type', 'restaurant')
                ->when($currentShopId > 0, fn($query) => $query->whereKey($currentShopId))
                ->first();
            if ($restaurant) {
                return redirect()->route('restaurant.dashboard', $restaurant);
            }
        }
        $marketerIds = $user?->isMarketer()
            ? $user->distributorMarketerProfiles()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all()
            : [];
        $distributorIds = $user?->isDistributor()
            ? $this->currentDistributorIds($request)
            : [];

        $ordersQuery = FrontOrder::query()
            ->with(['shop', 'rewardWheel', 'distributor', 'distributorMarketer'])
            ->when($user?->isMarketer(), fn($query) => $query->whereIn('front_orders.distributor_marketer_id', $marketerIds))
            ->when($user?->isDistributor(), fn($query) => $this->scopeToDistributorOrders($query, $distributorIds))
            ->when(!$user?->isSuperAdmin() && !$user?->isMarketer() && !$user?->isDistributor(),
                fn($query) => $query->whereIn('front_orders.shop_id', $accessibleShopIds))
            ->when(in_array($channel, ['whatsapp', 'instant_payment'], true), fn($query) => $query->where('order_channel', $channel))
            ->when(array_key_exists((string) $orderStatus, FrontOrder::statusOptions()), fn($query) => $query->where('status', $orderStatus))
            ->when($search !== '', function ($query) use ($search, $user) {
                $query->where(function ($query) use ($search, $user) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_whatsapp', 'like', "%{$search}%");

                    if (! $user?->isMarketer()) {
                        $query->orWhere('reward_label', 'like', "%{$search}%");
                    }

                    $query->orWhereHas('distributorMarketer', fn($marketerQuery) => $marketerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('distributor', fn($distributorQuery) => $distributorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        $statsQuery = FrontOrder::query()
            ->when($user?->isMarketer(), fn($query) => $query->whereIn('front_orders.distributor_marketer_id', $marketerIds))
            ->when($user?->isDistributor(), fn($query) => $this->scopeToDistributorOrders($query, $distributorIds));
        if (!$user?->isSuperAdmin() && !$user?->isMarketer() && !$user?->isDistributor()) {
            $statsQuery->whereIn('front_orders.shop_id', $accessibleShopIds);
        }
        $distributorProfiles = $user?->isDistributor()
            ? $user->distributorProfiles()
                ->with('shop')
                ->whereIn('id', $distributorIds)
                ->get()
            : collect();
        $commissionStatsQuery = (clone $statsQuery)
            ->leftJoin('distributor_marketers', 'front_orders.distributor_marketer_id', '=', 'distributor_marketers.id');
        $marketerCommissionTotal = (float) $commissionStatsQuery->sum(DB::raw(
            'COALESCE(front_orders.marketer_commission_amount, front_orders.total * distributor_marketers.commission_rate / 100, 0)'
        ));

        return view('admin.front_orders.index', [
            'orders' => $ordersQuery->paginate(25)->withQueryString(),
            'totalCount' => (clone $statsQuery)->count(),
            'instantCount' => (clone $statsQuery)->where('order_channel', 'instant_payment')->count(),
            'whatsappCount' => (clone $statsQuery)->where('order_channel', 'whatsapp')->count(),
            'rewardedCount' => (clone $statsQuery)->whereNotNull('reward_label')->count(),
            'marketerCount' => (clone $statsQuery)->whereNotNull('distributor_marketer_id')->count(),
            'marketerCommissionTotal' => $marketerCommissionTotal,
            'selectedChannel' => $channel,
            'selectedStatus' => $orderStatus,
            'statusOptions' => FrontOrder::statusOptions(),
            'search' => $search,
            'distributorProfiles' => $distributorProfiles,
        ]);
    }

    private function makeOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (FrontOrder::where('order_number', $number)->exists());

        return $number;
    }

    private function currentDistributorIds(Request $request): array
    {
        $user = $request->user();
        if (! $user?->isDistributor()) {
            return [];
        }

        $ids = $user->distributorProfiles()->pluck('id');

        if ($user->email) {
            $ids = $ids->merge($user->distributorProfiles()->getModel()::query()
                ->where('email', $user->email)
                ->pluck('id'));
        }

        return $ids->map(fn($id) => (int) $id)->unique()->values()->all();
    }

    private function scopeToDistributorOrders($query, array $distributorIds)
    {
        if ($distributorIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($query) use ($distributorIds) {
            $query->whereIn('front_orders.distributor_id', $distributorIds)
                ->orWhereHas('distributorMarketer', fn($marketerQuery) => $marketerQuery->whereIn('distributor_id', $distributorIds));
        });
    }

    private function canAccessOrder(Request $request, FrontOrder $order): bool
    {
        $user = $request->user();

        if ($user?->isMarketer()) {
            $marketerIds = $user->distributorMarketerProfiles()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            return in_array((int) $order->distributor_marketer_id, $marketerIds, true);
        }

        if ($user?->isDistributor()) {
            $distributorIds = $this->currentDistributorIds($request);
            if (in_array((int) $order->distributor_id, $distributorIds, true)) {
                return true;
            }

            $order->loadMissing('distributorMarketer');

            return $order->distributorMarketer
                && in_array((int) $order->distributorMarketer->distributor_id, $distributorIds, true);
        }

        if (!$user?->isSuperAdmin()) {
            return in_array((int) $order->shop_id, $user?->accessibleShopIds() ?? [], true);
        }

        return true;
    }

    private function nextSegmentForWheel(RewardWheel $wheel): RewardWheelSegment
    {
        return DB::transaction(function () use ($wheel) {
            $lockedWheel = RewardWheel::query()
                ->whereKey($wheel->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWheel->load(['segments' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
            ]);

            $segments = $lockedWheel->segments->values();
            $cycle = collect($lockedWheel->spin_cycle ?? [])
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $segments->contains('id', $id))
                ->values()
                ->all();

            if ($cycle === []) {
                $cycle = $this->buildSpinCycle($segments, (int) ($lockedWheel->win_quota_total ?: 200));
            }

            $cycleIndex = random_int(0, count($cycle) - 1);
            $segmentId = (int) $cycle[$cycleIndex];
            array_splice($cycle, $cycleIndex, 1);

            $lockedWheel->forceFill(['spin_cycle' => $cycle])->save();

            return $segments->firstWhere('id', $segmentId) ?? $segments->first();
        });
    }

    private function buildSpinCycle($segments, int $quotaTotal = 200): array
    {
        $cycle = [];
        $quotaTotal = max(1, $quotaTotal);

        foreach ($segments as $segment) {
            $quota = max(0, min($quotaTotal, (int) ($segment->win_quota ?? 1)));

            for ($index = 0; $index < $quota; $index++) {
                $cycle[] = (int) $segment->id;
            }
        }

        if ($cycle === []) {
            $cycle = $segments
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        shuffle($cycle);

        return $cycle;
    }

    private function orderRewardPayload(FrontOrder $order): array
    {
        return [
            'label' => $order->reward_label,
            'discount_value' => $order->reward_discount_value,
            'discount_type' => $order->reward_discount_type,
            'gift_image' => $order->reward_gift_image,
            'color' => $order->reward_color ?: '#00e5ff',
            'won_at' => optional($order->reward_won_at)->toIso8601String(),
        ];
    }

    private function segmentIndexForOrderReward(FrontOrder $order): int
    {
        if (! $order->reward_wheel_id || ! $order->reward_label) {
            return 0;
        }

        $segments = RewardWheelSegment::query()
            ->where('reward_wheel_id', $order->reward_wheel_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->values();

        $index = $segments->search(fn($segment) => $segment->label === $order->reward_label);

        return $index === false ? 0 : (int) $index;
    }

    private function notify(string $type, string $title, string $message, FrontOrder $order): void
    {
        AdminNotification::create([
            'shop_id' => $order->shop_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'subject_type' => FrontOrder::class,
            'subject_id' => $order->id,
            'url' => route('front-orders.index'),
            'data' => [
                'order_number' => $order->order_number,
                'channel' => $order->order_channel,
                'reward_label' => $order->reward_label,
                'distributor_id' => $order->distributor_id,
                'distributor_marketer_id' => $order->distributor_marketer_id,
            ],
        ]);
    }
}
