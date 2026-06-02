<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    protected function ownedShopIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        return Auth::user()
            ? Auth::user()->shops()->pluck('id')->all()
            : [];
    }

    protected function scopeToAccessibleShops(Builder $query, string $column = 'shop_id'): Builder
    {
        if ($this->isSuperAdmin()) {
            return $query;
        }

        return $query->whereIn($column, $this->ownedShopIds());
    }

    protected function accessibleShops(): Collection
    {
        return $this->isSuperAdmin()
            ? Shop::query()->orderBy('name')->get()
            : Auth::user()?->shops()->orderBy('name')->get() ?? collect();
    }

    protected function firstAccessibleShopId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->ownedShopIds()[0] ?? null;
    }

    protected function normalizeShopId(array &$data): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $shopId = $this->firstAccessibleShopId();
        abort_if($shopId === null, 403);

        $data['shop_id'] = $shopId;
    }

    protected function authorizeShopAccess(Model $model): void
    {
        if ($this->isSuperAdmin()) {
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
}
