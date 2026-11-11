<?php

namespace Tests\Feature;

use App\Services\FirebaseMessagingService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class FirebaseCredentialsPathTest extends TestCase
{
    public function test_a_blank_credentials_setting_falls_back_to_the_usual_file(): void
    {
        $path = storage_path('app/private/firebase-service-account.json');
        $existing = File::exists($path) ? File::get($path) : null;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(['client_email' => 'test@example.com', 'private_key' => 'key', 'project_id' => 'demo']));
        config(['services.firebase.credentials' => '']);

        try {
            $credentials = (new ReflectionMethod(FirebaseMessagingService::class, 'credentials'))
                ->invoke(app(FirebaseMessagingService::class));
            $this->assertSame('test@example.com', $credentials['client_email']);
        } finally {
            $existing === null ? File::delete($path) : File::put($path, $existing);
        }
    }
}
