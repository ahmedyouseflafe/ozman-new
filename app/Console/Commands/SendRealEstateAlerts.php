<?php

namespace App\Console\Commands;

use App\Mail\RealEstatePropertyAlertMail;
use App\Models\RealEstateAlert;
use App\Models\RealEstateAlertDelivery;
use App\Models\RealEstateProperty;
use App\Services\WhatsAppCloudService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRealEstateAlerts extends Command
{
    protected $signature = 'real-estate:send-alerts {--alert= : Process one alert ID only}';

    protected $description = 'Send email and WhatsApp alerts for newly published properties and price changes';

    public function handle(WhatsAppCloudService $whatsapp): int
    {
        $alerts = RealEstateAlert::query()->where('is_active', true)
            ->when($this->option('alert'), fn (Builder $query, $id) => $query->whereKey($id))
            ->get();

        $sent = 0;
        foreach ($alerts as $alert) {
            foreach ($this->matchingProperties($alert)->cursor() as $property) {
                $fingerprint = sha1(implode('|', [$property->id, $property->price, $property->published_at?->timestamp]));
                $delivery = RealEstateAlertDelivery::firstOrCreate(
                    ['real_estate_alert_id' => $alert->id, 'real_estate_property_id' => $property->id, 'fingerprint' => $fingerprint],
                    ['channel' => $alert->channel, 'status' => 'pending']
                );

                if ($delivery->status === 'sent' || $delivery->attempts >= 3) {
                    continue;
                }

                try {
                    $reference = $alert->channel === 'email'
                        ? $this->sendEmail($alert, $property)
                        : $whatsapp->sendPropertyAlert($alert, $property);

                    $delivery->update(['status' => 'sent', 'attempts' => $delivery->attempts + 1, 'provider_reference' => $reference, 'error_message' => null, 'sent_at' => now()]);
                    $alert->update(['last_notified_at' => now()]);
                    $sent++;
                } catch (Throwable $exception) {
                    $delivery->update(['status' => 'failed', 'attempts' => $delivery->attempts + 1, 'error_message' => mb_substr($exception->getMessage(), 0, 2000)]);
                    Log::warning('Real-estate alert delivery failed', ['alert_id' => $alert->id, 'property_id' => $property->id, 'channel' => $alert->channel, 'error' => $exception->getMessage()]);
                }
            }
        }

        $this->info("Sent {$sent} real-estate alert(s).");

        return self::SUCCESS;
    }

    private function sendEmail(RealEstateAlert $alert, RealEstateProperty $property): string
    {
        if (app()->environment('production') && in_array(config('mail.default'), ['log', 'array'], true)) {
            throw new \RuntimeException('إعدادات إرسال البريد غير مكتملة على بيئة الإنتاج.');
        }

        Mail::to($alert->email, $alert->name)->send(new RealEstatePropertyAlertMail($alert, $property));

        return 'mail:'.config('mail.default');
    }

    private function matchingProperties(RealEstateAlert $alert): Builder
    {
        $filters = $alert->filters ?? [];
        $query = RealEstateProperty::query()->published()
            ->whereHas('shop', fn (Builder $shop) => $shop->where('is_active', true)->where('catalog_type', 'real_estate'))
            ->when($alert->shop_id, fn (Builder $builder) => $builder->where('shop_id', $alert->shop_id))
            ->where(fn (Builder $recent) => $recent->where('published_at', '>=', $alert->created_at)->orWhere('updated_at', '>=', $alert->created_at))
            ->with(['images', 'shop']);

        $query->when(filled($filters['q'] ?? null), function (Builder $builder) use ($filters): void {
            $term = '%'.trim($filters['q']).'%';
            $builder->where(fn (Builder $nested) => $nested->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('city', 'like', $term)->orWhere('neighborhood', 'like', $term)->orWhere('address', 'like', $term));
        });

        foreach (['purpose', 'property_type', 'city', 'floor'] as $field) {
            if (filled($filters[$field] ?? null)) $query->where($field, $filters[$field]);
        }
        if (filled($filters['neighborhood'] ?? null)) $query->where('neighborhood', 'like', '%'.$filters['neighborhood'].'%');
        foreach (['min_price'=>['price','>='],'max_price'=>['price','<='],'min_area'=>['area','>='],'max_area'=>['area','<='],'rooms'=>['rooms','>='],'bathrooms'=>['bathrooms','>=']] as $filter => [$field,$operator]) {
            if (filled($filters[$filter] ?? null)) $query->where($field, $operator, (float) $filters[$filter]);
        }
        foreach (['furnished'=>'furnished','elevator'=>'has_elevator','balcony'=>'has_balcony','garden'=>'has_garden','storage'=>'has_storage','air_conditioning'=>'has_air_conditioning','new_project'=>'is_new_project'] as $filter => $field) {
            if (filter_var($filters[$filter] ?? false, FILTER_VALIDATE_BOOLEAN)) $query->where($field, true);
        }
        if (filter_var($filters['parking'] ?? false, FILTER_VALIDATE_BOOLEAN)) $query->where('parking_spaces', '>', 0);
        if (filter_var($filters['available_now'] ?? false, FILTER_VALIDATE_BOOLEAN)) $query->where(fn (Builder $available) => $available->whereNull('available_from')->orWhereDate('available_from', '<=', today()));

        return $query->orderBy('id')->limit(250);
    }
}
