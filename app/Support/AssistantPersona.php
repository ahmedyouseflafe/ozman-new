<?php

namespace App\Support;

/** The assistants' name in the visitor's language (the illustrated girl on the website and in the app). */
class AssistantPersona
{
    public static function name(?string $locale = null): string
    {
        return match ($locale ?? app()->getLocale()) {
            'he' => (string) config('services.assistant_persona.name_he', 'פולה'),
            'en' => (string) config('services.assistant_persona.name_en', 'Fulla'),
            default => (string) config('services.assistant_persona.name', 'فلة'),
        };
    }
}
