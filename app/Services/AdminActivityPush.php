<?php
namespace App\Services;

use App\Models\{AdminPushSubscription, ShopActivityEvent};
use Illuminate\Support\Facades\{DB, Schema};

class AdminActivityPush
{
    private ?bool $ready = null;
    private bool $scheduled = false;

    public function ready(): bool
    {
        return $this->ready ??= Schema::hasTable('admin_push_deliveries');
    }

    public function enqueue(int $eventId): void
    {
        if (! $this->ready()) return;
        $ids = AdminPushSubscription::whereHas('user', fn ($q) => $q->where('role', 'super_admin')->where('is_active', true))->pluck('id');
        foreach ($ids as $id) DB::table('admin_push_deliveries')->insertOrIgnore([
            'subscription_id' => $id, 'event_id' => $eventId, 'available_at' => now(),
        ]);
        if ($ids->isEmpty() || $this->scheduled || app()->runningInConsole()) return;
        $this->scheduled = true;
        // Delivery runs after the HTTP response and sees committed records only.
        // The scheduled command retries after network errors or interrupted workers.
        app()->terminating(function () {
            try { $this->flush(15, 15); } catch (\Throwable $e) { report($e); }
        });
    }

    public function payload(ShopActivityEvent $event): array
    {
        $shop = $event->shop?->name ?? 'أوزمان';
        $url = route('admin.analytics.index');
        if ($event->subject_type === 'FrontOrder') $url .= '?order='.$event->subject_id;
        else $url .= '?'.http_build_query(['shop_id' => $event->shop_id, 'from' => $event->created_at->toDateString(), 'to' => $event->created_at->toDateString()]).'#activity';
        $body = $event->title;
        if (isset($event->data['total'])) $body .= ' • '.number_format((float) $event->data['total'], 2).' ₪';
        return ['title' => $shop, 'body' => mb_substr($body, 0, 280), 'url' => $url,
            'tag' => 'ozman-admin-event-'.$event->id, 'data' => ['event_id' => $event->id, 'kind' => $event->kind]];
    }

    public function flush(int $limit = 100, int $timeBudgetSeconds = 45): int
    {
        if (! $this->ready()) return 0;
        $sent = 0;
        $deadline = microtime(true) + $timeBudgetSeconds;
        $ids = DB::table('admin_push_deliveries')->whereNull('sent_at')->whereNull('failed_at')
            ->where('available_at', '<=', now())->where(fn ($q) => $q->whereNull('locked_at')->orWhere('locked_at', '<', now()->subMinutes(3)))
            ->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            if (microtime(true) >= $deadline) break;
            $claimed = DB::table('admin_push_deliveries')->where('id', $id)->whereNull('sent_at')->whereNull('failed_at')
                ->where('available_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('locked_at')->orWhere('locked_at', '<', now()->subMinutes(3)))
                ->update(['locked_at' => now(), 'attempts' => DB::raw('attempts + 1')]);
            if (! $claimed) continue;
            $delivery = DB::table('admin_push_deliveries')->find($id);
            $subscription = AdminPushSubscription::with('user')->find($delivery->subscription_id);
            if (! $subscription) continue;
            if (! $subscription->user?->isSuperAdmin() || ! $subscription->user->is_active) { $subscription->delete(); continue; }
            try {
                $event = ShopActivityEvent::with('shop')->findOrFail($delivery->event_id);
                $result = app(WebPushService::class)->sendAdminDevice($subscription, $this->payload($event));
                if ($result === 'expired') { $subscription->delete(); continue; }
                if ($result !== 'sent') throw new \RuntimeException('Push service rejected delivery');
                DB::table('admin_push_deliveries')->where('id', $id)->update(['sent_at' => now(), 'locked_at' => null, 'last_error' => null]);
                $sent++;
            } catch (\Throwable $e) {
                // Do not persist provider URLs or credentials in the user-facing error.
                DB::table('admin_push_deliveries')->where('id', $id)->update([
                    'locked_at' => null, 'available_at' => now()->addSeconds(min(3600, 30 * (2 ** min($delivery->attempts, 7)))),
                    'failed_at' => $delivery->attempts >= 10 ? now() : null,
                    'last_error' => 'تعذّر التسليم لخدمة إشعارات الجهاز.',
                ]);
            }
        }
        return $sent;
    }
}
