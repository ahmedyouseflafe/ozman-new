<?php
namespace App\Console\Commands;

use App\Services\AdminActivityPush;
use Illuminate\Console\Command;

class SendAdminActivityPush extends Command
{
    protected $signature = 'admin:send-activity-push';
    protected $description = 'Deliver and retry pending administrator activity notifications';
    public function handle(AdminActivityPush $push): int
    {
        $this->info('Accepted by push service: '.$push->flush(100));
        return self::SUCCESS;
    }
}
