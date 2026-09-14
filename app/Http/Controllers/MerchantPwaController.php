<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\WebPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MerchantPwaController extends Controller
{
    public function index(Request $request, WebPushService $webPush): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('merchant.login', [
                'redirect' => route('merchant-app.index', absolute: false),
            ]);
        }

        abort_unless($request->user()->isShopOwner(), 403);
        $shop = $this->ownerShop($request);
        abort_unless($shop, 403, 'هذا الحساب غير مرتبط بمحل فعال.');

        $request->session()->put([
            'merchant_shop_id' => $shop->id,
            'current_shop_id' => $shop->id,
        ]);

        return view('front.merchant_app', [
            'shop' => $shop,
            'vapidPublicKey' => $webPush->publicKey(),
        ]);
    }

    public function launch(Request $request, Shop $shop): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('merchant.login', [
                'redirect' => route('merchant-app.launch', $shop, false),
            ]);
        }

        abort_unless(
            $request->user()->isShopOwner()
            && $shop->is_active
            && $request->user()->shops()->whereKey($shop->id)->exists(),
            403,
        );

        $request->session()->put([
            'merchant_shop_id' => $shop->id,
            'current_shop_id' => $shop->id,
        ]);

        return redirect()->to($shop->publicUrl());
    }

    public function manifest(Shop $shop): Response
    {
        abort_unless($shop->is_active, 404);
        $version = $shop->updated_at?->timestamp ?? 1;
        $manifest = [
            'id' => '/merchant-app/shops/'.$shop->id,
            'name' => $shop->name,
            'short_name' => Str::limit($shop->name, 24, ''),
            'description' => $shop->description ?: 'التطبيق الرسمي لمحل '.$shop->name,
            'lang' => app()->getLocale(),
            'dir' => in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr',
            'start_url' => route('merchant-app.launch', $shop, false),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#05090d',
            'theme_color' => '#071820',
            'categories' => $shop->catalog_type === 'restaurant' ? ['food', 'business'] : ['business', 'shopping'],
            'icons' => collect([192, 512])->map(fn (int $size) => [
                'src' => route('merchant-app.icon', ['shop' => $shop, 'size' => $size, 'v' => $version], false),
                'sizes' => $size.'x'.$size,
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ])->all(),
            'shortcuts' => [
                [
                    'name' => 'فتح المحل',
                    'url' => route('merchant-app.launch', $shop, false),
                    'icons' => [[
                        'src' => route('merchant-app.icon', ['shop' => $shop, 'size' => 192, 'v' => $version], false),
                        'sizes' => '192x192',
                    ]],
                ],
                [
                    'name' => 'إدارة المحل',
                    'url' => route($shop->dashboardRouteName(), $shop, false),
                ],
            ],
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

    public function icon(Shop $shop, int $size): Response
    {
        abort_unless($shop->is_active && in_array($size, [192, 512], true), 404);
        $sourcePath = $this->shopLogoPath($shop);
        $source = @imagecreatefromstring((string) file_get_contents($sourcePath));

        if (! $source) {
            abort(404);
        }

        $canvas = imagecreatetruecolor($size, $size);
        $background = imagecolorallocate($canvas, 3, 10, 14);
        imagefill($canvas, 0, 0, $background);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $padding = (int) round($size * .1);
        $available = $size - ($padding * 2);
        $ratio = min($available / max($sourceWidth, 1), $available / max($sourceHeight, 1));
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));
        $targetX = (int) round(($size - $targetWidth) / 2);
        $targetY = (int) round(($size - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagepng($canvas, null, 8);
        $png = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Length' => (string) strlen($png),
        ]);
    }

    private function ownerShop(Request $request): ?Shop
    {
        $shops = $request->user()->shops()->where('is_active', true)->get();
        $preferredIds = collect([
            $request->session()->get('merchant_shop_id'),
            $request->session()->get('current_shop_id'),
        ])->filter()->map(fn ($id) => (int) $id);

        return $preferredIds
            ->map(fn (int $id) => $shops->firstWhere('id', $id))
            ->first(fn ($candidate) => $candidate instanceof Shop)
            ?: $shops->first();
    }

    private function shopLogoPath(Shop $shop): string
    {
        $logo = str_replace('\\', '/', ltrim((string) $shop->logo, '/'));
        $candidates = [];

        if ($logo !== '' && ! str_contains($logo, '..') && ! Str::startsWith($logo, ['http://', 'https://'])) {
            $candidates[] = public_path($logo);
            $candidates[] = public_path('storage/'.Str::after($logo, 'storage/'));
            $candidates[] = storage_path('app/public/'.Str::after($logo, 'storage/'));
        }

        $candidates[] = public_path('ozman-favicon.png');

        return collect($candidates)->first(fn (string $path) => is_file($path))
            ?? public_path('ozman-favicon.png');
    }
}
