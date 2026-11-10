<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\CommunityUsername;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Mobile login: the same accounts as the website, with a Bearer token instead of a session. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        $login = trim($data['login']);
        // Email or @username, like the website's community login.
        $user = str_contains($login, '@') && ! str_starts_with($login, '@')
            ? User::where('email', mb_strtolower($login))->first()
            : User::where('username', CommunityUsername::normalize($login))->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => __('الإيميل/اسم المستخدم أو كلمة المرور غير صحيحة.'), 'code' => 'invalid_credentials'], 422);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile', 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => Presenter::user($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['logged_out' => true]);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        [$token, $plain] = ApiToken::issue($user, $device);

        return response()->json([
            'token' => $plain,
            'expires_at' => $token->expires_at->toIso8601String(),
            'user' => Presenter::user($user),
        ], $status);
    }
}
