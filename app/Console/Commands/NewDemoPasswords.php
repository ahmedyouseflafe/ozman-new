<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Staging only: gives demo accounts new random passwords and writes them to a private file in the hosting
 * account's home folder (outside the website). Nothing is printed, so the passwords never reach a log.
 */
class NewDemoPasswords extends Command
{
    protected $signature = 'demo:new-passwords {emails?* : demo account emails (default: the demo restaurant owner and the demo customer)} {--file= : where to write them (default: ~/staging-demo-passwords.txt)} {--in-app : write to storage/app/private/staging-demo-passwords.txt inside the app instead}';

    protected $description = 'Staging only: new random passwords for demo accounts, saved to a private file in the home folder';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Not on production.');

            return self::FAILURE;
        }
        $emails = $this->argument('emails') ?: ['demo-owner-restaurant@staging.ozman.test', 'demo-customer@staging.ozman.test'];
        $file = $this->option('file') ?: ($this->option('in-app')
            ? storage_path('app/private/staging-demo-passwords.txt')
            : rtrim((string) (getenv('HOME') ?: dirname(base_path())), '/').'/staging-demo-passwords.txt');
        if (! is_dir(dirname($file))) {
            @mkdir(dirname($file), 0700, true);
        }
        $public = realpath(public_path()) ?: public_path();
        if (str_starts_with((string) (realpath(dirname($file)) ?: dirname($file)), $public)) {
            $this->error('The file must be outside the public website folder.');

            return self::FAILURE;
        }

        $lines = [];
        foreach ($emails as $email) {
            if (! str_ends_with((string) $email, '@staging.ozman.test')) {
                $this->warn('Skipped (not a demo account): '.$email);
                continue;
            }
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->warn('No account: '.$email);
                continue;
            }
            $password = Str::password(14, symbols: false);
            DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password), 'updated_at' => now()]);
            $lines[] = $email.'  '.$password;
            $this->line('New password for: '.$email);
        }
        if ($lines === []) {
            $this->error('No demo accounts changed.');

            return self::FAILURE;
        }

        $old = umask(077);
        file_put_contents($file, "Ozman staging demo accounts (".now('Asia/Hebron')->format('Y-m-d H:i').")\n".implode("\n", $lines)."\n");
        umask($old);
        @chmod($file, 0600);
        $this->info('Saved '.count($lines).' to '.$file.' (only the hosting account can read it). App folder: '.base_path());

        return self::SUCCESS;
    }
}
