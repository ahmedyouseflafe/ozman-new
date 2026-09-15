<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopAppController extends Controller
{
    public function index(Shop $shop): View
    {
        abort_unless($shop->is_active, 404);

        return view('front.shop_app', [
            'shop' => $shop,
        ]);
    }

    public function launch(Shop $shop): RedirectResponse
    {
        abort_unless($shop->is_active, 404);

        return redirect()->to($shop->publicUrl());
    }

    public function manifest(Shop $shop): Response
    {
        abort_unless($shop->is_active, 404);

        $version = $shop->updated_at?->timestamp ?? 1;
        $openLabel = $shop->catalog_type === 'restaurant' ? 'فتح قائمة الطعام' : 'فتح المحل';
        $manifest = [
            'id' => '/shop-app/customers/'.$shop->id,
            'name' => $shop->name,
            'short_name' => Str::limit($shop->name, 24, ''),
            'description' => $shop->description ?: 'التطبيق الرسمي لمحل '.$shop->name,
            'lang' => app()->getLocale(),
            'dir' => in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr',
            'start_url' => route('shop-app.launch', $shop, false),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#05090d',
            'theme_color' => '#071820',
            'categories' => $shop->catalog_type === 'restaurant'
                ? ['food', 'lifestyle']
                : ['shopping', 'lifestyle'],
            'icons' => collect([192, 512])->map(fn (int $size) => [
                'src' => route('merchant-app.icon', ['shop' => $shop, 'size' => $size, 'v' => $version], false),
                'sizes' => $size.'x'.$size,
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ])->all(),
            'shortcuts' => [[
                'name' => $openLabel,
                'url' => route('shop-app.launch', $shop, false),
                'icons' => [[
                    'src' => route('merchant-app.icon', ['shop' => $shop, 'size' => 192, 'v' => $version], false),
                    'sizes' => '192x192',
                ]],
            ]],
        ];

        return response(
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            200,
            [
                'Content-Type' => 'application/manifest+json; charset=UTF-8',
                'Cache-Control' => 'public, max-age=300',
            ],
        );
    }
}
