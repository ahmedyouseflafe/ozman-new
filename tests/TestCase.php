<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Test requests otherwise carry the framework's default "Accept-Language: en-us", while
        // the site's audience (and these tests' expected texts) is Arabic by default.
        $this->withHeader('Accept-Language', 'ar');
        app()->setLocale('ar');
    }
}
