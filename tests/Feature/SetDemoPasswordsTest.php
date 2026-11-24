<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetDemoPasswordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_demo_accounts_get_the_new_hash(): void
    {
        $demo = User::factory()->create(['email' => 'demo-customer@staging.ozman.test']);
        $real = User::factory()->create(['email' => 'someone@example.com']);
        $realHash = $real->password;
        $payload = base64_encode(json_encode([
            $demo->email => Hash::make('new-demo-pass'),
            $real->email => Hash::make('nope'),
            'demo-x@staging.ozman.test' => 'not-a-hash',
        ]));

        $this->artisan('demo:set-passwords', ['hashes' => $payload])->expectsOutputToContain('Done: 1')->assertSuccessful();

        $this->assertTrue(Hash::check('new-demo-pass', $demo->fresh()->password));
        $this->assertSame($realHash, $real->fresh()->password);
    }

    public function test_it_refuses_on_production(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('demo:set-passwords', ['hashes' => base64_encode('{}')])->assertFailed();
    }
}
