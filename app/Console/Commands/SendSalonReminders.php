<?php

namespace App\Console\Commands;

use App\Models\{PushDevice, SalonAppointment};
use App\Services\FirebaseMessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Reminds customers of their salon appointment on the phone: a day before and an hour before.
 * Appointment times are saved as the salon's local wall-clock time, so "now" is taken in that time zone.
 */
class SendSalonReminders extends Command
{
    protected $signature = 'salon:send-reminders';

    protected $description = 'Push a reminder a day and an hour before salon appointments.';

    public function handle(FirebaseMessagingService $firebase): int
    {
        $now = Carbon::parse(now('Asia/Hebron')->format('Y-m-d H:i:s'), config('app.timezone'));
        $sent = 0;
        $base = fn () => SalonAppointment::query()->with('shop:id,name,slug,catalog_type')->whereNotNull('user_id')
            ->whereIn('status', [SalonAppointment::STATUS_NEW, SalonAppointment::STATUS_CONFIRMED]);

        // A day before: anything in the next 24 hours that's still more than 2 hours away.
        foreach ($base()->whereNull('reminded_day_at')->whereBetween('appointment_at', [$now->copy()->addHours(2), $now->copy()->addDay()])->get() as $appointment) {
            $sent += $this->remind($firebase, $appointment, $appointment->appointment_at->isSameDay($now) ? 'today' : 'tomorrow');
            $appointment->forceFill(['reminded_day_at' => now()])->save();
        }
        // An hour before.
        foreach ($base()->whereNull('reminded_hour_at')->whereBetween('appointment_at', [$now, $now->copy()->addHour()])->get() as $appointment) {
            $sent += $this->remind($firebase, $appointment, 'hour');
            $appointment->forceFill(['reminded_hour_at' => now(), 'reminded_day_at' => $appointment->reminded_day_at ?? now()])->save();
        }
        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    private function remind(FirebaseMessagingService $firebase, SalonAppointment $appointment, string $when): int
    {
        $tokens = PushDevice::where('user_id', $appointment->user_id)->pluck('token');
        if ($tokens->isEmpty() || ! $appointment->shop) {
            return 0;
        }
        $locale = in_array($appointment->locale, ['ar', 'he', 'en'], true) ? $appointment->locale : 'ar';
        $time = $appointment->appointment_at->format('H:i');
        $title = match ($when) {
            'today' => __('تذكير بموعدك اليوم ⏰', [], $locale),
            'tomorrow' => __('تذكير بموعدك بكرا ⏰', [], $locale),
            default => __('موعدك بعد ساعة ⏰', [], $locale),
        };
        $body = __(':service عند :shop الساعة :time', ['service' => $appointment->service, 'shop' => $appointment->shop->name, 'time' => $time], $locale);
        try {
            return $firebase->sendToTokens($tokens, $title, $body, $appointment->shop->publicUrl(),
                ['type' => 'salon_reminder', 'shop_id' => $appointment->shop_id, 'appointment_id' => $appointment->id]) > 0 ? 1 : 0;
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }
}
