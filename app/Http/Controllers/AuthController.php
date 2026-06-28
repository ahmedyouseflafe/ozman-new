<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            return back()
                ->withErrors(['email' => 'بيانات الدخول غير صحيحة أو الحساب غير فعال.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->isShopOwner()) {
            $shop = Auth::user()->shops()->first();

            if ($shop) {
                return redirect()->route('shops.show', $shop);
            }
        }

        if (Auth::user()->isAgent() || Auth::user()->isDistributor()) {
            $user = Auth::user();

            foreach (['products', 'categories', 'dashboard.main', 'dashboard'] as $routeName) {
                if ($user->canAccessRouteName($routeName)) {
                    return redirect()->route($routeName);
                }
            }

            return redirect()->route('products');
        }

        if (Auth::user()->isMarketer()) {
            return redirect()->route('reward-wheels.marketer.play');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
