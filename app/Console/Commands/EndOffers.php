<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/** Offers with an end: once it passes, the discount is switched off, so every page and order uses the full price. */
class EndOffers extends Command
{
    protected $signature = 'products:end-offers';

    protected $description = 'Switch off product discounts whose end time has passed';

    public function handle(): int
    {
        $ended = Product::whereNotNull('discount_ends_at')->where('discount_ends_at', '<=', now())
            ->update(['discount_price' => null, 'discount_ends_at' => null]);
        if ($ended > 0) {
            $this->info("Ended {$ended} offers.");
        }

        return self::SUCCESS;
    }
}
