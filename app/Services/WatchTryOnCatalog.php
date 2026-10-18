<?php

namespace App\Services;

use App\Models\Category;

class WatchTryOnCatalog
{
    public static function isWatchCategory(?Category $category): bool
    {
        return $category && $category->is_active
            && preg_match('/ساعة|ساعات|watch|שעונ/ui', $category->name) === 1;
    }
}
