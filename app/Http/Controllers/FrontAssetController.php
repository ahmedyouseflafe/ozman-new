<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FrontAssetController extends Controller
{
    private const REAL_ESTATE_SERVICE_IMAGES = [
        'mobile-home.webp',
        'bathroom-unit.webp',
        'guard-room.webp',
        'caravan.webp',
        'custom-build.webp',
    ];

    public function show(Request $request, string $file): BinaryFileResponse
    {
        $types = [
            'script.js' => 'application/javascript',
            'style.css' => 'text/css',
            'shop-stories.js' => 'application/javascript',
            'shop-stories.css' => 'text/css',
            'merchant-pwa.js' => 'application/javascript',
            'merchant-pwa-sw.js' => 'application/javascript',
            'shop-pwa.js' => 'application/javascript',
            'offer-notifications.js' => 'application/javascript',
        ];
        abort_unless(isset($types[$file]), 404);

        // Read the Git-managed assets even when hosting uses a separate public_html.
        $path = base_path('public/'.$file);
        abort_unless(is_file($path), 404);

        $headers = [
            'Content-Type' => $types[$file].'; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
        ];

        if ($file === 'merchant-pwa-sw.js') {
            $headers['Service-Worker-Allowed'] = '/';
        }

        $response = response()->file($path, $headers);
        $response->setEtag(hash_file('sha256', $path));
        $response->isNotModified($request);

        return $response;
    }

    public function realEstateServiceImage(Request $request, string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, self::REAL_ESTATE_SERVICE_IMAGES, true), 404);

        // The production host keeps public_html separate from the Git-managed
        // public directory, so serve these catalog images through Laravel when
        // they are not copied into the web root.
        $path = base_path('public/images/real-estate-services/'.$file);
        abort_unless(is_file($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
        $response->setEtag(hash_file('sha256', $path));
        $response->isNotModified($request);

        return $response;
    }
}
