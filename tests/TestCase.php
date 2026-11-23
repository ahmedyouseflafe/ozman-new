<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

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

    /**
     * The page, or one of the page's own style/script files it links (public/css/views, public/js/views), contains
     * [$needle]. Pages keep their CSS and scripts in those files, so a check for a style or a script function
     * looks there too.
     */
    protected function assertPageHas(TestResponse $response, string $needle): void
    {
        $html = (string) $response->getContent();
        if (str_contains($html, $needle)) {
            $this->addToAssertionCount(1);

            return;
        }
        preg_match_all('~/((?:css|js)/views/[\w.\-]+\.(?:css|js))~', $html, $files);
        foreach (array_unique($files[1]) as $file) {
            if (str_contains((string) @file_get_contents(public_path($file)), $needle)) {
                $this->addToAssertionCount(1);

                return;
            }
        }
        $this->fail("Neither the page nor its linked style/script files contain [{$needle}].");
    }

    /** Neither the page nor its linked style/script files contain [$needle]. */
    protected function assertPageLacks(TestResponse $response, string $needle): void
    {
        $html = (string) $response->getContent();
        $this->assertStringNotContainsString($needle, $html);
        preg_match_all('~/((?:css|js)/views/[\w.\-]+\.(?:css|js))~', $html, $files);
        foreach (array_unique($files[1]) as $file) {
            $this->assertStringNotContainsString($needle, (string) @file_get_contents(public_path($file)), "Found in {$file}");
        }
    }
}
