<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('real-estate:send-alerts')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('admin:send-activity-push')->everyMinute()->withoutOverlapping(5);
Artisan::command('promoters:send-notifications', function () {
    $this->info('Sent: '.app(\App\Services\PromoterFollowerNotifications::class)->flush());
});
Schedule::command('promoters:send-notifications')->everyMinute()->withoutOverlapping(5);
