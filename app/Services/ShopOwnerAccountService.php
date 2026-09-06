<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShopOwnerAccountService
{
    public function resolve(Shop $shop, bool $applyDefaults = true): User
    {
        $owner = $shop->user;

        if ($owner?->isShopOwner()) {
            if (! $owner->is_active) {
                $owner->update(['is_active' => true]);
            }
            $owner = $owner->fresh();
        } else {
            $owner = $this->createOwner($shop);
        }

        $permissionShops = $owner->shops()->where('is_active', true)->get();
        if (! $permissionShops->contains('id', $shop->id)) {
            $permissionShops->push($shop);
        }

        $allowedPermissions = $permissionShops
            ->flatMap(fn (Shop $ownedShop) => $this->permissionsFor($ownedShop))
            ->unique()
            ->values();

        if ($applyDefaults && ! $owner->employeePermissions()->exists()) {
            $permissions = $allowedPermissions;

            foreach ($permissions as $permission) {
                $owner->employeePermissions()->create(['permission' => $permission]);
            }
        }

        if ($applyDefaults) {
            $owner->employeePermissions()
                ->whereNotIn('permission', $allowedPermissions->all())
                ->delete();

            $requiredPermissions = $permissionShops
                ->flatMap(fn (Shop $ownedShop) => config("shop_owner_permissions.catalog_type_required.{$ownedShop->catalog_type}", []))
                ->merge(config('shop_owner_permissions.required', []))
                ->unique();

            foreach ($requiredPermissions as $permission) {
                $owner->employeePermissions()->firstOrCreate(['permission' => $permission]);
            }
        }

        return $owner;
    }

    public function permissionsFor(Shop $shop): \Illuminate\Support\Collection
    {
        $excluded = config("shop_owner_permissions.catalog_type_excluded.{$shop->catalog_type}", []);

        return collect(config('shop_owner_permissions.allowed', []))
            ->merge(config("shop_owner_permissions.catalog_type_permissions.{$shop->catalog_type}", []))
            ->reject(fn (string $permission) => in_array($permission, $excluded, true))
            ->unique()
            ->values();
    }

    private function createOwner(Shop $shop): User
    {
        $email = filled($shop->email) && ! User::query()->where('email', $shop->email)->exists()
            ? $shop->email
            : "shop-{$shop->id}@ozman.local";

        while (User::query()->where('email', $email)->exists()) {
            $email = "shop-{$shop->id}-".Str::lower(Str::random(6)).'@ozman.local';
        }

        $owner = User::create([
            'name' => $shop->name.' - صاحب المتجر',
            'email' => $email,
            'phone' => $shop->phone,
            'password' => Hash::make(Str::random(32)),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $shop->update(['user_id' => $owner->id]);
        $shop->setRelation('user', $owner);

        return $owner;
    }
}
