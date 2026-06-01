<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\ShopSocial;

class ShopController extends Controller
{
    public function index(): View
    {
        $shops = Shop::query()
            ->where('slug', '!=', 'ozman')
            ->when(! $this->isSuperAdmin(), fn($query) => $query->whereIn('id', $this->ownedShopIds()))
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
        ]);
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

        return view('admin.shop.shops_list', compact('shop'));
    }

    public function ozman(): RedirectResponse
    {
        abort_unless($this->isSuperAdmin(), 403);

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
        abort_unless($this->isSuperAdmin(), 403);

        return view('admin.shop.shops_create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isSuperAdmin(), 403);

        $data = $this->validatedData($request);
        $owner = $this->resolveShopOwner($request, $data);
        $data['user_id'] = $owner->id;
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeUpload($request, 'logo', 'shops/logos');
        }

        if ($request->hasFile('banner')) {
            $data['banner'] = $this->storeUpload($request, 'banner', 'shops/banners');
        }

        $shop = Shop::create($data);

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
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $shop);
        $data['is_active'] = $request->boolean('is_active');

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
        abort_unless($this->isSuperAdmin(), 403);

        $this->deleteUpload($shop->logo);
        $this->deleteUpload($shop->banner);
        $shop->delete();

        return redirect()
            ->route('shops')
            ->with('status', 'تم حذف المتجر بنجاح.');
    }

    private function validatedData(Request $request, ?Shop $shop = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
            'owner_email' => [
                $shop ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($shop?->user_id),
            ],
            'owner_password' => [$shop ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    private function resolveShopOwner(Request $request, array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $request->input('owner_email'),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($request->input('owner_password')),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
    }

    private function updateShopOwner(Request $request, Shop $shop, array $data): void
    {
        $ownerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'role' => 'shop_owner',
            'is_active' => true,
        ];

        if ($request->filled('owner_email')) {
            $ownerData['email'] = $request->input('owner_email');
        }

        if ($request->filled('owner_password')) {
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
