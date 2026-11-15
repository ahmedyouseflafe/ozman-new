<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Shop, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Storage};

/**
 * The owner edits the shop's everyday details from the app: name, description, contact, hours,
 * location, logo, cover and social links. Only the fields sent are changed (the website form
 * still has the rare settings: type, link, payment details).
 */
class ShopSettingsController extends Controller
{
    private const SOCIAL = ['facebook', 'instagram', 'tiktok', 'telegram', 'snapchat', 'twitter', 'youtube', 'whatsapp'];

    public function show(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeOwner($request->user(), $shop);

        return response()->json(['settings' => $this->present($shop->load('social'))], 200, ['Cache-Control' => 'no-store']);
    }

    public function update(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeOwner($request->user(), $shop);
        foreach (['open_time', 'close_time'] as $field) {
            $time = $request->input($field);
            if (is_string($time) && preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $time)) {
                $request->merge([$field => substr($time, 0, 5)]);
            }
        }
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'open_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'close_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'logo' => ['sometimes', 'image', 'max:2048'],
            'banner' => ['sometimes', 'image', 'max:4096'],
            'social' => ['sometimes', 'array'],
            ...collect(self::SOCIAL)->mapWithKeys(fn ($key) => ["social.$key" => ['nullable', 'string', 'max:255']])->all(),
        ], ['logo.max' => 'الشعار لازم يكون أقل من 2 ميجا.', 'banner.max' => 'صورة الغلاف لازم تكون أقل من 4 ميجا.']);

        $fields = collect($data)->except(['logo', 'banner', 'social'])->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
        $old = [];
        foreach (['logo' => 'shops/logos', 'banner' => 'shops/banners'] as $field => $directory) {
            if ($request->hasFile($field)) {
                $old[] = $shop->{$field};
                $fields[$field] = 'storage/'.$request->file($field)->store($directory, 'public');
            }
        }

        DB::transaction(function () use ($shop, $fields, $data) {
            if ($fields) {
                $shop->update($fields);
            }
            if (array_key_exists('social', $data)) {
                $shop->social()->updateOrCreate(['shop_id' => $shop->id],
                    collect(self::SOCIAL)->filter(fn ($key) => array_key_exists($key, $data['social']))
                        ->mapWithKeys(fn ($key) => [$key => filled($data['social'][$key]) ? trim($data['social'][$key]) : null])->all());
            }
        });
        foreach (array_filter($old) as $path) {
            if (str_starts_with($path, 'storage/')) {
                Storage::disk('public')->delete(substr($path, strlen('storage/')));
            }
        }

        return response()->json(['settings' => $this->present($shop->fresh('social')), 'shop' => Presenter::shop($shop->fresh(), true),
            'message' => 'تم حفظ بيانات '.$shop->name.'.']);
    }

    private function present(Shop $shop): array
    {
        return [
            'id' => $shop->id, 'name' => $shop->name, 'type' => $shop->catalog_type, 'description' => $shop->description,
            'phone' => $shop->phone, 'whatsapp' => $shop->whatsapp, 'email' => $shop->email,
            'address' => $shop->address, 'city' => $shop->city,
            'latitude' => $shop->latitude !== null ? (float) $shop->latitude : null, 'longitude' => $shop->longitude !== null ? (float) $shop->longitude : null,
            'open_time' => $shop->open_time ? substr((string) $shop->open_time, 0, 5) : null,
            'close_time' => $shop->close_time ? substr((string) $shop->close_time, 0, 5) : null,
            'logo' => Presenter::media($shop->logo), 'banner' => Presenter::media($shop->banner),
            'social' => collect(self::SOCIAL)->mapWithKeys(fn ($key) => [$key => $shop->social?->{$key}])->all(),
            'public_url' => $shop->publicUrl(),
        ];
    }

    private function authorizeOwner(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin()
            || (in_array((int) $shop->id, $user->accessibleShopIds(), true) && $user->canAccessRouteName('shops.update')), 403);
    }
}
