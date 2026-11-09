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
Artisan::command('promoters:prune-stories', function () {
    $this->info('Deleted: '.app(\App\Services\PromoterStoryCleanup::class)->run());
})->purpose('Delete promoter stories that expired more than 3 days ago, with their files');
Schedule::command('promoters:prune-stories')->dailyAt('03:30')->withoutOverlapping(30);
Schedule::command('analytics:weekly-report')->weeklyOn(0, '08:10')->timezone('Asia/Jerusalem')->withoutOverlapping(30)->onOneServer();
