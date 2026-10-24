<?php

namespace App\Console\Commands;

use App\Services\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseWebPush extends Command
{
    protected $signature = 'web-push:diagnose';
    protected $description = 'Check push configuration and storage without sending notifications or printing secrets';

    public function handle(WebPushService $push): int
    {
        $failed = false;
        foreach (['openssl', 'mbstring', 'curl'] as $extension) {
            $ok = extension_loaded($extension);
            $this->line(($ok ? 'OK' : 'FAIL').' PHP '.$extension);
            $failed = $failed || ! $ok;
        }
        try {
            $push->verifyConfiguration();
            $this->info('OK VAPID signing; public key fingerprint: '.substr(hash('sha256', $push->publicKey()), 0, 12));
        } catch (\Throwable $e) {
            $this->error('FAIL VAPID configuration/signing ('.class_basename($e).'). Check the configured key pair and key file permissions.');
            $failed = true;
        }
        foreach (['offer_push_subscriptions', 'web_push_subscriptions', 'admin_push_subscriptions', 'admin_push_deliveries'] as $table) {
            try {
                if (! Schema::hasTable($table)) throw new \RuntimeException('missing table');
                $columns = $table === 'admin_push_deliveries'
                    ? ['subscription_id', 'available_at', 'sent_at', 'failed_at', 'locked_at']
                    : ['endpoint_hash', 'endpoint', 'public_key', 'auth_token', 'last_seen_at'];
                if (! Schema::hasColumns($table, $columns)) throw new \RuntimeException('missing columns');
                $this->info('OK '.$table.' rows='.DB::table($table)->count());
                if ($table === 'admin_push_deliveries') {
                    $this->line('Pending='.DB::table($table)->whereNull('sent_at')->whereNull('failed_at')->count()
                        .' Failed='.DB::table($table)->whereNotNull('failed_at')->count());
                }
            } catch (\Throwable) {
                $this->error('FAIL '.$table.' unavailable. Check database access and pending migrations.');
                $failed = true;
            }
        }
        $this->line('Browser activation and provider delivery still require a device test. No notifications sent.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
