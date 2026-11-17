<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\{Controller, ModularServiceDashboardController, RealEstateDashboardController};
use App\Models\{ModularCategory, ModularService, ModularServiceImage, RealEstateProperty, RealEstatePropertyImage, Shop, User};
use Illuminate\Http\{JsonResponse, Request};

/**
 * Real estate and building companies manage their listings in the app: properties with photos, and services with
 * their sections and options. Every change runs through the dashboard's own code (validation, images, publishing).
 */
class RealEstateManageController extends Controller
{
    // ── Properties ───────────────────────────────────────────────────────────

    public function properties(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        $properties = $shop->realEstateProperties()->with('images')->latest('id')->limit(200)->get();

        return response()->json([
            'data' => $properties->map(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'purpose' => $p->purpose, 'property_type' => $p->property_type,
                'price' => (float) $p->price, 'currency' => $p->currency, 'city' => $p->city, 'status' => $p->status,
                'status_label' => __(self::statusLabel($p->status)), 'is_featured' => (bool) $p->is_featured,
                'cover' => $this->image($p->images->firstWhere('is_cover', true)?->path ?? $p->images->first()?->path),
                'images_count' => $p->images->count(),
            ]),
            'options' => $this->propertyOptions(),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function property(Request $request, RealEstateProperty $property): JsonResponse
    {
        $shop = $this->propertyShop($request->user(), $property);

        return response()->json(['property' => $this->presentProperty($property->load('images')), 'options' => $this->propertyOptions(), 'shop_id' => $shop->id]);
    }

    public function storeProperty(Request $request, Shop $shop, RealEstateDashboardController $site): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        $before = (int) $shop->realEstateProperties()->max('id');
        app()->call([$site, 'store'], ['request' => $request, 'shop' => $shop]);
        $property = $shop->realEstateProperties()->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['property' => $this->presentProperty($property->load('images')), 'message' => __('تمت إضافة العقار.')], 201);
    }

    public function updateProperty(Request $request, RealEstateProperty $property, RealEstateDashboardController $site): JsonResponse
    {
        $shop = $this->propertyShop($request->user(), $property);
        app()->call([$site, 'update'], ['request' => $request, 'shop' => $shop, 'property' => $property]);

        return response()->json(['property' => $this->presentProperty($property->fresh()->load('images')), 'message' => __('انحفظ العقار.')]);
    }

    public function destroyProperty(Request $request, RealEstateProperty $property, RealEstateDashboardController $site): JsonResponse
    {
        $shop = $this->propertyShop($request->user(), $property);
        app()->call([$site, 'destroy'], ['request' => $request, 'shop' => $shop, 'property' => $property]);

        return response()->json(['message' => __('انحذف العقار وصوره.')]);
    }

    public function destroyPropertyImage(Request $request, RealEstatePropertyImage $image, RealEstateDashboardController $site): JsonResponse
    {
        $property = $image->property;
        abort_unless($property, 404);
        $shop = $this->propertyShop($request->user(), $property);
        app()->call([$site, 'destroyImage'], ['request' => $request, 'shop' => $shop, 'property' => $property, 'image' => $image]);

        return response()->json(['property' => $this->presentProperty($property->fresh()->load('images'))]);
    }

    public function coverPropertyImage(Request $request, RealEstatePropertyImage $image, RealEstateDashboardController $site): JsonResponse
    {
        $property = $image->property;
        abort_unless($property, 404);
        $shop = $this->propertyShop($request->user(), $property);
        app()->call([$site, 'coverImage'], ['request' => $request, 'shop' => $shop, 'property' => $property, 'image' => $image]);

        return response()->json(['property' => $this->presentProperty($property->fresh()->load('images'))]);
    }

    // ── Services and their sections ──────────────────────────────────────────

    public function services(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        $categories = $shop->modularCategories()->with(['services' => fn ($q) => $q->orderBy('position')])->orderBy('position')->get();

        return response()->json(['categories' => $categories->map(fn (ModularCategory $c) => [
            'id' => $c->id, 'name' => $c->name, 'name_he' => $c->name_translations['he'] ?? null, 'name_en' => $c->name_translations['en'] ?? null,
            'description' => $c->description, 'is_active' => (bool) $c->is_active, 'position' => $c->position, 'icon_key' => $c->icon_key,
            'services' => $c->services->map(fn (ModularService $s) => [
                'id' => $s->id, 'name' => $s->name, 'is_active' => (bool) $s->is_active, 'is_featured' => (bool) $s->is_featured,
                'cover' => $this->image($s->cover_image), 'short_description' => $s->short_description,
            ])->values(),
        ])->values()], 200, ['Cache-Control' => 'no-store']);
    }

    public function service(Request $request, ModularService $service): JsonResponse
    {
        $this->serviceShop($request->user(), $service);

        return response()->json(['service' => $this->presentService($service)]);
    }

    public function storeService(Request $request, Shop $shop, ModularServiceDashboardController $site): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        $before = (int) ModularService::max('id');
        app()->call([$site, 'storeService'], ['request' => $request, 'shop' => $shop]);
        $service = ModularService::whereHas('category', fn ($q) => $q->where('shop_id', $shop->id))->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['service' => $this->presentService($service), 'message' => __('تمت إضافة الخدمة.')], 201);
    }

    public function updateService(Request $request, ModularService $service, ModularServiceDashboardController $site): JsonResponse
    {
        $shop = $this->serviceShop($request->user(), $service);
        app()->call([$site, 'updateService'], ['request' => $request, 'shop' => $shop, 'service' => $service]);

        return response()->json(['service' => $this->presentService($service->fresh()), 'message' => __('انحفظت الخدمة.')]);
    }

    public function destroyService(Request $request, ModularService $service, ModularServiceDashboardController $site): JsonResponse
    {
        $shop = $this->serviceShop($request->user(), $service);
        app()->call([$site, 'destroyService'], ['request' => $request, 'shop' => $shop, 'service' => $service]);

        return response()->json(['message' => __('انحذفت الخدمة.')]);
    }

    public function destroyServiceImage(Request $request, ModularServiceImage $image, ModularServiceDashboardController $site): JsonResponse
    {
        $service = $image->service;
        abort_unless($service, 404);
        $shop = $this->serviceShop($request->user(), $service);
        app()->call([$site, 'destroyServiceImage'], ['request' => $request, 'shop' => $shop, 'image' => $image]);

        return response()->json(['service' => $this->presentService($service->fresh())]);
    }

    public function storeCategory(Request $request, Shop $shop, ModularServiceDashboardController $site): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        app()->call([$site, 'storeCategory'], ['request' => $request, 'shop' => $shop]);

        return response()->json(['message' => __('انضاف القسم.')], 201);
    }

    public function updateCategory(Request $request, ModularCategory $category, ModularServiceDashboardController $site): JsonResponse
    {
        $shop = $category->shop;
        abort_unless($shop, 404);
        $this->authorizeCompany($request->user(), $shop);
        app()->call([$site, 'updateCategory'], ['request' => $request, 'shop' => $shop, 'category' => $category]);

        return response()->json(['message' => __('انحفظ القسم.')]);
    }

    public function destroyCategory(Request $request, ModularCategory $category, ModularServiceDashboardController $site): JsonResponse
    {
        $shop = $category->shop;
        abort_unless($shop, 404);
        $this->authorizeCompany($request->user(), $shop);
        app()->call([$site, 'destroyCategory'], ['request' => $request, 'shop' => $shop, 'category' => $category]);

        return response()->json(['message' => __('انحذف القسم.')]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function presentProperty(RealEstateProperty $p): array
    {
        return [
            'id' => $p->id, 'reference' => $p->reference, 'purpose' => $p->purpose, 'property_type' => $p->property_type,
            'title' => $p->title, 'title_he' => $p->title_translations['he'] ?? null, 'title_en' => $p->title_translations['en'] ?? null,
            'description' => $p->description, 'description_he' => $p->description_translations['he'] ?? null, 'description_en' => $p->description_translations['en'] ?? null,
            'price' => (float) $p->price, 'currency' => $p->currency, 'monthly_fees' => $p->monthly_fees !== null ? (float) $p->monthly_fees : null,
            'city' => $p->city, 'neighborhood' => $p->neighborhood, 'address' => $p->address,
            'latitude' => $p->latitude !== null ? (float) $p->latitude : null, 'longitude' => $p->longitude !== null ? (float) $p->longitude : null,
            'rooms' => $p->rooms !== null ? (float) $p->rooms : null, 'bedrooms' => $p->bedrooms, 'bathrooms' => $p->bathrooms,
            'area' => $p->area !== null ? (float) $p->area : null, 'floor' => $p->floor, 'building_floors' => $p->building_floors,
            'parking_spaces' => $p->parking_spaces, 'available_from' => $p->available_from?->format('Y-m-d'),
            'status' => $p->status, 'amenities_text' => implode("\n", $p->amenities ?? []), 'nearby_places_text' => implode("\n", $p->nearby_places ?? []),
            'video_url' => $p->video_url, 'virtual_tour_url' => $p->virtual_tour_url,
            'furnished' => (bool) $p->furnished, 'is_new_project' => (bool) $p->is_new_project, 'has_elevator' => (bool) $p->has_elevator,
            'has_balcony' => (bool) $p->has_balcony, 'has_garden' => (bool) $p->has_garden, 'has_storage' => (bool) $p->has_storage,
            'has_air_conditioning' => (bool) $p->has_air_conditioning, 'is_featured' => (bool) $p->is_featured,
            'images' => $p->images->sortBy('position')->values()->map(fn ($i) => ['id' => $i->id, 'url' => $this->image($i->path), 'is_cover' => (bool) $i->is_cover])->all(),
            'web_url' => $p->shop ? route('real-estate.property', [$p->shop, $p]) : null,
        ];
    }

    private function presentService(ModularService $s): array
    {
        $s->load(['images', 'optionGroups.values']);

        return [
            'id' => $s->id, 'modular_category_id' => $s->modular_category_id,
            'name' => $s->name, 'name_he' => $s->name_translations['he'] ?? null, 'name_en' => $s->name_translations['en'] ?? null,
            'short_description' => $s->short_description, 'short_description_he' => $s->short_description_translations['he'] ?? null,
            'short_description_en' => $s->short_description_translations['en'] ?? null,
            'description' => $s->description, 'description_he' => $s->description_translations['he'] ?? null, 'description_en' => $s->description_translations['en'] ?? null,
            'is_active' => (bool) $s->is_active, 'is_featured' => (bool) $s->is_featured, 'position' => $s->position,
            'cover' => $this->image($s->cover_image),
            'images' => $s->images->sortBy('position')->values()->map(fn ($i) => ['id' => $i->id, 'url' => $this->image($i->path)])->all(),
            'option_groups' => $s->optionGroups->sortBy('position')->values()->map(fn ($g) => [
                'name' => $g->name, 'name_he' => $g->name_translations['he'] ?? null, 'name_en' => $g->name_translations['en'] ?? null,
                'help_text' => $g->help_text, 'help_text_he' => $g->help_text_translations['he'] ?? null, 'help_text_en' => $g->help_text_translations['en'] ?? null,
                'is_required' => (bool) $g->is_required,
                'values' => $g->values->sortBy('position')->values()->map(fn ($v) => [
                    'name' => $v->name, 'name_he' => $v->name_translations['he'] ?? null, 'name_en' => $v->name_translations['en'] ?? null,
                    'is_default' => (bool) $v->is_default,
                ])->all(),
            ])->all(),
        ];
    }

    private function propertyOptions(): array
    {
        $pairs = fn (array $keys, callable $label) => collect($keys)->map(fn ($k) => ['key' => $k, 'label' => __($label($k))])->values();

        return [
            'purposes' => $pairs(RealEstateProperty::PURPOSES, fn ($k) => $k === 'rent' ? 'للإيجار' : 'للبيع'),
            'types' => $pairs(['apartment', 'villa', 'house', 'land', 'office', 'shop', 'warehouse', 'building', 'commercial'], fn ($k) => self::typeLabel($k)),
            'statuses' => $pairs(RealEstateProperty::STATUSES, fn ($k) => self::statusLabel($k)),
            'currencies' => ['ILS', 'USD', 'JOD', 'EUR'],
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'apartment' => 'شقة', 'villa' => 'فيلا', 'house' => 'بيت', 'land' => 'أرض', 'office' => 'مكتب',
            'shop' => 'محل تجاري', 'warehouse' => 'مخزن', 'building' => 'عمارة', default => 'تجاري',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'مسودة', 'published' => 'منشور', 'reserved' => 'محجوز', 'rented' => 'مؤجَّر', 'sold' => 'مباع', default => 'مؤرشف',
        };
    }

    private function image(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return preg_match('~^https?://~i', $path) ? $path : (str_starts_with($path, 'images/') ? asset($path) : \Illuminate\Support\Facades\Storage::url($path));
    }

    private function authorizeCompany(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        abort_unless($shop->catalog_type === 'real_estate', 404);
    }

    private function propertyShop(User $user, RealEstateProperty $property): Shop
    {
        $shop = Shop::find($property->shop_id);
        abort_unless($shop, 404);
        $this->authorizeCompany($user, $shop);

        return $shop;
    }

    private function serviceShop(User $user, ModularService $service): Shop
    {
        $shop = $service->category?->shop;
        abort_unless($shop, 404);
        $this->authorizeCompany($user, $shop);

        return $shop;
    }
}
