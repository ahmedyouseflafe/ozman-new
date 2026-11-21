<?php

namespace App\Console\Commands;

use App\Models\{FrontOrder, PushDevice, Shop};
use App\Services\FirebaseMessagingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

/** Late evening: each shop owner who had orders today gets "today: 12 orders, 640 ₪". */
class SendMerchantDailySummary extends Command
{
    protected $signature = 'merchant:daily-summary';

    protected $description = "Send shop owners a notification with today's orders and sales";

    public function handle(FirebaseMessagingService $firebase): int
    {
        $start = CarbonImmutable::now('Asia/Hebron')->startOfDay()->utc();
        $end = $start->addDay();
        $rows = FrontOrder::query()->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', \App\Services\LoyaltyPoints::CANCEL_STATUSES)->whereNotNull('shop_id')
            ->selectRaw('shop_id, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS sales')->groupBy('shop_id')->get();
        $sent = 0;
        foreach ($rows as $row) {
            $shop = Shop::find($row->shop_id);
            if (! $shop || ! $shop->is_active || ! $shop->user_id) {
                continue;
            }
            $tokens = PushDevice::where('user_id', $shop->user_id)->pluck('token');
            if ($tokens->isEmpty()) {
                continue;
            }
            $sales = rtrim(rtrim(number_format((float) $row->sales, 2, '.', ''), '0'), '.');
            try {
                $sent += $firebase->sendToTokens($tokens,
                    __('ملخص اليوم في :shop 📊', ['shop' => $shop->name]),
                    trans_choice('{1} اليوم إجاك طلب واحد بـ :sales ₪.|[2,*] اليوم إجاك :count طلبات بـ :sales ₪.', (int) $row->orders, ['count' => $row->orders, 'sales' => $sales]),
                    $shop->publicUrl(),
                    ['type' => 'daily_summary', 'shop_id' => $shop->id]) > 0 ? 1 : 0;
            } catch (Throwable $e) {
                report($e);
            }
        }
        $this->info("Daily summaries sent: $sent");

        return self::SUCCESS;
    }
}
