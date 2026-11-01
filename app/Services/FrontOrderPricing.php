<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCampaign;
use App\Models\RewardWheel;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Recomputes a main-store (front-orders) cart on the server.
 *
 * The browser only tells us which product, which unit (package / carton / pallet)
 * and how many. Prices, campaign offers, the 5% cart discount and the signup-wheel
 * discount all come from the database, so a tampered request cannot lower the total.
 * The logic mirrors public/script.js (productUnitOptions, cartItemLineTotal,
 * itemsDiscountBreakdown) so the WhatsApp message and the saved order agree.
 */
class FrontOrderPricing
{
    public const UNIT_KEYS = ['package', 'carton', 'pallet'];

    private const STANDARD_DISCOUNT_RATE = 0.05;

    /**
     * @param  array<int, array{product_id:int, unit_key:string, qty:int}>  $items
     * @param  array{discount_type?:?string, discount_value?:mixed, label?:?string}|null  $signupReward
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount: float, total: float, signup_reward: ?array}
     */
    public function price(array $items, bool $isMerchant, ?array $signupReward = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'السلة فاضية.']);
        }

        $products = Product::query()
            ->whereIn('id', array_map(fn ($item) => (int) $item['product_id'], $items))
            ->where('is_active', true)
            ->whereHas('shop', fn ($query) => $query->where('is_active', true))
            ->with('campaigns')
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotalCents = 0;

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(['items' => 'أحد المنتجات لم يعد متاحًا. حدّث السلة وأعد المحاولة.']);
            }

            $unitKey = (string) $item['unit_key'];
            $qty = (int) $item['qty'];
            $unitPrice = $this->unitPrice($product, $unitKey, $isMerchant);
            if ($unitPrice === null) {
                throw ValidationException::withMessages(['items' => 'سعر "'.$product->localized('name').'" بهالوحدة غير متاح. حدّث السلة.']);
            }

            $campaign = $isMerchant ? null : $this->activeCampaign($product, $unitKey);
            $lineCents = $this->lineTotalCents($unitPrice, $qty, $campaign);
            $subtotalCents += $lineCents;

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->localized('name'),
                'unit_key' => $unitKey,
                'unit_label' => $this->unitLabel($unitKey),
                'price' => number_format($unitPrice, 2, '.', '').' ₪',
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'line_total' => round($lineCents / 100, 2),
                'campaign_offer' => $campaign ? $this->campaignSummary($campaign) : null,
                'img' => $product->main_image ? asset($product->main_image) : '',
            ];
        }

        $standardCents = (! $isMerchant && count($lines) >= 2)
            ? (int) round($subtotalCents * self::STANDARD_DISCOUNT_RATE)
            : 0;

        $reward = $isMerchant ? null : $this->verifiedSignupReward($signupReward);
        $rewardCents = $reward ? $this->rewardDiscountCents($reward, $subtotalCents, $subtotalCents - $standardCents) : 0;

        $discountCents = $standardCents + $rewardCents;
        $totalCents = max($subtotalCents - $discountCents, 0);

        return [
            'items' => $lines,
            'subtotal' => round($subtotalCents / 100, 2),
            'discount' => round($discountCents / 100, 2),
            'total' => round($totalCents / 100, 2),
            'signup_reward' => $reward,
        ];
    }

    /** Logs when the browser's numbers disagree with ours, so tampering or a stale cart is visible. */
    public function reportMismatch(array $client, array $server, array $context = []): void
    {
        $clientTotal = isset($client['total']) ? round((float) $client['total'], 2) : null;
        if ($clientTotal === null || abs($clientTotal - $server['total']) < 0.01) {
            return;
        }

        Log::warning('front_order.total_mismatch', [
            ...$context,
            'client_subtotal' => $client['subtotal'] ?? null,
            'client_discount' => $client['discount'] ?? null,
            'client_total' => $clientTotal,
            'server_subtotal' => $server['subtotal'],
            'server_discount' => $server['discount'],
            'server_total' => $server['total'],
        ]);
    }

    public function unitPrice(Product $product, string $unitKey, bool $isMerchant): ?float
    {
        $columns = $isMerchant
            ? ['package' => ['package_price', 'show_package_price'], 'carton' => ['carton_price', 'show_carton_price'], 'pallet' => ['pallet_price', 'show_pallet_price']]
            : ['package' => ['customer_package_price', 'show_customer_package_price'], 'carton' => ['customer_carton_price', 'show_customer_carton_price'], 'pallet' => ['customer_pallet_price', 'show_customer_pallet_price']];

        if (! isset($columns[$unitKey])) {
            return null;
        }

        [$priceColumn, $showColumn] = $columns[$unitKey];
        $price = $product->{$priceColumn};

        if (! $product->{$showColumn} || $price === null || (float) $price <= 0) {
            return null;
        }

        return round((float) $price, 2);
    }

    /** Same pick order as activePricingCampaign() in script.js, limited to campaigns running today. */
    public function activeCampaign(Product $product, string $unitKey): ?ProductCampaign
    {
        $valid = $product->campaigns
            ->filter(fn (ProductCampaign $campaign) => self::campaignIsRunning($campaign)
                && in_array($campaign->offer_type, ['bundle_price', 'range_price'], true)
                && $campaign->offer_price !== null
                && (float) $campaign->offer_price >= 0
                && $this->campaignShapeIsValid($campaign))
            ->values();

        return $valid->first(fn ($campaign) => (string) ($campaign->unit_key ?? '') === $unitKey)
            ?? $valid->first(fn ($campaign) => blank($campaign->unit_key));
    }

    public static function campaignIsRunning(ProductCampaign $campaign): bool
    {
        $today = now()->startOfDay();

        return (! $campaign->starts_at || $campaign->starts_at->lte($today))
            && (! $campaign->ends_at || $campaign->ends_at->gte($today));
    }

    private function campaignShapeIsValid(ProductCampaign $campaign): bool
    {
        if ($campaign->offer_type === 'range_price') {
            $min = (int) $campaign->min_quantity;
            $max = (int) $campaign->max_quantity;

            return $min >= 1 && $max >= $min;
        }

        return (int) $campaign->offer_quantity >= 1;
    }

    private function lineTotalCents(float $unitPrice, int $qty, ?ProductCampaign $campaign): int
    {
        $unitCents = (int) round($unitPrice * 100);

        if ($campaign?->offer_type === 'range_price') {
            $min = (int) $campaign->min_quantity;
            $max = (int) $campaign->max_quantity;
            if ($qty >= $min && $qty <= $max) {
                return (int) round((float) $campaign->offer_price * 100) * $qty;
            }

            return $unitCents * $qty;
        }

        if ($campaign?->offer_type === 'bundle_price') {
            $bundleSize = (int) $campaign->offer_quantity;
            $bundles = intdiv($qty, $bundleSize);
            $remainder = $qty % $bundleSize;

            return $bundles * (int) round((float) $campaign->offer_price * 100) + $remainder * $unitCents;
        }

        return $unitCents * $qty;
    }

    private function campaignSummary(ProductCampaign $campaign): array
    {
        return $campaign->offer_type === 'range_price'
            ? ['type' => 'range_price', 'min_quantity' => (int) $campaign->min_quantity, 'max_quantity' => (int) $campaign->max_quantity, 'unit_price' => (float) $campaign->offer_price, 'label' => $campaign->localized('title')]
            : ['type' => 'bundle_price', 'quantity' => (int) $campaign->offer_quantity, 'price' => (float) $campaign->offer_price, 'label' => $campaign->localized('title')];
    }

    /**
     * The signup-wheel prize lives in the visitor's browser, so we only accept one that
     * matches a segment the active signup wheel can actually land on.
     */
    private function verifiedSignupReward(?array $claimed): ?array
    {
        $type = $claimed['discount_type'] ?? null;
        if (! in_array($type, ['percent', 'amount'], true)) {
            return null;
        }

        $value = round((float) ($claimed['discount_value'] ?? 0), 2);
        if ($value <= 0) {
            return null;
        }

        $segment = RewardWheel::query()
            ->where('key', RewardWheel::CUSTOMER_SIGNUP_DISCOUNTS)
            ->where('is_active', true)
            ->first()
            ?->segments()
            ->where('is_active', true)
            ->where('discount_type', $type)
            ->get()
            ->first(fn ($segment) => abs((float) $segment->discount_value - $value) < 0.005);

        return $segment
            ? ['label' => $segment->label, 'discount_type' => $segment->discount_type, 'discount_value' => (float) $segment->discount_value]
            : null;
    }

    /** Mirrors rewardDiscountValue(): percent is of the subtotal, capped by what is left after the 5%. */
    private function rewardDiscountCents(array $reward, int $subtotalCents, int $remainingCents): int
    {
        $value = max((float) $reward['discount_value'], 0);
        $cents = $reward['discount_type'] === 'percent'
            ? (int) round($subtotalCents * $value / 100)
            : min((int) round($value * 100), $subtotalCents);

        return max(min($cents, $remainingCents), 0);
    }

    private function unitLabel(string $unitKey): string
    {
        return ['package' => 'العبوة', 'carton' => 'الكرتونة', 'pallet' => 'المشطاح'][$unitKey] ?? '';
    }
}
