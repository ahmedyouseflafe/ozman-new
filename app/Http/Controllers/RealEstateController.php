<?php

namespace App\Http\Controllers;

use App\Models\RealEstateAlert;
use App\Models\RealEstateLead;
use App\Models\RealEstateProperty;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RealEstateController extends Controller
{
    private const FILTER_KEYS = [
        'q', 'purpose', 'property_type', 'city', 'neighborhood', 'min_price', 'max_price',
        'min_area', 'max_area', 'rooms', 'bathrooms', 'floor', 'furnished', 'parking',
        'elevator', 'balcony', 'garden', 'storage', 'air_conditioning', 'available_now',
        'new_project', 'sort',
    ];

    public function index(Request $request): View
    {
        return $this->renderMarket($request);
    }

    public function company(Request $request, Shop $shop): View
    {
        $this->ensurePublicRealEstateCompany($shop);

        return $this->renderMarket($request, $shop);
    }

    public function property(Shop $shop, RealEstateProperty $realEstateProperty): View
    {
        $this->ensurePublicRealEstateCompany($shop);
        abort_unless($realEstateProperty->status === 'published' && $realEstateProperty->published_at, 404);

        $realEstateProperty->load(['images', 'shop', 'assignedUser']);
        $similar = RealEstateProperty::query()
            ->published()
            ->where('id', '!=', $realEstateProperty->id)
            ->where('shop_id', $shop->id)
            ->where('purpose', $realEstateProperty->purpose)
            ->where('property_type', $realEstateProperty->property_type)
            ->where('city', $realEstateProperty->city)
            ->with(['images', 'shop'])
            ->limit(4)
            ->get();

        return view('front.real_estate.property', [
            'shop' => $shop,
            'property' => $realEstateProperty,
            'similar' => $similar,
        ]);
    }

    public function compare(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(4);

        $properties = RealEstateProperty::query()
            ->published()
            ->whereIn('id', $ids)
            ->whereHas('shop', fn (Builder $query) => $query->where('is_active', true)->where('catalog_type', 'real_estate'))
            ->with(['images', 'shop'])
            ->get()
            ->sortBy(fn (RealEstateProperty $property) => $ids->search($property->id))
            ->values();

        return view('front.real_estate.compare', compact('properties'));
    }

    public function inquiry(Request $request, Shop $shop, RealEstateProperty $realEstateProperty): RedirectResponse
    {
        $this->ensurePublicRealEstateCompany($shop);
        abort_unless($realEstateProperty->status === 'published' && $realEstateProperty->published_at, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'message' => ['nullable', 'string', 'max:1500'],
            'viewing_at' => ['nullable', 'date', 'after:now'],
            'source' => ['nullable', Rule::in(['website', 'facebook', 'whatsapp', 'qr'])],
            'website' => ['nullable', 'max:0'],
        ]);

        RealEstateLead::create([
            'shop_id' => $shop->id,
            'property_id' => $realEstateProperty->id,
            'assigned_user_id' => $realEstateProperty->assigned_user_id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'] ?? null,
            'viewing_at' => $data['viewing_at'] ?? null,
            'source' => $data['source'] ?? 'website',
        ]);

        return back()->with('real_estate_success', 'تم إرسال طلبك للشركة بنجاح، وسيتم التواصل معك قريبًا.');
    }

    public function alert(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('catalog_type', 'real_estate')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::in(['email', 'whatsapp'])],
            'email' => ['nullable', 'required_if:channel,email', 'email', 'max:190'],
            'phone' => ['nullable', 'required_if:channel,whatsapp', 'string', 'max:40'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'max:0'],
        ]);

        RealEstateAlert::create([
            'shop_id' => $data['shop_id'] ?? null,
            'name' => $data['name'],
            'channel' => $data['channel'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'filters' => Arr::only($data['filters'] ?? [], self::FILTER_KEYS),
            'locale' => app()->getLocale(),
        ]);

        return back()->with('real_estate_success', 'تم حفظ تنبيه البحث. سنبلغك عند توفر عقار مطابق.');
    }

    private function renderMarket(Request $request, ?Shop $company = null): View
    {
        $query = RealEstateProperty::query()
            ->published()
            ->whereHas('shop', fn (Builder $builder) => $builder->where('is_active', true)->where('catalog_type', 'real_estate'))
            ->when($company, fn (Builder $builder) => $builder->where('shop_id', $company->id));

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->string('sort')->toString());

        $mapProperties = (clone $query)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('shop:id,name,slug')
            ->limit(250)
            ->get(['id', 'shop_id', 'slug', 'title', 'price', 'currency', 'latitude', 'longitude', 'city']);

        $properties = $query->with(['images', 'shop:id,name,slug,logo,phone,whatsapp'])->paginate(18)->withQueryString();
        $optionBase = RealEstateProperty::query()->published()->when($company, fn (Builder $builder) => $builder->where('shop_id', $company->id));

        return view('front.real_estate.market', [
            'company' => $company,
            'properties' => $properties,
            'mapProperties' => $mapProperties,
            'cities' => (clone $optionBase)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'propertyTypes' => (clone $optionBase)->distinct()->orderBy('property_type')->pluck('property_type'),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('q'), function (Builder $builder) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $builder->where(fn (Builder $nested) => $nested
                    ->where('title', 'like', $term)->orWhere('description', 'like', $term)
                    ->orWhere('city', 'like', $term)->orWhere('neighborhood', 'like', $term)
                    ->orWhere('address', 'like', $term));
            })
            ->when($request->filled('purpose'), fn (Builder $builder) => $builder->where('purpose', $request->string('purpose')))
            ->when($request->filled('property_type'), fn (Builder $builder) => $builder->where('property_type', $request->string('property_type')))
            ->when($request->filled('city'), fn (Builder $builder) => $builder->where('city', $request->string('city')))
            ->when($request->filled('neighborhood'), fn (Builder $builder) => $builder->where('neighborhood', 'like', '%'.$request->string('neighborhood').'%'))
            ->when($request->filled('min_price'), fn (Builder $builder) => $builder->where('price', '>=', max(0, $request->integer('min_price'))))
            ->when($request->filled('max_price'), fn (Builder $builder) => $builder->where('price', '<=', max(0, $request->integer('max_price'))))
            ->when($request->filled('min_area'), fn (Builder $builder) => $builder->where('area', '>=', max(0, $request->integer('min_area'))))
            ->when($request->filled('max_area'), fn (Builder $builder) => $builder->where('area', '<=', max(0, $request->integer('max_area'))))
            ->when($request->filled('rooms'), fn (Builder $builder) => $builder->where('rooms', '>=', $request->float('rooms')))
            ->when($request->filled('bathrooms'), fn (Builder $builder) => $builder->where('bathrooms', '>=', $request->integer('bathrooms')))
            ->when($request->filled('floor'), fn (Builder $builder) => $builder->where('floor', $request->integer('floor')))
            ->when($request->boolean('furnished'), fn (Builder $builder) => $builder->where('furnished', true))
            ->when($request->boolean('parking'), fn (Builder $builder) => $builder->where('parking_spaces', '>', 0))
            ->when($request->boolean('elevator'), fn (Builder $builder) => $builder->where('has_elevator', true))
            ->when($request->boolean('balcony'), fn (Builder $builder) => $builder->where('has_balcony', true))
            ->when($request->boolean('garden'), fn (Builder $builder) => $builder->where('has_garden', true))
            ->when($request->boolean('storage'), fn (Builder $builder) => $builder->where('has_storage', true))
            ->when($request->boolean('air_conditioning'), fn (Builder $builder) => $builder->where('has_air_conditioning', true))
            ->when($request->boolean('new_project'), fn (Builder $builder) => $builder->where('is_new_project', true))
            ->when($request->boolean('available_now'), fn (Builder $builder) => $builder->where(fn (Builder $nested) => $nested->whereNull('available_from')->orWhereDate('available_from', '<=', today())));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'area_desc' => $query->orderByDesc('area'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };
    }

    private function ensurePublicRealEstateCompany(Shop $shop): void
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'real_estate', 404);
    }
}
