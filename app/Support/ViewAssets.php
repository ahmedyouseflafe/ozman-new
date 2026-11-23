<?php

namespace App\Support;

/**
 * A page's own stylesheet or script (public/css/views, public/js/views), linked with its last-change time so browsers
 * fetch it again whenever it is edited. Used through @viewStyle('front.furniture_store') / @viewScript('...').
 */
class ViewAssets
{
    public static function style(string $name): string
    {
        return '<link rel="stylesheet" href="'.e(self::url("css/views/{$name}.css")).'">';
    }

    public static function script(string $name): string
    {
        return '<script src="'.e(self::url("js/views/{$name}.js")).'"></script>';
    }

    private static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).'?v='.(is_file($file) ? filemtime($file) : 0);
    }
}
