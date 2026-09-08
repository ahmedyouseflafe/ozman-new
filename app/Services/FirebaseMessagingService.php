<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FirebaseMessagingService
{
    public function sendToAll(string $title, string $body, string $url): void
    {
        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);

        if (! $projectId) {
            throw new RuntimeException('لم يتم ضبط Firebase Project ID.');
        }

        Http::withToken($this->accessToken($credentials))
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'topic' => 'all',
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => ['url' => $url],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'ozman_notifications',
                            'sound' => 'default',
                        ],
                    ],
                ],
            ])->throw();
    }

    public function sendToTokens(iterable $tokens, string $title, string $body, string $url, array $data = []): int
    {
        $tokens = collect($tokens)
            ->filter(fn ($token) => is_string($token) && trim($token) !== '')
            ->map(fn ($token) => trim($token))
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);
        if (! $projectId) {
            throw new RuntimeException('لم يتم ضبط Firebase Project ID.');
        }

        $accessToken = $this->accessToken($credentials);
        $sent = 0;
        $lastFailure = null;
        $payloadData = collect(array_merge($data, ['url' => $url]))
            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
            ->all();

        foreach ($tokens as $token) {
            try {
                Http::withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $token,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data' => $payloadData,
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'ozman_notifications',
                                    'sound' => 'default',
                                    'visibility' => 'PUBLIC',
                                    'notification_count' => 1,
                                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                ],
                            ],
                            'apns' => [
                                'headers' => ['apns-priority' => '10'],
                                'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
                            ],
                        ],
                    ])->throw();
                $sent++;
            } catch (Throwable $exception) {
                $lastFailure = $exception;
                report($exception);
            }
        }

        if ($sent === 0 && $lastFailure) {
            throw $lastFailure;
        }

        return $sent;
    }

    public function isConfigured(): bool
    {
        $path = config('services.firebase.credentials');

        return is_string($path) && $path !== '' && is_file($path);
    }

    private function credentials(): array
    {
        $path = config('services.firebase.credentials');
        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('حمّل مفتاح حساب خدمة Firebase وضع مساره في FIREBASE_CREDENTIALS.');
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('ملف بيانات Firebase غير صالح.');
        }

        return $credentials;
    }

    private function accessToken(array $credentials): string
    {
        return Cache::remember('firebase.messaging.access_token', 3300, function () use ($credentials) {
            $now = time();
            $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
            $header = $encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $claims = $encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);
            $unsigned = "{$header}.{$claims}";
            openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw()->json();

            return $response['access_token'];
        });
    }
}
