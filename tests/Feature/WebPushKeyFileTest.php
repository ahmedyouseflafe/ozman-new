<?php

namespace Tests\Feature;

use App\Services\WebPushService;
use Tests\TestCase;

class WebPushKeyFileTest extends TestCase
{
    public function test_a_blank_key_file_setting_falls_back_to_the_default_file(): void
    {
        config([
            'services.web_push.public_key' => null,
            'services.web_push.private_key' => null,
            'services.web_push.key_file' => '',
        ]);

        $this->assertNotEmpty(app(WebPushService::class)->publicKey());
    }
}
