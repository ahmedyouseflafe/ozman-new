<?php

namespace App\Services;

use App\Models\OfferPushSubscription;
use App\Models\RestaurantDriver;
use App\Models\Shop;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use RuntimeException;
use Throwable;

class WebPushService
{
    public function publicKey(): string
    {
        return $this->vapidConfiguration()['publicKey'];
    }

    public function sendToShop(Shop $shop, string $title, string $body, string $url, array $data = []): int
    {
        if (! $shop->user_id) {
            return 0;
        }

        $subscriptions = WebPushSubscription::query()
            ->where('shop_id', $shop->id)
            ->where('user_id', $shop->user_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => route('merchant-app.icon', ['shop' => $shop, 'size' => 192]),
            'badge' => route('merchant-app.icon', ['shop' => $shop, 'size' => 192]),
            'tag' => ($data['type'] ?? 'ozman').'-'.($data['order_id'] ?? now()->timestamp),
            'requireInteraction' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->deliver($subscriptions, $payload, WebPushSubscription::class, [
            'channel' => 'merchant_orders',
            'shop_id' => $shop->id,
        ]);
    }

    public function sendToDriver(RestaurantDriver $driver, string $title, string $body, string $url, array $data = []): int
    {
        $subscriptions = WebPushSubscription::query()
            ->where('shop_id', $driver->shop_id)
            ->where('user_id', $driver->user_id)
            ->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $driver->loadMissing('shop');
        $icon = route('merchant-app.icon', ['shop' => $driver->shop->slug, 'size' => 192]);
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon,
            'badge' => $icon,
            'tag' => 'driver-order-'.($data['order_id'] ?? now()->timestamp),
            'requireInteraction' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->deliver($subscriptions, $payload, WebPushSubscription::class, [
            'channel' => 'driver_orders',
            'shop_id' => $driver->shop_id,
            'user_id' => $driver->user_id,
        ]);
    }

    public function sendOfferToAll(?Shop $shop, string $title, string $body, string $url, array $data = []): int
    {
        if (! Schema::hasTable('offer_push_subscriptions')) {
            return 0;
        }

        $subscriptions = OfferPushSubscription::query()->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $icon = $shop
                ? route('merchant-app.icon', ['shop' => $shop, 'size' => 192])
                : asset('ozman-favicon.png');
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'icon' => $icon,
                'badge' => $icon,
                'tag' => 'offer-'.($data['offer_id'] ?? $data['story_id'] ?? now()->timestamp),
                'requireInteraction' => false,
                'data' => array_merge($data, ['audience' => 'all_offers']),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            return $this->deliver($subscriptions, $payload, OfferPushSubscription::class, [
                'channel' => 'all_offers',
                'shop_id' => $shop?->id,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to broadcast offer Web Push notification.', [
                'shop_id' => $shop?->id,
                'reason' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function deliver(iterable $subscriptions, string $payload, string $subscriptionModel, array $logContext): int
    {
        $webPush = new WebPush(
            ['VAPID' => $this->vapidConfiguration()],
            ['TTL' => 300, 'urgency' => 'high', 'batchSize' => 100],
        );
        $webPush->setReuseVAPIDHeaders(true);

        foreach ($subscriptions as $storedSubscription) {
            $webPush->queueNotification(
                new Subscription(
                    $storedSubscription->endpoint,
                    $storedSubscription->public_key,
                    $storedSubscription->auth_token,
                    $storedSubscription->content_encoding,
                ),
                $payload,
            );
        }

        $sent = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $subscriptionModel::query()
                    ->where('endpoint_hash', hash('sha256', $report->getEndpoint()))
                    ->delete();

                continue;
            }

            Log::warning('Web Push notification failed.', array_merge($logContext, [
                'reason' => $report->getReason(),
            ]));
        }

        return $sent;
    }

    private function vapidConfiguration(): array
    {
        $configuredPublicKey = config('services.web_push.public_key');
        $configuredPrivateKey = config('services.web_push.private_key');

        if (filled($configuredPublicKey) && filled($configuredPrivateKey)) {
            return [
                'subject' => $this->subject(),
                'publicKey' => (string) $configuredPublicKey,
                'privateKey' => (string) $configuredPrivateKey,
            ];
        }

        $keyFile = (string) config('services.web_push.key_file', storage_path('app/private/web-push-vapid.json'));
        File::ensureDirectoryExists(dirname($keyFile));
        $lock = fopen($keyFile.'.lock', 'c+');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the Web Push key lock file.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the Web Push key file.');
            }

            $keys = $this->readKeyFile($keyFile);
            if (! $keys) {
                $keys = $this->generateVapidKeys();
                $encoded = json_encode($keys, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                if (file_put_contents($keyFile, $encoded, LOCK_EX) === false) {
                    throw new RuntimeException('Unable to save the Web Push keys.');
                }
                @chmod($keyFile, 0600);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return [
            'subject' => $this->subject(),
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ];
    }

    private function readKeyFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $keys = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($keys) && filled($keys['publicKey'] ?? null) && filled($keys['privateKey'] ?? null)
            ? $keys
            : null;
    }

    private function generateVapidKeys(): array
    {
        try {
            return VAPID::createVapidKeys();
        } catch (Throwable $exception) {
            // Some Windows PHP installations do not ship OpenSSL's default
            // configuration file. Supplying our small provider config keeps
            // local key generation working without weakening production keys.
            $key = openssl_pkey_new([
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'private_key_bits' => 2048,
                'config' => base_path('config/openssl-web-push.cnf'),
            ]);
            $details = $key === false ? false : openssl_pkey_get_details($key);
            $ec = is_array($details) ? ($details['ec'] ?? null) : null;

            if (! is_array($ec) || ! isset($ec['d'], $ec['x'], $ec['y'])) {
                throw new RuntimeException('Unable to generate the Web Push VAPID keys.', previous: $exception);
            }

            return [
                'publicKey' => $this->base64UrlEncode("\x04".$ec['x'].$ec['y']),
                'privateKey' => $this->base64UrlEncode($ec['d']),
            ];
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function subject(): string
    {
        $subject = (string) config('services.web_push.subject', config('app.url'));

        return filter_var($subject, FILTER_VALIDATE_EMAIL)
            ? 'mailto:'.$subject
            : $subject;
    }
}
