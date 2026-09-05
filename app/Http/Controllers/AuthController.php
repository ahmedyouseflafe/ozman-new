<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\Shop;
use App\Models\User;
use App\Rules\ValidPhoneNumber;
use App\Services\ShopOwnerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
        $this->captureMerchantReferral($request);

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('front.merchant_login', [
            'redirectTo' => $this->safeMerchantRedirect($request->query('redirect')),
            'canRegister' => is_array($request->session()->get('merchant_referral')),
        ]);
    }

    public function merchantLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string', 'max:1000'],
        ]);

        unset($credentials['redirect']);

        if (! Auth::attempt(array_merge($credentials, [
            'role' => 'shop_owner',
            'is_active' => true,
        ]), true)) {
            return back()
                ->withErrors(['email' => 'بيانات الدخول غير صحيحة أو حساب المتجر غير فعال.'])
                ->onlyInput('email', 'redirect');
        }

        $shop = Auth::user()?->shops()
            ->where('is_active', true)
            ->first();

        if (! $shop) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'هذا الحساب غير مرتبط بمتجر فعال وموزع. تواصل مع الموزع.'])
                ->onlyInput('email', 'redirect');
        }

        $this->applyMerchantReferral($request, $shop);
        $shop->refresh();

        $linkedDistributor = $shop->distributorMarketer?->distributor ?: $shop->distributor;
        if (! $linkedDistributor?->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'هذا الحساب غير مرتبط بمتجر فعال وموزع. امسح QR الموزع أو المروّج ثم سجّل الدخول.'])
                ->onlyInput('email', 'redirect');
        }

        $request->session()->regenerate();
        $request->session()->put('merchant_shop_id', $shop->id);
        $request->session()->forget('merchant_referral');

        return redirect()->to($this->safeMerchantRedirect($request->input('redirect')));
    }

    public function showMerchantRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()?->isShopOwner()
                ? redirect()->route('home')
                : redirect()->route('dashboard');
        }

        if (! $this->resolvedMerchantReferral($request)) {
            return redirect()
                ->route('merchant.login')
                ->withErrors(['email' => 'إنشاء متجر جديد متاح بعد مسح QR موزع أو مروّج فعال.']);
        }

        return view('front.merchant_register', [
            'redirectTo' => $this->safeMerchantRedirect($request->query('redirect')),
        ]);
    }

    public function merchantRegister(Request $request): RedirectResponse
    {
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));
        $request->merge(['email' => $normalizedEmail]);
        $orphanOwner = filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)
            ? User::query()->where('email', $normalizedEmail)->first()
            : null;
        if (! $orphanOwner?->isShopOwner() || $orphanOwner->shops()->exists()) {
            $orphanOwner = null;
        }

        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'shop_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($orphanOwner?->id)],
            'phone' => ['required', 'string', 'max:60', new ValidPhoneNumber],
            'whatsapp' => ['nullable', 'string', 'max:60', new ValidPhoneNumber],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'redirect' => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique' => 'هذا البريد مرتبط بحساب موجود فعلياً. جرّب تسجيل الدخول أو استخدم بريداً آخر.',
        ]);

        $referral = $this->resolvedMerchantReferral($request);
        if (! $referral) {
            return back()
                ->withErrors(['email' => 'انتهت أو أصبحت إحالة QR غير صالحة. امسح QR مرة أخرى.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        // حذف المتجر قديماً كان يترك حساب صاحبه بلا متجر ويحجز بريده.
        // لا نحذف السجل اليتيم إلا بعد التأكد من صلاحية إحالة QR.
        $orphanOwner?->delete();

        $logoPath = $request->hasFile('logo')
            ? 'storage/'.$request->file('logo')->store('shops/logos', 'public')
            : null;

        [$user, $shop] = DB::transaction(function () use ($data, $referral, $logoPath) {
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'shop_owner',
                'is_active' => true,
            ]);

            $shop = Shop::create([
                'user_id' => $user->id,
                'distributor_id' => $referral['distributor_id'],
                'distributor_marketer_id' => $referral['distributor_marketer_id'],
                'name' => $data['shop_name'],
                'slug' => $this->uniqueShopSlug($data['shop_name']),
                'catalog_type' => 'general',
                'logo' => $logoPath,
                'phone' => $data['phone'],
                'whatsapp' => ($data['whatsapp'] ?? null) ?: $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_active' => true,
                // المتجر المسجل من QR هو عميل للموزع/المروج، لذلك يجب أن
                // يرى كتالوج Ozman الأساسي فور دخوله بدون نسخ ملكية المنتجات.
                'show_ozman_products' => true,
            ]);

            return [$user, $shop];
        });

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('merchant_shop_id', $shop->id);
        $request->session()->forget('merchant_referral');

        return redirect()
            ->to($this->safeMerchantRedirect($data['redirect'] ?? null))
            ->with('status', 'تم إنشاء حساب المتجر وربطه بنجاح.');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isShopOwner()) {
            $shop = $user->shops()->first();

            if ($shop) {
                app(ShopOwnerAccountService::class)->resolve($shop);

                if ($shop->catalog_type === 'real_estate') {
                    return redirect()->route('real-estate.dashboard', $shop);
                }

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
            ->with('success', 'أنت الآن داخل لوحة تحكم متجر '.$shop->name.'.');
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
            return route('dashboard');
        }

        if (parse_url($redirect, PHP_URL_HOST) !== null) {
            return route('home');
        }

        $path = parse_url($redirect, PHP_URL_PATH);
        $query = parse_url($redirect, PHP_URL_QUERY);

        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return route('home');
        }

        return $path.($query ? '?'.$query : '');
    }

    private function captureMerchantReferral(Request $request): void
    {
        $type = $request->query('referrer_type');
        $reference = $request->query('referrer');

        if (! in_array($type, ['distributor', 'marketer'], true) || ! filled($reference)) {
            return;
        }

        // Accept legacy absolute signatures and the proxy-safe relative signatures
        // used by newly generated QR codes. Relative signatures remain valid when
        // the hosting proxy changes http to https or normalizes the domain.
        abort_unless(
            $request->hasValidSignature() || $request->hasValidSignature(absolute: false),
            403
        );

        if ($type === 'marketer') {
            $marketer = DistributorMarketer::query()
                ->where('tracking_code', $reference)
                ->where('is_active', true)
                ->whereHas('distributor', fn ($query) => $query->where('is_active', true))
                ->firstOrFail();

            $request->session()->put('merchant_referral', [
                'distributor_id' => $marketer->distributor_id,
                'distributor_marketer_id' => $marketer->id,
            ]);

            return;
        }

        $distributor = Distributor::query()
            ->whereKey((int) $reference)
            ->where('is_active', true)
            ->firstOrFail();

        $request->session()->put('merchant_referral', [
            'distributor_id' => $distributor->id,
            'distributor_marketer_id' => null,
        ]);
    }

    private function applyMerchantReferral(Request $request, Shop $shop): void
    {
        $referral = $this->resolvedMerchantReferral($request);
        if (! $referral) {
            return;
        }

        $shop->update($referral);
    }

    private function resolvedMerchantReferral(Request $request): ?array
    {
        $referral = $request->session()->get('merchant_referral');
        if (! is_array($referral)) {
            return null;
        }

        $marketerId = (int) ($referral['distributor_marketer_id'] ?? 0);
        if ($marketerId > 0) {
            $marketer = DistributorMarketer::query()
                ->whereKey($marketerId)
                ->where('is_active', true)
                ->whereHas('distributor', fn ($query) => $query->where('is_active', true))
                ->first();

            return $marketer ? [
                'distributor_id' => $marketer->distributor_id,
                'distributor_marketer_id' => $marketer->id,
            ] : null;
        }

        $distributor = Distributor::query()
            ->whereKey((int) ($referral['distributor_id'] ?? 0))
            ->where('is_active', true)
            ->first();

        return $distributor ? [
            'distributor_id' => $distributor->id,
            'distributor_marketer_id' => null,
        ] : null;
    }

    private function uniqueShopSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $suffix = 2;

        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
