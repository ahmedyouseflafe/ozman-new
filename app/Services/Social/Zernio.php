<?php

namespace App\Services\Social;

use App\Models\Shop;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Thin client for the Zernio API, which publishes to each shop's own Instagram, Facebook and TikTok. */
class Zernio
{
    public const PLATFORMS = ['instagram', 'facebook', 'tiktok'];

    public function configured(): bool
    {
        return filled(config('services.zernio.key'));
    }

    private function http(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('ZERNIO_API_KEY is not set.');
        }

        return Http::baseUrl(rtrim(config('services.zernio.base_url'), '/'))
            ->withToken(config('services.zernio.key'))
            ->acceptJson()->timeout(30);
    }

    private function json($response): array
    {
        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error') ?: 'Zernio request failed ('.$response->status().')'));
        }

        return $response->json() ?? [];
    }

    /** Every shop gets its own Zernio profile, created the first time it is needed. */
    public function ensureProfile(Shop $shop): string
    {
        if ($shop->zernio_profile_id) {
            return $shop->zernio_profile_id;
        }
        $data = $this->json($this->http()->withHeaders(['Idempotency-Key' => 'ozman-shop-'.$shop->id])->post('/v1/profiles', [
            'name' => mb_substr($shop->name, 0, 80),
            'externalId' => 'shop-'.$shop->id,
            'timezone' => config('services.zernio.timezone'),
        ]));
        $id = (string) data_get($data, 'profile._id');
        if ($id === '') {
            throw new RuntimeException('Zernio did not return a profile.');
        }
        $shop->forceFill(['zernio_profile_id' => $id])->save();

        return $id;
    }

    public function connectUrl(string $platform, string $profileId, string $redirectUrl): string
    {
        $data = $this->json($this->http()->get('/v1/connect/'.$platform, [
            'profileId' => $profileId,
            'redirect_url' => $redirectUrl,
        ]));

        return (string) ($data['authUrl'] ?? throw new RuntimeException('Zernio did not return a connect link.'));
    }

    /** @return array<int, array{id: string, platform: string, username: ?string, name: ?string, picture: ?string, active: bool}> */
    public function accounts(string $profileId): array
    {
        $data = $this->json($this->http()->get('/v1/accounts', ['profileId' => $profileId, 'category' => 'social']));

        return collect($data['accounts'] ?? [])
            ->filter(fn ($a) => in_array($a['platform'] ?? '', self::PLATFORMS, true))
            ->map(fn ($a) => [
                'id' => (string) $a['_id'], 'platform' => $a['platform'], 'username' => $a['username'] ?? null,
                'name' => $a['displayName'] ?? null, 'picture' => $a['profilePicture'] ?? null,
                'active' => (bool) ($a['isActive'] ?? true) && ! ($a['needsReconnection'] ?? false),
            ])->values()->all();
    }

    /** Best hours by engagement; empty when there is no history yet or the analytics add-on is off. */
    public function bestTimes(string $profileId): array
    {
        try {
            $data = $this->json($this->http()->get('/v1/analytics/best-time', ['profileId' => $profileId]));
        } catch (RuntimeException) {
            return [];
        }

        return collect($data['slots'] ?? [])->sortByDesc('avg_engagement')->take(5)->values()->all();
    }

    public function createPost(array $payload): array
    {
        return $this->json($this->http()->post('/v1/posts', $payload));
    }

    public function deletePost(string $postId): void
    {
        $this->json($this->http()->delete('/v1/posts/'.$postId));
    }
}
