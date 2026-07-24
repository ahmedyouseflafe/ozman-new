<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Services\ShopOwnerAccountService;
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

        return $this->redirectAfterLogin($request);
    }

    public function showMerchantLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()?->isShopOwner()
                ? redirect()->route('home')
                : redirect()->route('dashboard');
        }

        return view('front.merchant_login', [
            'redirectTo' => $this->safeMerchantRedirect($request->query('redirect')),
        ]);
    }

    public function merchantLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string', 'max:1000'],
        ]);

        $remember = $request->boolean('remember');
        unset($credentials['redirect']);

        if (! Auth::attempt(array_merge($credentials, [
            'role' => 'shop_owner',
            'is_active' => true,
        ]), $remember)) {
            return back()
                ->withErrors(['email' => 'بيانات الدخول غير صحيحة أو حساب المتجر غير فعال.'])
                ->onlyInput('email', 'redirect');
        }

        $shop = Auth::user()?->shops()
            ->where('is_active', true)
            ->whereNotNull('distributor_id')
            ->first();

        if (! $shop) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'هذا الحساب غير مرتبط بمتجر فعال وموزع. تواصل مع الموزع.'])
                ->onlyInput('email', 'redirect');
        }

        $request->session()->regenerate();
        $request->session()->put('merchant_shop_id', $shop->id);

        return redirect()->to($this->safeMerchantRedirect($request->input('redirect')));
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isShopOwner()) {
            $shop = $user->shops()->first();

            if ($shop) {
                app(ShopOwnerAccountService::class)->resolve($shop);
                return redirect()->route('shops.show', $shop);
            }
        }

        if ($user?->isAgent() || $user?->isDistributor()) {
            foreach (['front-orders.index', 'raffle-cards.index', 'distributors.marketers.index', 'products', 'categories', 'dashboard.main'] as $routeName) {
                if ($user->canAccessRouteName($routeName)) {
                    return redirect()->route($routeName);
                }
            }

            return view('admin.dashboard');
        }

        if ($user?->isMarketer()) {
            if ($user->hasAssignedPermissions()) {
                foreach (['front-orders.index', 'reward-wheels.marketer.play', 'reward-wheels.marketer.direct.play'] as $routeName) {
                    if ($user->canAccessRouteName($routeName)) {
                        return redirect()->route($routeName);
                    }
                }

                return view('admin.dashboard');
            }

            if ($user->distributorMarketerProfiles()->exists()) {
                return redirect()->route('front-orders.index');
            }

            return redirect()->route('reward-wheels.marketer.play');
        }

        return view('admin.dashboard');
    }

    public function enterShopDashboard(Request $request, Shop $shop): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin?->isSuperAdmin(), 403);

        $owner = app(ShopOwnerAccountService::class)->resolve($shop);

        $adminId = $admin->id;
        $adminName = $admin->name;

        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put([
            'impersonator_admin_id' => $adminId,
            'impersonator_admin_name' => $adminName,
            'impersonated_shop_id' => $shop->id,
            'current_shop_id' => $shop->id,
        ]);

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'أنت الآن داخل لوحة تحكم متجر ' . $shop->name . '.');
    }

    public function returnFromShopDashboard(Request $request): RedirectResponse
    {
        $adminId = (int) $request->session()->get('impersonator_admin_id');
        abort_unless($adminId > 0, 403);

        $admin = User::query()
            ->whereKey($adminId)
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->firstOrFail();

        Auth::login($admin);
        $request->session()->regenerate();
        $request->session()->forget([
            'impersonator_admin_id',
            'impersonator_admin_name',
            'impersonated_shop_id',
            'current_shop_id',
        ]);

        return redirect()
            ->route('shops')
            ->with('success', 'تم الرجوع إلى لوحة تحكم الإدارة.');
    }


    private function redirectAfterLogin(Request $request): RedirectResponse
    {
        $dashboard = $this->dashboard($request);

        return $dashboard instanceof RedirectResponse
            ? $dashboard
            : redirect()->intended(route('dashboard'));
    }

    private function safeMerchantRedirect(?string $redirect): string
    {
        if (! filled($redirect)) {
            return route('home');
        }

        if (parse_url($redirect, PHP_URL_HOST) !== null) {
            return route('home');
        }

        $path = parse_url($redirect, PHP_URL_PATH);
        $query = parse_url($redirect, PHP_URL_QUERY);

        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return route('home');
        }

        return $path . ($query ? '?' . $query : '');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
