<?php

namespace App\Services;

use App\Models\RealEstateAlert;
use App\Models\RealEstateProperty;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppCloudService
{
    public function sendPropertyAlert(RealEstateAlert $alert, RealEstateProperty $property): string
    {
        $token = config('services.whatsapp_cloud.token');
        $phoneNumberId = config('services.whatsapp_cloud.phone_number_id');
        $template = config('services.whatsapp_cloud.property_alert_template');

        if (! $token || ! $phoneNumberId || ! $template) {
            throw new RuntimeException('إعدادات WhatsApp Cloud API غير مكتملة.');
        }

        $response = Http::withToken($token)->acceptJson()->timeout(20)->post(
            'https://graph.facebook.com/'.config('services.whatsapp_cloud.graph_version', 'v23.0').'/'.$phoneNumberId.'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhone((string) $alert->phone),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => config('services.whatsapp_cloud.template_language', 'ar')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => collect([
                            $property->localized('title'),
                            number_format((float) $property->price).' '.$property->currency,
                            implode('، ', array_filter([$property->city, $property->neighborhood])) ?: '-',
                            $property->publicUrl(),
                        ])->map(fn (string $text) => ['type' => 'text', 'text' => $text])->all(),
                    ]],
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Meta: '.$response->json('error.message', $response->body()));
        }

        return (string) $response->json('messages.0.id', 'accepted');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;

        if (str_starts_with($digits, '0')) {
            $digits = config('services.whatsapp_cloud.default_country_code', '972').substr($digits, 1);
        }

        if (! $digits || strlen($digits) < 8) {
            throw new RuntimeException('رقم واتساب غير صالح للإرسال.');
        }

        return $digits;
    }
}
