<?php

namespace App\Console\Commands;

use App\Models\{PushDevice, Shop};
use App\Services\Demand\LostDemand;
use App\Services\FirebaseMessagingService;
use Illuminate\Console\Command;
use Throwable;

/** Once a week each shop owner hears the top thing customers looked for in their shop (or their kind of shop) and didn't find. */
class SendLostDemandSummary extends Command
{
    protected $signature = 'demand:weekly-summary {--min=3 : searches needed before a shop is told}';

    protected $description = 'Tell shop owners what customers searched for this week and did not find';

    public function handle(LostDemand $demand, FirebaseMessagingService $firebase): int
    {
        $sent = 0;
        Shop::where('is_active', true)->whereNotNull('user_id')->chunkById(100, function ($shops) use ($demand, $firebase, &$sent) {
            foreach ($shops as $shop) {
                $report = $demand->forShop($shop, 7, 1);
                $top = $report['in_shop']->firstWhere('added', false) ?? $report['in_type']->firstWhere('added', false);
                if (! $top || $top['searches'] < (int) $this->option('min')) {
                    continue;
                }
                $tokens = PushDevice::where('user_id', $shop->user_id)->pluck('token');
                if ($tokens->isEmpty()) {
                    continue;
                }
                $inShop = $report['in_shop']->contains('term', $top['term']);
                try {
                    $sent += $firebase->sendToTokens($tokens,
                        __('زباين بدوّروا وما لقوا 👀'),
                        $inShop
                            ? __(':count زبون دوّروا على «:term» بمحلك هالأسبوع وما لقوه.', ['count' => $top['searches'], 'term' => $top['shown']])
                            : __(':count زبون دوّروا على «:term» بأوزمان هالأسبوع وما لقوه بولا محل.', ['count' => $top['searches'], 'term' => $top['shown']]),
                        route('front.home'),
                        ['type' => 'lost_demand', 'shop_id' => $shop->id]) > 0 ? 1 : 0;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        });
        $this->info("Lost-demand summaries sent: $sent");

        return self::SUCCESS;
    }
}
