<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FrontAssetController extends Controller
{
    public function show(Request $request, string $file): BinaryFileResponse
    {
        $types = ['script.js' => 'application/javascript', 'style.css' => 'text/css', 'shop-stories.js' => 'application/javascript', 'shop-stories.css' => 'text/css'];
        abort_unless(isset($types[$file]), 404);

        // Read the Git-managed assets even when hosting uses a separate public_html.
        $path = base_path('public/'.$file);
        abort_unless(is_file($path), 404);

        $response = response()->file($path, [
            'Content-Type' => $types[$file].'; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
        ]);
        $response->setEtag(hash_file('sha256', $path));
        $response->isNotModified($request);

        return $response;
    }
}
