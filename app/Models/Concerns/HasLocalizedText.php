<?php

namespace App\Models\Concerns;

trait HasLocalizedText
{
    public function localized(string $attribute, ?string $fallbackAttribute = null): string
    {
        $locale = app()->getLocale();
        $translations = $this->getAttribute("{$attribute}_translations");
        $fallbackAttribute ??= $attribute;

        if (is_array($translations) && filled($translations[$locale] ?? null)) {
            return (string) $translations[$locale];
        }

        if (is_array($translations) && filled($translations['ar'] ?? null)) {
            return (string) $translations['ar'];
        }

        return (string) ($this->getAttribute($fallbackAttribute) ?? '');
    }
}
