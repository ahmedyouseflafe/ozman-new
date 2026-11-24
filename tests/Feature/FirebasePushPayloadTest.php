<?php

namespace Tests\Feature;

use App\Services\FirebaseMessagingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\{Cache, Http};
use Tests\TestCase;

/** What the app's phones receive: both kinds of message ring on Android and on iPhone. */
class FirebasePushPayloadTest extends TestCase
{
    private string $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $this->credentials = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->credentials, json_encode(['client_email' => 'push@test.iam.gserviceaccount.com', 'private_key' => $pem, 'project_id' => 'ozman-test']));
        config(['services.firebase.credentials' => $this->credentials, 'services.firebase.project_id' => 'ozman-test']);
        Cache::forget('firebase.messaging.access_token');
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/ozman-test/messages/1']),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentials);
        parent::tearDown();
    }

    public function test_an_announcement_to_everyone_rings_on_iphones_too(): void
    {
        app(FirebaseMessagingService::class)->sendToAll('عرض جديد', 'خصم اليوم', 'https://ozman.online');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'fcm.googleapis.com')
            && $request['message']['topic'] === 'all'
            && $request['message']['android']['notification']['sound'] === 'default'
            && $request['message']['apns']['payload']['aps']['sound'] === 'default');
    }

    public function test_an_order_notification_rings_on_iphones(): void
    {
        $sent = app(FirebaseMessagingService::class)->sendToTokens(['phone-token'], 'طلب جديد', 'وصلك طلب', 'https://ozman.online/orders/1');

        $this->assertSame(1, $sent);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'fcm.googleapis.com')
            && $request['message']['token'] === 'phone-token'
            && $request['message']['apns']['payload']['aps']['sound'] === 'default');
    }
}
