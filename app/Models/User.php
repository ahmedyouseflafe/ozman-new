<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'image',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function agentProfiles()
    {
        return $this->hasMany(Agent::class);
    }

    public function distributorProfiles()
    {
        return $this->hasMany(Distributor::class);
    }

    public function employeePermissions()
    {
        return $this->hasMany(EmployeePermission::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isShopOwner(): bool
    {
        return $this->role === 'shop_owner';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isDistributor(): bool
    {
        return $this->role === 'distributor';
    }

    public function isMarketer(): bool
    {
        return $this->role === 'marketer';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasAssignedPermissions(): bool
    {
        if ($this->isEmployee()) {
            return true;
        }

        if (! ($this->isAgent() || $this->isDistributor())) {
            return false;
        }

        if ($this->relationLoaded('employeePermissions')) {
            return $this->employeePermissions->isNotEmpty();
        }

        return $this->employeePermissions()->exists();
    }

    public function permissionKeys(): array
    {
        if (! ($this->isEmployee() || $this->isAgent() || $this->isDistributor())) {
            return [];
        }

        return $this->employeePermissions()
            ->pluck('permission')
            ->all();
    }

    public function hasEmployeePermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->hasAssignedPermissions()) {
            return false;
        }

        $loadedPermissions = $this->relationLoaded('employeePermissions')
            ? $this->employeePermissions->pluck('permission')->all()
            : null;

        return $loadedPermissions
            ? in_array($permission, $loadedPermissions, true)
            : $this->employeePermissions()->where('permission', $permission)->exists();
    }

    public function canAccessRouteName(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->hasAssignedPermissions()) {
            return true;
        }

        $alwaysAllowed = ['dashboard', 'logout', 'lang.switch', 'translations.suggest'];
        if (in_array($routeName, $alwaysAllowed, true)) {
            return true;
        }

        return $this->canAccessAnyRoute([$routeName]);
    }

    public function canAccessAnyRoute(array $routeNames): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->hasAssignedPermissions()) {
            return true;
        }

        $permissions = $this->relationLoaded('employeePermissions')
            ? $this->employeePermissions->pluck('permission')->all()
            : $this->permissionKeys();

        foreach (config('employee_permissions.groups', []) as $group) {
            foreach ($group['permissions'] ?? [] as $permission => $meta) {
                if (! in_array($permission, $permissions, true)) {
                    continue;
                }

                foreach ($meta['routes'] ?? [] as $allowedRoute) {
                    foreach ($routeNames as $routeName) {
                        if ($routeName === $allowedRoute) {
                            return true;
                        }

                        if (str_ends_with($allowedRoute, '*') && str_starts_with($routeName, rtrim($allowedRoute, '*'))) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function accessibleShopIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        $shopIds = $this->shops()->pluck('id');

        if ($this->isAgent()) {
            $agentShopIds = Agent::query()
                ->where(function ($query) {
                    $query->where('user_id', $this->id);

                    if ($this->email) {
                        $query->orWhere('email', $this->email);
                    }
                })
                ->pluck('shop_id');

            $shopIds = $shopIds->merge($agentShopIds);
        }

        if ($this->isDistributor()) {
            $distributorShopIds = Distributor::query()
                ->where(function ($query) {
                    $query->where('user_id', $this->id);

                    if ($this->email) {
                        $query->orWhere('email', $this->email);
                    }
                })
                ->pluck('shop_id');

            $shopIds = $shopIds->merge($distributorShopIds);
        }

        return $shopIds
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
