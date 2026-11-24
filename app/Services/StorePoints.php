<?php

namespace App\Services;

use App\Models\{LoyaltyPointEntry, Shop, StorePointCode, User};
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Points for purchases made in the shop itself.
 *
 * At the till the shop types the amount and shows a one-time QR; the customer scans it and gets the same points
 * an online order of that amount would earn, counted as that shop's points. A code works once, for 10 minutes;
 * a customer can use at most 3 codes a day from one shop, and nobody can give points to themselves.
 */
class StorePoints
{
    public const MINUTES = 10;
    public const MAX_AMOUNT = 5000;
    public const DAILY_PER_SHOP = 3;

    public function __construct(private LoyaltyPoints $loyalty) {}

    public function create(Shop $shop, User $staff, float $amount): StorePointCode
    {
        if (! $this->loyalty->participates($shop)) {
            throw ValidationException::withMessages(['amount' => __('النقاط موقّفة لهذا المحل.')]);
        }
        if ($amount <= 0 || $amount > self::MAX_AMOUNT) {
            throw ValidationException::withMessages(['amount' => __('اكتب مبلغ بين 1 و :max ₪.', ['max' => self::MAX_AMOUNT])]);
        }
        $points = $this->loyalty->pointsFor($amount);
        if ($points <= 0) {
            $rule = $this->loyalty->settings();
            throw ValidationException::withMessages(['amount' => __('المبلغ أقل من أول درجة نقاط (كل :amount ₪ = :points نقاط).', $rule)]);
        }

        return StorePointCode::create([
            'shop_id' => $shop->id,
            'created_by' => $staff->id,
            'amount' => round($amount, 2),
            'points' => $points,
            'token' => Str::random(40),
            'expires_at' => now()->addMinutes(self::MINUTES),
        ]);
    }

    /** The customer scanned the code: adds the points, once. */
    public function claim(User $customer, string $token): StorePointCode
    {
        return DB::transaction(function () use ($customer, $token) {
            $code = StorePointCode::where('token', $token)->lockForUpdate()->first();
            if (! $code) {
                throw ValidationException::withMessages(['token' => __('الكود مش صحيح.')]);
            }
            $shop = Shop::find($code->shop_id);
            match ($code->status()) {
                'claimed' => throw ValidationException::withMessages(['token' => $code->claimed_by === $customer->id
                    ? __('أخذت نقاط هالكود من قبل.') : __('هالكود انمسح من قبل.')]),
                'cancelled' => throw ValidationException::withMessages(['token' => __('المحل لغى هالكود.')]),
                'expired' => throw ValidationException::withMessages(['token' => __('خلص وقت الكود. اطلب من المحل كود جديد.')]),
                default => null,
            };
            if (! $this->loyalty->eligibleCustomer($customer)) {
                throw ValidationException::withMessages(['token' => __('النقاط لحسابات الزباين بس.')]);
            }
            if (! $shop || ! $this->loyalty->participates($shop)) {
                throw ValidationException::withMessages(['token' => __('النقاط موقّفة لهذا المحل.')]);
            }
            if ($shop->user_id === $customer->id || in_array($shop->id, $customer->accessibleShopIds(), true) || $code->created_by === $customer->id) {
                throw ValidationException::withMessages(['token' => __('ما بتقدر تعطي نقاط لحالك.')]);
            }
            $today = StorePointCode::where('shop_id', $shop->id)->where('claimed_by', $customer->id)
                ->where('claimed_at', '>=', now('Asia/Hebron')->startOfDay()->utc())->count();
            if ($today >= self::DAILY_PER_SHOP) {
                throw ValidationException::withMessages(['token' => __('وصلت لحد النقاط من هالمحل اليوم. بكرا بترجع.')]);
            }

            $entry = LoyaltyPointEntry::create([
                'user_id' => $customer->id,
                'shop_id' => $shop->id,
                'points' => $code->points,
                'type' => LoyaltyPointEntry::TYPE_STORE,
                'status' => LoyaltyPointEntry::CONFIRMED,
                'base_amount' => $code->amount,
                'note' => __('شراء من المحل'),
                'created_by' => $code->created_by,
            ]);
            $code->update(['claimed_by' => $customer->id, 'claimed_at' => now(), 'loyalty_point_entry_id' => $entry->id]);

            return $code->setRelation('shop', $shop);
        });
    }

    /** The QR as rows of "1" (dark) and "0" (light), so the app can draw it without an image library. */
    public function qrRows(string $text): array
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), Encoder::DEFAULT_BYTE_MODE_ECODING)->getMatrix();
        $rows = [];
        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            $row = '';
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                $row .= $matrix->get($x, $y) === 1 ? '1' : '0';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** What the shop's screen shows about a code. */
    public function present(StorePointCode $code, bool $withQr = false): array
    {
        $claimer = $code->claimed_by ? User::find($code->claimed_by) : null;

        return [
            'id' => $code->id,
            'amount' => (float) $code->amount,
            'points' => $code->points,
            'status' => $code->status(),
            'expires_at' => $code->expires_at->toIso8601String(),
            // Only the first name: enough for "points added for Lina", nothing more.
            'claimed_by' => $claimer ? Str::before(trim((string) $claimer->name), ' ') : null,
            'claimed_at' => $code->claimed_at?->toIso8601String(),
        ] + ($withQr ? ['url' => $code->url(), 'qr' => $this->qrRows($code->url())] : []);
    }
}
