<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NewDemoPasswordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_get_new_passwords_written_only_to_the_private_file(): void
    {
        $demo = User::factory()->create(['email' => 'demo-customer@staging.ozman.test']);
        $real = User::factory()->create(['email' => 'someone@example.com']);
        $realHash = $real->password;
        $file = sys_get_temp_dir().'/demo-pw-'.uniqid().'.txt';

        $this->artisan('demo:new-passwords', ['emails' => [$demo->email, $real->email], '--file' => $file])
            ->expectsOutputToContain('Saved 1')->assertSuccessful();

        $this->assertMatchesRegularExpression('/demo-customer@staging\.ozman\.test  (\S{14})/', file_get_contents($file));
        preg_match('/demo-customer@staging\.ozman\.test  (\S{14})/', file_get_contents($file), $m);
        $this->assertTrue(Hash::check($m[1], $demo->fresh()->password));
        $this->assertSame($realHash, $real->fresh()->password);
        $this->assertSame('0600', substr(sprintf('%o', fileperms($file)), -4));
        unlink($file);
    }

    public function test_it_refuses_production_and_the_public_folder(): void
    {
        User::factory()->create(['email' => 'demo-customer@staging.ozman.test']);
        $this->artisan('demo:new-passwords', ['--file' => public_path('pw.txt')])->assertFailed();
        $this->assertFileDoesNotExist(public_path('pw.txt'));
        $this->app['env'] = 'production';
        $this->artisan('demo:new-passwords')->assertFailed();
    }
}
