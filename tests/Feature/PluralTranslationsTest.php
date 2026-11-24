<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * trans_choice() falls back to English when Arabic has no line for the key (plain __() does not),
 * so every plural text must be listed in lang/ar.json or Arabic pages show it in English.
 */
class PluralTranslationsTest extends TestCase
{
    public function test_every_plural_text_has_an_arabic_line(): void
    {
        $arabic = json_decode(File::get(lang_path('ar.json')), true);
        $missing = [];
        $files = array_merge(File::allFiles(resource_path('views')), File::allFiles(app_path()));
        foreach ($files as $file) {
            preg_match_all("/trans_choice\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/u", $file->getContents(), $matches);
            foreach ($matches[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (! array_key_exists($key, $arabic)) {
                    $missing[] = $key;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_a_plural_text_stays_arabic_on_arabic_pages(): void
    {
        app()->setLocale('ar');
        $this->assertSame('منشور واحد', trans_choice('{1} منشور واحد|[2,*] :count منشورات', 1, ['count' => 1]));
        $this->assertSame('3 منشورات', trans_choice('{1} منشور واحد|[2,*] :count منشورات', 3, ['count' => 3]));

        app()->setLocale('he');
        $this->assertSame('3 פוסטים', trans_choice('{1} منشور واحد|[2,*] :count منشورات', 3, ['count' => 3]));
    }
}
