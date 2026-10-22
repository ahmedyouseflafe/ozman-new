<?php
namespace App\Http\Controllers;

class AdminPwaController extends Controller
{
    public function manifest()
    {
        return response()->json(['id' => '/admin/analytics', 'name' => 'إدارة أوزمان', 'short_name' => 'إدارة أوزمان',
            'lang' => 'ar', 'dir' => 'rtl', 'start_url' => '/admin/analytics', 'scope' => '/admin/',
            'display' => 'standalone', 'background_color' => '#ffffff', 'theme_color' => '#2563eb',
            'icons' => array_map(fn ($size) => ['src' => '/admin/analytics/icon/'.$size.'.png', 'sizes' => $size.'x'.$size,
                'type' => 'image/png', 'purpose' => 'any maskable'], [192, 512])], 200,
            ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache']);
    }

    public function worker()
    {
        return response(file_get_contents(public_path('admin-activity-sw.js')), 200,
            ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/admin/', 'Cache-Control' => 'no-cache']);
    }

    public function icon(int $size)
    {
        abort_unless(in_array($size, [180, 192, 512], true), 404);
        $im = imagecreatetruecolor($size, $size);
        $blue = imagecolorallocate($im, 37, 99, 235); $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $blue);
        imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), (int) ($size * .52), (int) ($size * .60), $white);
        imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), (int) ($size * .28), (int) ($size * .36), $blue);
        ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im);
        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }
}
