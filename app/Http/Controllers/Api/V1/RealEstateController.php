<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ModularServiceController;
use App\Http\Controllers\RealEstateController as SiteRealEstate;
use App\Models\{ModularService, ModularServiceRequest, PushDevice, RealEstateLead, RealEstateProperty, Shop, User};
use App\Services\{FirebaseMessagingService, ModularCatalogDefaults};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Service companies ("real estate" shop type) in the app: their service sections with options,
 * request a service, browse and ask about properties; the owner follows requests and leads.
 */
class RealEstateController extends Controller
{
    public function company(Shop $shop, ModularCatalogDefaults $defaults): JsonResponse
    {
        $this->ensureCompany($shop);
        $defaults->ensure($shop);
        $categories = $shop->modularCategories()->where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->with('images', 'category')])->get();
        $properties = RealEstateProperty::published()->where('shop_id', $shop->id)->with('images')
            ->orderByDesc('is_featured')->latest('published_at')->limit(50)->get();

        return response()->json([
            'shop' => Presenter::shop($shop, true),
            'categories' => $categories->filter(fn ($category) => $category->services->isNotEmpty())->map(fn ($category) => [
                'id' => $category->id, 'name' => $category->localized('name'), 'description' => $category->localized('description'),
                'image' => $this->image($category->cover_image),
                'services' => $category->services->map(fn ($service) => $this->serviceCard($service))->values(),
            ])->values(),
            'properties' => $properties->map(fn ($property) => $this->propertyCard($property)),
        ]);
    }

    public function service(ModularService $service): JsonResponse
    {
        $service->load(['category.shop', 'images', 'optionGroups' => fn ($q) => $q->orderBy('position'),
            'optionGroups.values' => fn ($q) => $q->where('is_active', true)->orderBy('position')]);
        $shop = $service->category?->shop;
        abort_unless($shop && $service->is_active && $service->category->is_active, 404);
        $this->ensureCompany($shop);

        return response()->json(['service' => $this->serviceCard($service) + [
            'description' => $service->localized('description'),
            'images' => $service->images->map(fn ($image) => $this->image($image->path))->filter()->values(),
            'groups' => $service->optionGroups->filter(fn ($group) => $group->values->isNotEmpty())->map(fn ($group) => [
                'id' => $group->id, 'name' => $group->localized('name'), 'help' => $group->localized('help_text'), 'required' => (bool) $group->is_required,
                'values' => $group->values->map(fn ($value) => ['id' => $value->id, 'name' => $value->localized('name'),
                    'description' => $value->localized('description'), 'default' => (bool) $value->is_default])->values(),
            ])->values(),
            'share_url' => $service->publicUrl(),
        ], 'shop' => Presenter::shop($shop)]);
    }

    /** The website saves the request and opens WhatsApp with the message; the app gets the same link. */
    public function requestService(Request $request, ModularService $service, ModularServiceController $site, FirebaseMessagingService $firebase): JsonResponse
    {
        $service->load('category.shop');
        $shop = $service->category?->shop;
        abort_unless($shop, 404);
        $response = $site->whatsapp($request, $shop, $service->category, $service);
        $requestRow = ModularServiceRequest::where('modular_service_id', $service->id)->latest('id')->first();
        if ($requestRow) {
            $this->tellTheCompany($shop, 'طلب خدمة جديد · '.$service->localized('name'), "{$requestRow->customer_name} · {$requestRow->customer_phone}",
                ['type' => 'service_request', 'shop_id' => $shop->id, 'request_id' => $requestRow->id], $firebase);
        }

        return response()->json(['message' => 'وصل طلبك للشركة ✅ كمّل المحادثة على واتساب.', 'whatsapp_url' => $response->getTargetUrl(),
            'request_id' => $requestRow?->id], 201);
    }

    public function properties(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shop_id' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:100'], 'purpose' => ['nullable', Rule::in(RealEstateProperty::PURPOSES)],
            'city' => ['nullable', 'string', 'max:100'], 'property_type' => ['nullable', 'string', 'max:60'],
            'min_price' => ['nullable', 'integer', 'min:0'], 'max_price' => ['nullable', 'integer', 'min:0'], 'rooms' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc', 'area_desc'])],
        ]);
        $like = fn ($v) => '%'.addcslashes(trim($v), '%_\\').'%';
        $query = RealEstateProperty::published()
            ->whereHas('shop', fn ($q) => $q->where('is_active', true)->where('catalog_type', 'real_estate'))
            ->when($data['shop_id'] ?? null, fn ($q, $id) => $q->where('shop_id', $id))
            ->when(filled($data['q'] ?? null), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', $like($data['q']))
                ->orWhere('city', 'like', $like($data['q']))->orWhere('neighborhood', 'like', $like($data['q']))))
            ->when($data['purpose'] ?? null, fn ($q, $v) => $q->where('purpose', $v))
            ->when($data['city'] ?? null, fn ($q, $v) => $q->where('city', $v))
            ->when($data['property_type'] ?? null, fn ($q, $v) => $q->where('property_type', $v))
            ->when(isset($data['min_price']), fn ($q) => $q->where('price', '>=', $data['min_price']))
            ->when(isset($data['max_price']), fn ($q) => $q->where('price', '<=', $data['max_price']))
            ->when(isset($data['rooms']), fn ($q) => $q->where('rooms', '>=', $data['rooms']));
        match ($data['sort'] ?? 'latest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'area_desc' => $query->orderByDesc('area'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };
        $page = $query->with(['images', 'shop:id,name,slug,logo'])->paginate(20);
        $base = RealEstateProperty::published();

        return response()->json([
            'data' => collect($page->items())->map(fn ($property) => $this->propertyCard($property)),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'cities' => (clone $base)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'types' => (clone $base)->distinct()->orderBy('property_type')->pluck('property_type'),
        ]);
    }

    public function property(RealEstateProperty $property): JsonResponse
    {
        abort_unless($property->status === 'published' && $property->published_at && $property->shop?->is_active, 404);
        $property->load(['images', 'shop']);
        $features = collect([
            'furnished' => 'مفروش', 'is_new_project' => 'مشروع جديد', 'has_elevator' => 'مصعد', 'has_balcony' => 'بلكونة',
            'has_garden' => 'حديقة', 'has_storage' => 'مخزن', 'has_air_conditioning' => 'تكييف',
        ])->filter(fn ($label, $key) => (bool) $property->{$key})->values();
        if ((int) $property->parking_spaces > 0) {
            $features->push('موقف سيارات ('.$property->parking_spaces.')');
        }

        return response()->json(['property' => $this->propertyCard($property) + [
            'description' => $property->localized('description'),
            'images' => $property->images->sortBy('position')->map(fn ($image) => $this->image($image->path))->filter()->values(),
            'address' => $property->address, 'neighborhood' => $property->neighborhood,
            'bedrooms' => $property->bedrooms, 'bathrooms' => $property->bathrooms, 'floor' => $property->floor,
            'building_floors' => $property->building_floors, 'monthly_fees' => Presenter::price($property->monthly_fees),
            'features' => $features, 'latitude' => $property->latitude, 'longitude' => $property->longitude,
            'video_url' => $property->video_url, 'available_from' => $property->available_from?->toDateString(),
            'share_url' => $property->publicUrl(),
        ], 'shop' => Presenter::shop($property->shop)]);
    }

    public function inquiry(Request $request, RealEstateProperty $property, SiteRealEstate $site, FirebaseMessagingService $firebase): JsonResponse
    {
        $property->load('shop');
        abort_unless($property->shop, 404);
        $request->merge(['source' => 'website']);
        $site->inquiry($request, $property->shop, $property);
        $this->tellTheCompany($property->shop, 'عميل مهتم بعقار · '.$property->title, $request->input('name').' · '.$request->input('phone'),
            ['type' => 'real_estate_lead', 'shop_id' => $property->shop_id, 'property_id' => $property->id], $firebase);

        return response()->json(['message' => 'تم إرسال طلبك للشركة بنجاح، وسيتم التواصل معك قريبًا.'], 201);
    }

    // ── Owner ──────────────────────────────────────────────────────────────

    public function inbox(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop);
        $requests = ModularServiceRequest::where('shop_id', $shop->id)->with('service')->latest('id')->limit(100)->get();
        $leads = RealEstateLead::where('shop_id', $shop->id)->with('property:id,title')->latest('id')->limit(100)->get();

        return response()->json([
            'requests' => $requests->map(fn ($row) => [
                'id' => $row->id, 'service' => $row->service?->localized('name'), 'name' => $row->customer_name, 'phone' => $row->customer_phone,
                'city' => $row->city, 'notes' => $row->notes, 'status' => $row->status,
                'selections' => collect($row->selections ?? [])->map(fn ($s) => ($s['group_name'] ?? '').': '.($s['value_name'] ?? ''))->values(),
                'created_at' => $row->created_at?->toIso8601String(),
            ]),
            'leads' => $leads->map(fn ($lead) => [
                'id' => $lead->id, 'property' => $lead->property?->title, 'name' => $lead->name, 'phone' => $lead->phone, 'email' => $lead->email,
                'message' => $lead->message, 'status' => $lead->status ?: 'new', 'viewing_at' => $lead->viewing_at?->toIso8601String(),
                'created_at' => $lead->created_at?->toIso8601String(),
            ]),
            'request_statuses' => ['new' => 'جديد', 'contacted' => 'تم التواصل', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'],
            'lead_statuses' => ['new' => 'جديد', 'contacted' => 'تم التواصل', 'viewing' => 'معاينة', 'won' => 'تم الاتفاق', 'lost' => 'لم يتم'],
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function updateRequest(Request $request, ModularServiceRequest $serviceRequest): JsonResponse
    {
        $shop = Shop::findOrFail($serviceRequest->shop_id);
        $this->authorizeCompany($request->user(), $shop);
        app()->call([app(\App\Http\Controllers\ModularServiceDashboardController::class), 'updateRequest'],
            ['request' => $request, 'shop' => $shop, 'serviceRequest' => $serviceRequest]);

        return response()->json(['status' => $serviceRequest->fresh()->status, 'message' => 'تم تحديث حالة الطلب.']);
    }

    public function updateLead(Request $request, RealEstateLead $lead): JsonResponse
    {
        $shop = Shop::findOrFail($lead->shop_id);
        $this->authorizeCompany($request->user(), $shop);
        app()->call([app(\App\Http\Controllers\RealEstateDashboardController::class), 'updateLead'], ['request' => $request, 'shop' => $shop, 'lead' => $lead]);

        return response()->json(['status' => $lead->fresh()->status, 'message' => 'تم تحديث حالة العميل.']);
    }

    private function serviceCard(ModularService $service): array
    {
        return ['id' => $service->id, 'name' => $service->localized('name'), 'summary' => $service->localized('short_description'),
            'image' => $service->imageUrl(), 'featured' => (bool) $service->is_featured];
    }

    private function propertyCard(RealEstateProperty $property): array
    {
        $cover = $property->images->firstWhere('is_cover', true) ?? $property->images->sortBy('position')->first();

        return [
            'id' => $property->id, 'title' => $property->localized('title'), 'purpose' => $property->purpose,
            'purpose_label' => $property->purpose === 'rent' ? 'للإيجار' : 'للبيع', 'type' => $property->property_type,
            'price' => Presenter::price($property->price), 'currency' => $property->currency ?: 'ILS', 'city' => $property->city,
            'rooms' => $property->rooms, 'area' => $property->area, 'image' => $this->image($cover?->path), 'featured' => (bool) $property->is_featured,
            'shop' => $property->relationLoaded('shop') && $property->shop ? ['id' => $property->shop->id, 'name' => $property->shop->name] : null,
        ];
    }

    private function image(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return preg_match('~^https?://~i', $path) ? $path : (str_starts_with($path, 'images/') ? asset($path) : Storage::url($path));
    }

    private function tellTheCompany(Shop $shop, string $title, string $body, array $data, FirebaseMessagingService $firebase): void
    {
        try {
            $tokens = PushDevice::where('user_id', $shop->user_id)->pluck('token');
            if ($shop->user_id && $tokens->isNotEmpty()) {
                $firebase->sendToTokens($tokens, $title, $body, route('real-estate.dashboard', $shop), $data);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function ensureCompany(Shop $shop): void
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'real_estate', 404);
    }

    private function authorizeCompany(User $user, Shop $shop): void
    {
        abort_unless($shop->catalog_type === 'real_estate', 404);
        abort_unless($user->isSuperAdmin() || in_array((int) $shop->id, $user->accessibleShopIds(), true), 403);
    }
}
