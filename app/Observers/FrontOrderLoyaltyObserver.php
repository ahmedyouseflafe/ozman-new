<?php

namespace App\Observers;

use App\Models\FrontOrder;
use App\Services\LoyaltyPoints;

/**
 * Hooks loyalty points into every place that creates or updates a front order,
 * without touching the checkout controllers. Points must never block an order, so failures are only reported.
 */
class FrontOrderLoyaltyObserver
{
    private const AMOUNT_FIELDS = ['status', 'total', 'reward_discount_value', 'reward_discount_type'];

    public function __construct(private LoyaltyPoints $points) {}

    public function creating(FrontOrder $order): void
    {
        $this->safely(fn () => $this->points->attachCustomer($order));
    }

    public function created(FrontOrder $order): void
    {
        $this->safely(fn () => $this->points->syncNewOrder($order));
    }

    public function updated(FrontOrder $order): void
    {
        if ($order->user_id && $order->wasChanged(self::AMOUNT_FIELDS)) {
            $this->safely(fn () => $this->points->sync($order));
        }
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
