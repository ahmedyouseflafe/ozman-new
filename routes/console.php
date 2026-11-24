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
Schedule::command('salon:send-reminders')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer();
// Shop owners: today's orders and sales, every evening at 22:30 local time.
Schedule::command('products:end-offers')->everyMinute()->withoutOverlapping(5);
Schedule::command('merchant:daily-summary')->dailyAt('22:30')->timezone('Asia/Hebron')->withoutOverlapping(30)->onOneServer();
Schedule::command('demand:weekly-summary')->weeklyOn(6, '10:20')->timezone('Asia/Hebron')->withoutOverlapping(30)->onOneServer();

// Photos owners send to their assistant are kept only long enough to confirm a social post with them.
Schedule::call(function () {
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    foreach ($disk->allFiles('assistant-images') as $file) {
        if ($disk->lastModified($file) < now()->subDays(2)->getTimestamp()) {
            $disk->delete($file);
        }
    }
    // Spoken replies are kept a month, so a repeated answer is read for free.
    foreach ($disk->allFiles('assistant-speech') as $file) {
        if ($disk->lastModified($file) < now()->subDays(30)->getTimestamp()) {
            $disk->delete($file);
        }
    }
})->name('assistant-images:prune')->dailyAt('03:45')->withoutOverlapping(30);
