<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\FrontOrder;
use App\Models\RewardWheel;
use App\Models\RewardWheelSegment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FrontOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'reward_wheel_id' => ['nullable', 'integer', 'exists:reward_wheels,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'customer_whatsapp' => ['nullable', 'string', 'max:60'],
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
        ]);

        $order = FrontOrder::create([
            ...$validated,
            'order_number' => $this->makeOrderNumber(),
            'subtotal' => $validated['subtotal'] ?? 0,
            'discount' => $validated['discount'] ?? 0,
            'total' => $validated['total'] ?? 0,
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
            ->where('is_active', true)
            ->with(['segments' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
            ])
            ->first();

        if (! $wheel || $wheel->segments->count() < 2) {
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

    public function index(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $channel = $request->query('channel');
        $search = trim((string) $request->query('search', ''));

        $ordersQuery = FrontOrder::query()
            ->with(['shop', 'rewardWheel'])
            ->when(in_array($channel, ['whatsapp', 'instant_payment'], true), fn($query) => $query->where('order_channel', $channel))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_whatsapp', 'like', "%{$search}%")
                        ->orWhere('reward_label', 'like', "%{$search}%");
                });
            })
            ->latest();

        return view('admin.front_orders.index', [
            'orders' => $ordersQuery->paginate(25)->withQueryString(),
            'totalCount' => FrontOrder::count(),
            'instantCount' => FrontOrder::where('order_channel', 'instant_payment')->count(),
            'whatsappCount' => FrontOrder::where('order_channel', 'whatsapp')->count(),
            'rewardedCount' => FrontOrder::whereNotNull('reward_label')->count(),
            'selectedChannel' => $channel,
            'search' => $search,
        ]);
    }

    private function makeOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (FrontOrder::where('order_number', $number)->exists());

        return $number;
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
            ],
        ]);
    }
}
