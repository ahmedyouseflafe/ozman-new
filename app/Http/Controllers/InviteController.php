<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Invites;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Str;

class InviteController extends Controller
{
    /** The app's "invite a friend" screen. */
    public function show(Request $request, Invites $invites): JsonResponse
    {
        return response()->json(['invite' => $invites->summary($request->user())]);
    }

    /** A friend opened the invite link: remember the code for 30 days, then go to sign up. */
    public function open(string $code): RedirectResponse
    {
        $code = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $code), 0, 12));
        $valid = $code !== '' && User::where('invite_code', $code)->exists();

        $redirect = redirect()->route('community.login', ['tab' => 'register']);

        return $valid ? $redirect->withCookie(cookie(Invites::COOKIE, $code, 60 * 24 * 30)) : $redirect;
    }
}
