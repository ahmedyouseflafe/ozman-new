<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

abstract class Controller
{
    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    protected function hasGlobalDashboardAccess(): bool
    {
        return Auth::user()?->isSuperAdmin() === true
            || Auth::user()?->isEmployee() === true;
    }

    protected function canAccessCurrentRoute(): bool
    {
        $user = Auth::user();

        return $user?->isSuperAdmin() === true
            || (($user?->isEmployee() === true || $user?->hasAssignedPermissions() === true)
                && $user->canAccessRouteName(request()->route()?->getName()));
    }

    protected function ownedShopIds(): array
    {
        if ($this->hasGlobalDashboardAccess()) {
            return [];
        }

        return Auth::user()
            ? Auth::user()->accessibleShopIds()
            : [];
    }

    protected function scopeToAccessibleShops(Builder $query, string $column = 'shop_id'): Builder
    {
        if ($this->hasGlobalDashboardAccess()) {
            return $query;
        }

        return $query->whereIn($column, $this->ownedShopIds());
    }

    protected function accessibleShops(): Collection
    {
        return $this->hasGlobalDashboardAccess()
            ? Shop::query()->orderBy('name')->get()
            : Shop::query()->whereIn('id', $this->ownedShopIds())->orderBy('name')->get();
    }

    protected function firstAccessibleShopId(): ?int
    {
        if ($this->hasGlobalDashboardAccess()) {
            return null;
        }

        return $this->ownedShopIds()[0] ?? null;
    }

    protected function normalizeShopId(array &$data): void
    {
        if ($this->hasGlobalDashboardAccess()) {
            return;
        }

        $ownedShopIds = $this->ownedShopIds();
        $requestedShopId = isset($data['shop_id']) ? (int) $data['shop_id'] : null;
        $shopId = $requestedShopId && in_array($requestedShopId, $ownedShopIds, true)
            ? $requestedShopId
            : ($ownedShopIds[0] ?? null);
        abort_if($shopId === null, 403);

        $data['shop_id'] = $shopId;
    }

    protected function authorizeShopAccess(Model $model): void
    {
        if ($this->hasGlobalDashboardAccess()) {
            return;
        }

        $shopId = $model instanceof Shop ? $model->id : $model->getAttribute('shop_id');

        abort_unless(in_array((int) $shopId, $this->ownedShopIds(), true), 403);
    }

    protected function notifySuperAdmin(
        string $type,
        Model $subject,
        string $title,
        ?string $message = null,
        ?string $url = null,
        array $data = []
    ): void {
        if (! $this->adminNotificationEnabled($type)) {
            return;
        }

        $shopId = $data['shop_id'] ?? ($subject instanceof Shop ? $subject->id : $subject->getAttribute('shop_id'));

        AdminNotification::create([
            'shop_id' => $shopId,
            'user_id' => Auth::id(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'url' => $url,
            'data' => $data ?: null,
        ]);
    }

    protected function adminNotificationEnabled(string $type): bool
    {
        $settingsKey = match ($type) {
            'shop_created' => 'new_shops',
            'product_out_of_stock' => 'out_of_stock',
            'user_created' => 'new_users',
            default => null,
        };

        if (! $settingsKey) {
            return true;
        }

        $defaults = [
            'new_shops' => true,
            'out_of_stock' => true,
            'new_users' => false,
        ];

        $stored = [];
        if (Storage::disk('local')->exists('ozman_settings.json')) {
            $stored = json_decode(Storage::disk('local')->get('ozman_settings.json'), true);
            $stored = is_array($stored) ? $stored : [];
        }

        $notifications = array_replace($defaults, $stored['notifications'] ?? []);

        return (bool) ($notifications[$settingsKey] ?? true);
    }
}
