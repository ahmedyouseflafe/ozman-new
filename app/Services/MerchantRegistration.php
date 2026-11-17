<?php

namespace App\Services;

use App\Models\{Distributor, DistributorMarketer, Shop, User};
use App\Rules\ValidPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A new shop signs up through a distributor's or marketer's referral (their QR). The website and the app both use
 * this, so the rules are the same: an active referral, a unique email, a location, and the shop starts as "general"
 * showing Ozman's catalogue, linked to whoever referred it.
 */
class MerchantRegistration
{
    /** The referral from a marketer's tracking code or a distributor id, if it's still active. */
    public function referral(?int $distributorId, ?int $marketerId): ?array
    {
        if ($marketerId) {
            $marketer = DistributorMarketer::query()->whereKey($marketerId)->where('is_active', true)
                ->whereHas('distributor', fn ($q) => $q->where('is_active', true))->first();

            return $marketer ? ['distributor_id' => $marketer->distributor_id, 'distributor_marketer_id' => $marketer->id] : null;
        }
        $distributor = $distributorId ? Distributor::query()->whereKey($distributorId)->where('is_active', true)->first() : null;

        return $distributor ? ['distributor_id' => $distributor->id, 'distributor_marketer_id' => null] : null;
    }

    public function referralFromCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        // A marketer's tracking code, or the links its QR opens: /marketer-stores/{code} or /distributor-stores/{id}.
        if (preg_match('~/distributor-stores/(\d+)~', $code, $m)) {
            return $this->referral((int) $m[1], null);
        }
        if (preg_match('~/marketer-stores/([^/?#]+)~', $code, $m)) {
            $code = urldecode($m[1]);
        }
        $marketer = DistributorMarketer::query()->where('tracking_code', $code)->first();

        return $marketer ? $this->referral(null, $marketer->id) : null;
    }

    /** Who referred the shop, to show it on the form. */
    public function describe(array $referral): array
    {
        $distributor = Distributor::find($referral['distributor_id']);
        $marketer = $referral['distributor_marketer_id'] ? DistributorMarketer::find($referral['distributor_marketer_id']) : null;

        return ['distributor' => $distributor?->name, 'marketer' => $marketer?->name];
    }

    /** A shop owner account left without a shop (its shop was deleted) may sign up again with the same email. */
    public function orphanOwner(string $email): ?User
    {
        $owner = filter_var($email, FILTER_VALIDATE_EMAIL) ? User::query()->where('email', $email)->first() : null;

        return $owner?->isShopOwner() && ! $owner->shops()->exists() ? $owner : null;
    }

    public function rules(?User $orphanOwner): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'هذا البريد مرتبط بحساب موجود فعلياً. جرّب تسجيل الدخول أو استخدم بريداً آخر.'];
    }

    /** @return array{0: User, 1: Shop} */
    public function create(array $data, array $referral, ?User $orphanOwner, Request $request): array
    {
        // An orphan account is removed only now, after the referral was found valid.
        $orphanOwner?->delete();
        $logoPath = $request->hasFile('logo') ? 'storage/'.$request->file('logo')->store('shops/logos', 'public') : null;

        return DB::transaction(function () use ($data, $referral, $logoPath) {
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
                'slug' => $this->uniqueSlug($data['shop_name']),
                'catalog_type' => 'general',
                'logo' => $logoPath,
                'phone' => $data['phone'],
                'whatsapp' => ($data['whatsapp'] ?? null) ?: $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_active' => true,
                // A shop that came through a distributor sells Ozman's base catalogue from day one.
                'show_ozman_products' => true,
            ]);

            return [$user, $shop];
        });
    }

    public function uniqueSlug(string $name): string
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
}
