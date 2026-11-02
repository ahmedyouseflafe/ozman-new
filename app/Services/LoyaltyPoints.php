<?php

namespace App\Services;

use App\Http\Controllers\SettingsController;
use App\Models\FrontOrder;
use App\Models\LoyaltyPointEntry;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Customer loyalty points earned on front orders.
 *
 * The ledger (loyalty_point_entries) is the only source of truth. Order points are reconciled against the
 * order status on every relevant change, so repeated or out-of-order status updates never count twice.
 */
class LoyaltyPoints
{
    public const CONFIRM_STATUSES = ['completed', 'delivered'];
    public const CANCEL_STATUSES = ['cancelled', 'not_delivered'];

    private ?array $settings = null;

    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        $stored = [];
        if (Storage::disk('local')->exists(SettingsController::SETTINGS_PATH)) {
            $decoded = json_decode((string) Storage::disk('local')->get(SettingsController::SETTINGS_PATH), true);
            $stored = is_array($decoded['loyalty'] ?? null) ? $decoded['loyalty'] : [];
        }

        return $this->settings = [
            'enabled' => (bool) ($stored['enabled'] ?? true),
            'amount' => max(1, (int) ($stored['amount'] ?? 100)),
            'points' => max(1, (int) ($stored['points'] ?? 10)),
        ];
    }

    public function saveSettings(bool $enabled, int $amount, int $points): void
    {
        $disk = Storage::disk('local');
        $all = $disk->exists(SettingsController::SETTINGS_PATH)
            ? (json_decode((string) $disk->get(SettingsController::SETTINGS_PATH), true) ?: [])
            : [];
        $all['loyalty'] = ['enabled' => $enabled, 'amount' => $amount, 'points' => $points];
        $disk->put(SettingsController::SETTINGS_PATH, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->settings = null;
    }

    /** Every full step of the amount earns the configured points (floor): 250 ₪ → 20, 99 ₪ → 0. */
    public function pointsFor(float $amount): int
    {
        $settings = $this->settings();
        $cents = (int) round(max($amount, 0) * 100);

        return intdiv($cents, $settings['amount'] * 100) * $settings['points'];
    }

    public function participates(?Shop $shop): bool
    {
        // Orders of Ozman's own catalog have no shop and follow the global switch only.
        return $this->settings()['enabled'] && (! $shop || $shop->loyalty_enabled !== false);
    }

    public function eligibleCustomer(?User $user): bool
    {
        return $user !== null && $user->isCustomer() && (bool) $user->is_active;
    }

    /** Final amount the customer pays: the stored total already includes cart discounts; a won wheel discount comes off too. */
    public function baseAmount(FrontOrder $order): float
    {
        $total = (float) $order->total;
        $value = (float) $order->reward_discount_value;
        $discount = match ($order->reward_discount_type) {
            'percent' => $total * min(max($value, 0), 100) / 100,
            'amount' => max($value, 0),
            default => 0,
        };

        return round(max($total - $discount, 0), 2);
    }

    /** Called while an order is being created: links it to the signed-in community customer. */
    public function attachCustomer(FrontOrder $order): void
    {
        $user = Auth::user();
        if (! $order->user_id && $this->eligibleCustomer($user)) {
            $order->user_id = $user->id;
        }
    }

    public function syncNewOrder(FrontOrder $order): void
    {
        if (! $order->user_id || ! $this->eligibleCustomer($order->user) || ! $this->participates($order->shop)) {
            return;
        }
        $this->sync($order, true);
    }

    /** Reconcile the order's ledger rows with its current status and amount. */
    public function sync(FrontOrder $order, bool $isNew = false): void
    {
        if (! $order->user_id) {
            return;
        }

        DB::transaction(function () use ($order, $isNew) {
            $order = FrontOrder::query()->whereKey($order->getKey())->lockForUpdate()->first();
            if (! $order || ! $order->user_id) {
                return;
            }
            $entries = LoyaltyPointEntry::query()->where('front_order_id', $order->id)
                ->whereIn('type', [LoyaltyPointEntry::TYPE_EARN, LoyaltyPointEntry::TYPE_REVERSE])
                ->lockForUpdate()->get();
            // Eligibility is decided once, when the order is placed; later changes only move existing points.
            if (! $isNew && $entries->isEmpty()) {
                return;
            }

            $base = $this->baseAmount($order);
            $target = $this->pointsFor($base);
            $net = (int) $entries->where('status', LoyaltyPointEntry::CONFIRMED)->sum('points');
            $pending = $entries->first(fn ($e) => $e->type === LoyaltyPointEntry::TYPE_EARN && $e->status === LoyaltyPointEntry::PENDING);

            if (in_array($order->status, self::CANCEL_STATUSES, true)) {
                $pending?->update(['status' => LoyaltyPointEntry::CANCELLED]);
                if ($net > 0) {
                    $this->record($order, -$net, LoyaltyPointEntry::TYPE_REVERSE, LoyaltyPointEntry::CONFIRMED, $base);
                }

                return;
            }

            if (in_array($order->status, self::CONFIRM_STATUSES, true)) {
                $missing = $target - $net;
                if ($pending) {
                    $pending->update($missing > 0
                        ? ['points' => $missing, 'base_amount' => $base, 'status' => LoyaltyPointEntry::CONFIRMED]
                        : ['status' => LoyaltyPointEntry::CANCELLED]);
                } elseif ($missing > 0) {
                    $this->record($order, $missing, LoyaltyPointEntry::TYPE_EARN, LoyaltyPointEntry::CONFIRMED, $base);
                }

                return;
            }

            // Open order (new, preparing, ...): keep one pending row while nothing is confirmed yet.
            if ($net > 0) {
                return;
            }
            if ($pending) {
                $pending->update($target > 0 ? ['points' => $target, 'base_amount' => $base] : ['status' => LoyaltyPointEntry::CANCELLED]);
            } elseif ($target > 0) {
                $this->record($order, $target, LoyaltyPointEntry::TYPE_EARN, LoyaltyPointEntry::PENDING, $base);
            }
        });
    }

    public function adjust(User $customer, int $points, string $note, User $admin): LoyaltyPointEntry
    {
        return DB::transaction(function () use ($customer, $points, $note, $admin) {
            // Serializes adjustments for one customer so two deductions cannot overdraw the balance.
            User::query()->whereKey($customer->id)->lockForUpdate()->first();
            if ($points < 0 && $this->balance($customer) + $points < 0) {
                throw ValidationException::withMessages(['points' => __('الخصم أكبر من رصيد العميل المؤكد.')]);
            }

            return LoyaltyPointEntry::create([
                'user_id' => $customer->id, 'points' => $points, 'type' => LoyaltyPointEntry::TYPE_MANUAL,
                'status' => LoyaltyPointEntry::CONFIRMED, 'note' => $note, 'created_by' => $admin->id,
            ]);
        });
    }

    public function balance(User $user): int
    {
        return (int) $user->loyaltyEntries()->where('status', LoyaltyPointEntry::CONFIRMED)->sum('points');
    }

    /**
     * Confirmed points per shop the customer earned them from (null = Ozman / admin adjustments),
     * oldest source first. Only these per-shop amounts can later become a discount at that same shop;
     * gifts to promoters may use all of them.
     *
     * @return \Illuminate\Support\Collection<int, array{shop_id: ?int, shop_name: ?string, balance: int, since: ?string}>
     */
    public function shopBalances(User $user): \Illuminate\Support\Collection
    {
        $rows = $user->loyaltyEntries()
            ->where('status', LoyaltyPointEntry::CONFIRMED)
            ->selectRaw('shop_id, SUM(points) as balance, MIN(CASE WHEN points > 0 THEN created_at END) as since, MIN(CASE WHEN points > 0 THEN id END) as first_id')
            ->groupBy('shop_id')
            ->get();
        $names = Shop::query()->whereIn('id', $rows->pluck('shop_id')->filter())->pluck('name', 'id');

        return $rows
            ->map(fn ($row) => [
                'shop_id' => $row->shop_id !== null ? (int) $row->shop_id : null,
                'shop_name' => $row->shop_id !== null ? ($names[$row->shop_id] ?? null) : null,
                'balance' => (int) $row->balance,
                'since' => $row->since,
                'first_id' => (int) ($row->first_id ?? PHP_INT_MAX),
            ])
            ->sortBy([['since', 'asc'], ['first_id', 'asc']])
            ->values();
    }

    /** Points earned at this shop that are still unspent (what a shop discount may use). */
    public function shopBalance(User $user, ?Shop $shop): int
    {
        $row = $this->shopBalances($user)->firstWhere('shop_id', $shop?->id);

        return max((int) ($row['balance'] ?? 0), 0);
    }

    /**
     * Spends confirmed points on a promoter gift, taking them from the oldest shop first.
     * Writes one negative "gift" ledger row per shop used, so each shop's own balance stays right.
     * Must run inside the caller's transaction.
     */
    public function spendOnGift(User $user, int $points, \App\Models\PromoterGiftSend $send): void
    {
        if ($points <= 0) {
            throw ValidationException::withMessages(['gift' => __('قيمة الهدية غير صالحة.')]);
        }
        // Serializes spending for one customer so two gifts at once cannot overdraw the balance.
        User::query()->whereKey($user->id)->lockForUpdate()->first();
        if ($this->balance($user) < $points) {
            throw ValidationException::withMessages(['gift' => __('رصيد نقاطك المؤكد ما بيكفي لهالهدية.')]);
        }

        $left = $points;
        foreach ($this->shopBalances($user) as $bucket) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, $bucket['balance']);
            if ($take <= 0) {
                continue;
            }
            LoyaltyPointEntry::create([
                'user_id' => $user->id, 'shop_id' => $bucket['shop_id'], 'promoter_gift_send_id' => $send->id,
                'points' => -$take, 'type' => LoyaltyPointEntry::TYPE_GIFT, 'status' => LoyaltyPointEntry::CONFIRMED,
                'note' => trim($send->gift_icon.' '.$send->gift_name.' → '.$send->profile?->display_name),
            ]);
            $left -= $take;
        }

        // A reversed order can leave one shop negative while the total is still enough; cover the rest without a shop.
        if ($left > 0) {
            LoyaltyPointEntry::create([
                'user_id' => $user->id, 'shop_id' => null, 'promoter_gift_send_id' => $send->id,
                'points' => -$left, 'type' => LoyaltyPointEntry::TYPE_GIFT, 'status' => LoyaltyPointEntry::CONFIRMED,
                'note' => trim($send->gift_icon.' '.$send->gift_name.' → '.$send->profile?->display_name),
            ]);
        }
    }

    public function pendingPoints(User $user): int
    {
        return (int) $user->loyaltyEntries()->where('status', LoyaltyPointEntry::PENDING)->sum('points');
    }

    /** Data for the cart hint; null when this visitor or shop earns nothing. */
    public function hint(?Shop $shop): ?array
    {
        if (! $this->participates($shop)) {
            return null;
        }
        $user = Auth::user();
        // Shop owners and staff ordering from the storefront never earn points.
        if ($user && ! $this->eligibleCustomer($user)) {
            return null;
        }

        return ['amount' => $this->settings()['amount'], 'points' => $this->settings()['points'], 'signedIn' => (bool) $user];
    }

    private function record(FrontOrder $order, int $points, string $type, string $status, float $base): void
    {
        LoyaltyPointEntry::create([
            'user_id' => $order->user_id, 'front_order_id' => $order->id, 'shop_id' => $order->shop_id,
            'points' => $points, 'type' => $type, 'status' => $status, 'base_amount' => $base,
        ]);
    }
}
