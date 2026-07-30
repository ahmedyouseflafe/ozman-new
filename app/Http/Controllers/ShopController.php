<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\ShopSocial;
use App\Services\ShopOwnerAccountService;

class ShopController extends Controller
{
    public function index(): View
    {
        $shops = Shop::query()
            ->where('slug', '!=', 'ozman')
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $query->whereIn('id', $this->ownedShopIds()))
            ->with('distributor:id,name')
            ->withCount('products')
            ->latest()
            ->get()
            ->map(function (Shop $shop) {
                $shop->status_label = $shop->is_active ? 'نشط' : 'غير نشط';
                $shop->status_class = $shop->is_active ? 'tag-g' : 'tag-r';

                return $shop;
            });

        return view('admin.shop.shops', [
            'shops' => $shops,
            'shopsCount' => $shops->count(),
            'activeShopsCount' => $shops->where('is_active', true)->count(),
            'inactiveShopsCount' => $shops->where('is_active', false)->count(),
            'assignableDistributors' => $this->isSuperAdmin()
                ? Distributor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone'])
                : collect(),
        ]);
    }

    public function assignDistributor(Request $request, Shop $shop): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'distributor_id' => ['nullable', 'integer', Rule::exists('distributors', 'id')->where('is_active', true)],
        ]);
        $distributorId = filled($data['distributor_id'] ?? null) ? (int) $data['distributor_id'] : null;

        $marketerBelongsToDistributor = $shop->distributor_marketer_id
            && DistributorMarketer::query()
                ->whereKey($shop->distributor_marketer_id)
                ->where('distributor_id', $distributorId)
                ->exists();

        $shop->update([
            'distributor_id' => $distributorId,
            'distributor_marketer_id' => $marketerBelongsToDistributor
                ? $shop->distributor_marketer_id
                : null,
        ]);

        $message = $distributorId
            ? 'تم ربط المتجر بالموزع بنجاح.'
            : 'تم إلغاء ربط المتجر بالموزع.';

        return back()->with('status', $message);
    }

    public function editOwnerPermissions(Request $request, Shop $shop): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $owner = app(ShopOwnerAccountService::class)->resolve($shop);
        $owner->load('employeePermissions');
        $allowed = $this->ownerPermissionsFor($shop);
        $groups = collect(config('employee_permissions.groups', []))
            ->map(function (array $group) use ($allowed) {
                $group['permissions'] = collect($group['permissions'] ?? [])
                    ->only($allowed)
                    ->all();
                return $group;
            })
            ->filter(fn (array $group) => ! empty($group['permissions']))
            ->all();

        return view('admin.employees.permissions', [
            'employee' => $owner,
            'permissionGroups' => $groups,
            'selectedPermissions' => $owner->employeePermissions->pluck('permission')->all(),
            'pageTitle' => 'صلاحيات لوحة المتجر',
            'headerTitle' => 'صلاحيات لوحة المتجر',
            'description' => 'حدد بالضبط الأقسام والعمليات التي يراها صاحب المتجر. عند الدخول من زر لوحة المتجر ستشاهد نفس هذه الصلاحيات.',
            'formAction' => route('shops.permissions.update', $shop),
            'backUrl' => route('shops'),
        ]);
    }

    public function updateOwnerPermissions(Request $request, Shop $shop): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $allowed = $this->ownerPermissionsFor($shop);
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($allowed)],
        ]);
        $permissions = collect($data['permissions'] ?? [])
            ->merge(config('shop_owner_permissions.required', []))
            ->merge(config("shop_owner_permissions.catalog_type_required.{$shop->catalog_type}", []))
            ->unique()
            ->values();
        $owner = app(ShopOwnerAccountService::class)->resolve($shop, false);

        $owner->employeePermissions()->delete();
        foreach ($permissions as $permission) {
            $owner->employeePermissions()->create(['permission' => $permission]);
        }

        return redirect()
            ->route('shops')
            ->with('status', 'تم حفظ صلاحيات لوحة متجر ' . $shop->name . ' بنجاح.');
    }

    private function ownerPermissionsFor(Shop $shop): array
    {
        return collect(config('shop_owner_permissions.allowed', []))
            ->merge(config("shop_owner_permissions.catalog_type_permissions.{$shop->catalog_type}", []))
            ->unique()
            ->values()
            ->all();
    }

    public function show(Shop $shop): View
    {
        $this->authorizeShopAccess($shop);

        $shop->load([
            'social',
            'categories' => fn($query) => $query->withCount('products')->latest(),
            'agents' => fn($query) => $query->latest(),
            'distributors' => fn($query) => $query->latest(),
            'advertisements' => fn($query) => $query->latest(),
        ])->loadCount(['products', 'categories', 'agents', 'distributors', 'advertisements']);

        $publicShopUrl = $shop->catalog_type === 'restaurant'
            ? route('restaurant.menu', $shop)
            : route('front.shop.slug', $shop);
        $shopQrCodeSvg = (new Writer(new ImageRenderer(
            new RendererStyle(360, 2),
            new SvgImageBackEnd()
        )))->writeString($publicShopUrl);
        $shopQrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($shopQrCodeSvg);
        $socialLinks = collect([
            ['label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'value' => optional($shop->social)->facebook],
            ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'value' => optional($shop->social)->instagram],
            ['label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'value' => optional($shop->social)->tiktok],
            ['label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'value' => optional($shop->social)->telegram],
            ['label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'value' => optional($shop->social)->snapchat],
            ['label' => 'Twitter / X', 'icon' => 'ti-brand-x', 'value' => optional($shop->social)->twitter],
            ['label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'value' => optional($shop->social)->youtube],
            ['label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'value' => optional($shop->social)->whatsapp],
        ])
            ->filter(fn($link) => filled($link['value']))
            ->map(function ($link) {
                $link['url'] = Str::startsWith($link['value'], ['http://', 'https://'])
                    ? $link['value']
                    : 'https://' . ltrim($link['value'], '/');

                return $link;
            })
            ->values();

        $paymentMethodLabels = [
            'bank_transfer' => 'تحويل بنكي',
            'wallet' => 'محفظة إلكترونية',
            'cash' => 'كاش / عند الاستلام',
            'other' => 'أخرى',
        ];

        return view('admin.shop.shops_list', compact(
            'shop',
            'publicShopUrl',
            'shopQrCodeDataUri',
            'socialLinks',
            'paymentMethodLabels'
        ));
    }

    public function ozman(): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $shop = Shop::query()->firstOrCreate(
            ['slug' => 'ozman'],
            [
                'user_id' => $this->resolveOwnerId(),
                'name' => 'Ozman',
                'description' => 'المتجر الأساسي لقسم Ozman العلوي.',
                'is_active' => true,
            ]
        );

        $shop->social()->firstOrCreate(['shop_id' => $shop->id]);

        return redirect()->route('shops.show', $shop);
    }

    public function create(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        return view('admin.shop.shops_create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $this->validatedData($request);
        $data['catalog_type'] = $data['catalog_type'] ?? 'general';
        $owner = $this->resolveShopOwner($request, $data);
        $data['user_id'] = $owner->id;
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_ozman_products'] = $request->boolean('show_ozman_products');

        if (Auth::user()?->isDistributor()) {
            $distributor = $this->currentDistributorProfile();
            abort_unless($distributor, 403);
            $data['distributor_id'] = $distributor->id;
        }

        if (Auth::user()?->isMarketer()) {
            $marketer = $this->currentMarketerProfile();
            abort_unless($marketer, 403);
            $data['distributor_marketer_id'] = $marketer->id;
            $data['distributor_id'] = $marketer->distributor_id;
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeUpload($request, 'logo', 'shops/logos');
        }

        if ($request->hasFile('banner')) {
            $data['banner'] = $this->storeUpload($request, 'banner', 'shops/banners');
        }

        $shop = Shop::create($data);
        if ($owner->isShopOwner()) {
            app(ShopOwnerAccountService::class)->resolve($shop);
        }

        ShopSocial::create([
            'shop_id'   => $shop->id,
            'facebook'  => $request->facebook,
            'instagram' => $request->instagram,
            'tiktok'    => $request->tiktok,
            'telegram'  => $request->telegram,
            'snapchat'  => $request->snapchat,
            'twitter'   => $request->twitter,
            'youtube'   => $request->youtube,
            'whatsapp'  => $request->social_whatsapp,
        ]);

        $this->notifySuperAdmin(
            'shop_created',
            $shop,
            'تم تسجيل متجر جديد',
            "تم إنشاء متجر جديد داخل النظام: {$shop->name}",
            route('shops.show', $shop),
            ['shop_id' => $shop->id, 'owner_id' => $owner->id]
        );

        return redirect()
            ->route('shops')
            ->with('status', 'تم إضافة المتجر بنجاح.');
    }

   public function edit($id): View
{
    $shop = Shop::with(['social', 'user'])->findOrFail($id);
    $this->authorizeShopAccess($shop);

    return view('admin.shop.shops_edit', compact('shop'));
}

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShopAccess($shop);

        $data = $this->validatedData($request, $shop);
        $data['catalog_type'] = $data['catalog_type'] ?? ($shop->catalog_type ?: 'general');
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $shop);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_ozman_products'] = $request->boolean('show_ozman_products');

        if ($this->isSuperAdmin()) {
            $this->updateShopOwner($request, $shop, $data);
        }

        if ($request->hasFile('logo')) {
            $this->deleteUpload($shop->logo);
            $data['logo'] = $this->storeUpload($request, 'logo', 'shops/logos');
        }

        if ($request->hasFile('banner')) {
            $this->deleteUpload($shop->banner);
            $data['banner'] = $this->storeUpload($request, 'banner', 'shops/banners');
        }

        $shop->update($data);
        $shop->social()->updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'facebook'  => $request->facebook,
                'instagram' => $request->instagram,
                'tiktok'    => $request->tiktok,
                'telegram'  => $request->telegram,
                'snapchat'  => $request->snapchat,
                'twitter'   => $request->twitter,
                'youtube'   => $request->youtube,
                'whatsapp'  => $request->social_whatsapp,
            ]
        );

        return redirect()
            ->route('shops')
            ->with('status', 'تم تحديث بيانات المتجر بنجاح.');
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $this->authorizeShopAccess($shop);

        $this->deleteUpload($shop->logo);
        $this->deleteUpload($shop->banner);
        $shop->delete();

        return redirect()
            ->route('shops')
            ->with('status', 'تم حذف المتجر بنجاح.');
    }

    private function validatedData(Request $request, ?Shop $shop = null): array
    {
        $authUser = Auth::user();
        $usesCurrentUserAsOwner = ! $shop && $authUser?->isAgent();
        $ownerPasswordRules = $shop || $usesCurrentUserAsOwner
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6', 'confirmed'];

        if ($shop && ! $request->filled('owner_password_confirmation')) {
            $request->merge(['owner_password' => null]);
        } elseif ($shop && $request->filled('owner_password')) {
            $ownerPasswordRules[] = 'confirmed';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'catalog_type' => ['nullable', Rule::in(array_keys(config('catalog_types', [])))],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('shops', 'slug')->ignore($shop?->id),
            ],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_provider' => ['nullable', 'string', 'max:255'],
            'payment_account_holder' => ['nullable', 'string', 'max:255'],
            'payment_account_number' => ['nullable', 'string', 'max:255'],
            'payment_iban' => ['nullable', 'string', 'max:255'],
            'payment_wallet_number' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
            'owner_email' => [
                $shop || $usesCurrentUserAsOwner ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($shop?->user_id),
            ],
            'owner_password' => $ownerPasswordRules,
            'owner_password_confirmation' => ['nullable', 'string', 'min:6'],
        ]);
    }

    private function resolveShopOwner(Request $request, array $data): User
    {
        $user = Auth::user();

        if ($user?->isAgent()) {
            return $user;
        }

        return User::create([
            'name' => $data['name'],
            'email' => $request->input('owner_email'),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($request->input('owner_password')),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
    }

    private function currentMarketerProfile(): ?DistributorMarketer
    {
        $user = Auth::user();

        if (! $user?->isMarketer()) {
            return null;
        }

        return DistributorMarketer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->oldest()
            ->first();
    }

    private function currentDistributorProfile(): ?Distributor
    {
        $user = Auth::user();

        if (! $user?->isDistributor()) {
            return null;
        }

        return Distributor::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->oldest()
            ->first();
    }

    private function updateShopOwner(Request $request, Shop $shop, array $data): void
    {
        if ($shop->user?->isSuperAdmin()) {
            if (! $request->filled('owner_email') || $request->input('owner_email') === $shop->user->email) {
                return;
            }

            $ownerData = [
                'name' => $data['name'],
                'email' => $request->input('owner_email'),
                'phone' => $data['phone'] ?? null,
                'password' => $request->filled('owner_password')
                    ? Hash::make($request->input('owner_password'))
                    : Hash::make(Str::random(16)),
                'role' => 'shop_owner',
                'is_active' => true,
            ];

            $owner = User::create($ownerData);
            $shop->update(['user_id' => $owner->id]);

            return;
        }

        $ownerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'role' => 'shop_owner',
            'is_active' => true,
        ];

        if ($request->filled('owner_email')) {
            $ownerData['email'] = $request->input('owner_email');
        }

        if (
            $request->filled('owner_password')
            && $request->filled('owner_password_confirmation')
            && $request->input('owner_password') === $request->input('owner_password_confirmation')
        ) {
            $ownerData['password'] = Hash::make($request->input('owner_password'));
        }

        if ($shop->user) {
            $shop->user->update($ownerData);
            return;
        }

        $ownerData['email'] = $ownerData['email'] ?? $data['email'] ?? 'shop-' . $shop->id . '@ozman.local';
        $ownerData['password'] = $ownerData['password'] ?? Hash::make(Str::random(16));

        $owner = User::create($ownerData);
        $shop->update(['user_id' => $owner->id]);
    }

    private function resolveOwnerId(): int
    {
        if (Auth::id()) {
            return Auth::id();
        }

        $user = User::query()->first();

        if ($user) {
            return $user->id;
        }

        return User::create([
            'name' => 'Ozman Admin',
            'email' => 'admin@ozman.local',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ])->id;
    }

    private function uniqueSlug(string $value, ?Shop $shop = null): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'shop';
        $slug = $base;
        $counter = 2;

        while (
            Shop::query()
            ->where('slug', $slug)
            ->when($shop, fn($query) => $query->where('id', '!=', $shop->id))
            ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function storeUpload(Request $request, string $field, string $directory): string
    {
        $path = $request->file($field)->store($directory, 'public');

        return 'storage/' . $path;
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path) {
            return;
        }

        $path = Str::of($path)->replaceStart('storage/', '')->toString();
        Storage::disk('public')->delete($path);
    }
}
