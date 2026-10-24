<?php

namespace Tests\Feature;

use App\Models\AdminPushSubscription;
use App\Services\WebPushService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WebPushTransportTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyFile = storage_path('framework/testing/push-'.bin2hex(random_bytes(8)).'.json');
        config(['services.web_push.public_key' => null, 'services.web_push.private_key' => null,
            'services.web_push.key_file' => $this->keyFile, 'services.web_push.subject' => 'https://ozman.test']);
    }

    protected function tearDown(): void
    {
        File::delete([$this->keyFile, $this->keyFile.'.lock']);
        parent::tearDown();
    }

    public function test_real_encryption_and_vapid_signing_with_provider_acceptance_expiry_and_retry(): void
    {
        // Only the HTTP provider is simulated; use the actual WebPush library,
        // signing, encryption and response handling (no real push is sent).
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([new Response(201), new Response(410), new Response(503)]));
        $handler->push(Middleware::history($requests));
        $service = new WebPushService(new Client(['handler' => $handler]));
        $public = $service->publicKey();
        $service->verifyConfiguration();
        $this->assertSame($public, (new WebPushService)->publicKey());
        $device = new AdminPushSubscription(['endpoint' => 'https://fcm.googleapis.com/fcm/send/local-test',
            'public_key' => $public, 'auth_token' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='),
            'content_encoding' => 'aes128gcm']);
        foreach (['sent', 'expired', 'retry'] as $expected) {
            $this->assertSame($expected, $service->sendAdminDevice($device, ['title' => 'Local test', 'body' => 'Private body']));
        }
        $this->assertCount(3, $requests);
        $request = $requests[0]['request'];
        $this->assertStringStartsWith('vapid ', $request->getHeaderLine('Authorization'));
        $this->assertSame('aes128gcm', $request->getHeaderLine('Content-Encoding'));
        $this->assertStringNotContainsString('Private body', (string) $request->getBody());
    }

    public function test_existing_corrupt_key_file_is_never_silently_replaced(): void
    {
        File::ensureDirectoryExists(dirname($this->keyFile));
        File::put($this->keyFile, '{broken');
        try {
            (new WebPushService)->publicKey();
            $this->fail('Corrupt key file should be reported');
        } catch (\RuntimeException) {
            $this->assertSame('{broken', File::get($this->keyFile));
        }
    }

    public function test_partial_environment_key_configuration_is_reported(): void
    {
        config(['services.web_push.public_key' => 'only-one-key']);
        $this->expectException(\RuntimeException::class);
        (new WebPushService)->publicKey();
    }
}
