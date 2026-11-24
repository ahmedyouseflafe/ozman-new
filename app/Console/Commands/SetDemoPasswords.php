<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Staging only: gives demo accounts new passwords, so app tests can sign in to them.
 * It takes bcrypt hashes (never the passwords), and only touches *@staging.ozman.test accounts.
 */
class SetDemoPasswords extends Command
{
    protected $signature = 'demo:set-passwords {hashes : base64 of a JSON object {email: bcrypt hash}}';

    protected $description = 'Staging only: set new passwords (given as bcrypt hashes) on demo accounts';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Not on production.');

            return self::FAILURE;
        }
        $hashes = json_decode((string) base64_decode((string) $this->argument('hashes'), true), true);
        if (! is_array($hashes) || $hashes === []) {
            $this->error('Give a base64 JSON object of email => bcrypt hash.');

            return self::FAILURE;
        }
        $done = 0;
        foreach ($hashes as $email => $hash) {
            $demo = is_string($email) && str_ends_with($email, '@staging.ozman.test');
            $bcrypt = is_string($hash) && Hash::isHashed($hash) && str_starts_with($hash, '$2y$');
            if (! $demo || ! $bcrypt) {
                $this->warn('Skipped: '.(is_string($email) ? $email : '?').' (only demo accounts and bcrypt hashes)');
                continue;
            }
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->warn('No account: '.$email);
                continue;
            }
            DB::table('users')->where('id', $user->id)->update(['password' => $hash, 'updated_at' => now()]);
            $this->line('Updated: '.$email);
            $done++;
        }
        $this->info("Done: {$done}");

        return self::SUCCESS;
    }
}
