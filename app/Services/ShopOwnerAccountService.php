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

        if ($applyDefaults && ! $owner->employeePermissions()->exists()) {
            foreach (config('shop_owner_permissions.allowed', []) as $permission) {
                $owner->employeePermissions()->create(['permission' => $permission]);
            }
        }

        return $owner;
    }

    private function createOwner(Shop $shop): User
    {
        $email = filled($shop->email) && ! User::query()->where('email', $shop->email)->exists()
            ? $shop->email
            : "shop-{$shop->id}@ozman.local";

        while (User::query()->where('email', $email)->exists()) {
            $email = "shop-{$shop->id}-" . Str::lower(Str::random(6)) . '@ozman.local';
        }

        $owner = User::create([
            'name' => $shop->name . ' - صاحب المتجر',
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
