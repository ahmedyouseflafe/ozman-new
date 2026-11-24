<?php

namespace App\Http\Controllers;

use App\Services\StorePoints;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** The till QR opened with a phone's camera (not inside the app): sign in if needed, then the points are added. */
class StorePointsClaimController extends Controller
{
    public function __invoke(Request $request, string $token, StorePoints $points)
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('community.login');
        }
        try {
            $code = $points->claim($request->user(), $token);

            return response()->view('front.store_points_claim', ['ok' => true, 'points' => $code->points, 'shop' => $code->shop]);
        } catch (ValidationException $e) {
            return response()->view('front.store_points_claim', ['ok' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }
    }
}
