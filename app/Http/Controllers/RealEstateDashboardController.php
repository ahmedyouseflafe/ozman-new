<?php

namespace App\Http\Controllers;

use App\Models\RealEstateLead;
use App\Models\RealEstateProperty;
use App\Models\RealEstatePropertyImage;
use App\Models\Shop;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class RealEstateDashboardController extends Controller
{
    public function index(Request $request, Shop $shop): View
    {
        $this->authorizeCompany($request, $shop);

        $propertiesQuery = $shop->realEstateProperties()->with('images')->withCount('leads');
        $status = $request->string('status')->toString();
        $properties = $propertiesQuery
            ->when(in_array($status, RealEstateProperty::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($nested) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $nested->where('title', 'like', $term)->orWhere('reference', 'like', $term)->orWhere('city', 'like', $term);
            }))
            ->latest()
            ->paginate(20, ['*'], 'properties_page')
            ->withQueryString();

        $leadsQuery = $shop->realEstateLeads()->with('property:id,title,slug')->latest();
        $leadStatus = $request->string('lead_status')->toString();
        $leads = $leadsQuery
            ->when(in_array($leadStatus, ['new', 'contacted', 'viewing', 'won', 'lost'], true), fn ($query) => $query->where('status', $leadStatus))
            ->paginate(20, ['*'], 'leads_page')
            ->withQueryString();

        $stats = [
            'all' => $shop->realEstateProperties()->count(),
            'published' => $shop->realEstateProperties()->published()->count(),
            'available' => $shop->realEstateProperties()->whereIn('status', ['draft', 'published'])->count(),
            'closed' => $shop->realEstateProperties()->whereIn('status', ['sold', 'rented'])->count(),
            'new_leads' => $shop->realEstateLeads()->where('status', 'new')->count(),
            'viewings' => $shop->realEstateLeads()->whereNotNull('viewing_at')->where('viewing_at', '>=', now())->count(),
            'alerts' => $shop->realEstateAlerts()->where('is_active', true)->count(),
        ];

        return view('admin.real_estate.dashboard', compact('shop', 'properties', 'leads', 'stats', 'status', 'leadStatus'));
    }

    public function create(Request $request, Shop $shop): View
    {
        $this->authorizeCompany($request, $shop);

        return view('admin.real_estate.form', ['shop' => $shop, 'property' => new RealEstateProperty]);
    }

    public function store(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeCompany($request, $shop);
        $data = $this->validatedProperty($request, $shop);

        $property = DB::transaction(function () use ($request, $shop, $data): RealEstateProperty {
            $property = $shop->realEstateProperties()->create($this->normalizePropertyData($data));
            $this->storeImages($request, $property);

            return $property;
        });

        return redirect()->route('real-estate.dashboard.properties.edit', [$shop, $property])->with('status', 'تمت إضافة العقار بنجاح.');
    }

    public function edit(Request $request, Shop $shop, RealEstateProperty $property): View
    {
        $this->authorizeProperty($request, $shop, $property);
        $property->load('images');

        return view('admin.real_estate.form', compact('shop', 'property'));
    }

    public function update(Request $request, Shop $shop, RealEstateProperty $property): RedirectResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        $data = $this->validatedProperty($request, $shop, $property);

        DB::transaction(function () use ($request, $property, $data): void {
            $property->update($this->normalizePropertyData($data, $property));
            $this->storeImages($request, $property);
        });

        return back()->with('status', 'تم حفظ تعديلات العقار.');
    }

    public function destroy(Request $request, Shop $shop, RealEstateProperty $property): RedirectResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $property->delete();

        return redirect()->route('real-estate.dashboard', $shop)->with('status', 'تم حذف العقار وصوره.');
    }

    public function destroyImage(Request $request, Shop $shop, RealEstateProperty $property, RealEstatePropertyImage $image): RedirectResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        abort_unless($image->property_id === $property->id, 404);
        Storage::disk('public')->delete($image->path);
        $wasCover = $image->is_cover;
        $image->delete();
        if ($wasCover) {
            $property->images()->orderBy('position')->first()?->update(['is_cover' => true]);
        }

        return back()->with('status', 'تم حذف الصورة.');
    }

    public function coverImage(Request $request, Shop $shop, RealEstateProperty $property, RealEstatePropertyImage $image): RedirectResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        abort_unless($image->property_id === $property->id, 404);
        DB::transaction(function () use ($property, $image): void {
            $property->images()->update(['is_cover' => false]);
            $image->update(['is_cover' => true]);
        });

        return back()->with('status', 'تم اعتماد صورة الغلاف.');
    }

    public function moveImage(Request $request, Shop $shop, RealEstateProperty $property, RealEstatePropertyImage $image): RedirectResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        abort_unless($image->property_id === $property->id, 404);
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $operator = $data['direction'] === 'up' ? '<' : '>';
        $order = $data['direction'] === 'up' ? 'desc' : 'asc';
        $neighbor = $property->images()->where('position', $operator, $image->position)->orderBy('position', $order)->first();

        if ($neighbor) {
            DB::transaction(function () use ($image, $neighbor): void {
                $position = $image->position;
                $image->update(['position' => $neighbor->position]);
                $neighbor->update(['position' => $position]);
            });
        }

        return back()->with('status', 'تم تحديث ترتيب الصور.');
    }

    public function facebookImages(Request $request, Shop $shop, RealEstateProperty $property): BinaryFileResponse
    {
        $this->authorizeProperty($request, $shop, $property);
        $data = $request->validate([
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['integer'],
        ]);
        $selectedIds = collect($data['images'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $images = $property->images()
            ->when($selectedIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $selectedIds))
            ->orderBy('position')
            ->get();
        abort_if($images->isEmpty(), 422, 'لا توجد صور متاحة للتحميل.');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'ozman-property-');
        abort_if($temporaryPath === false, 500);
        $zip = new ZipArchive;
        abort_unless($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);
        foreach ($images as $index => $image) {
            if (! Storage::disk('public')->exists($image->path)) {
                continue;
            }
            $extension = pathinfo($image->path, PATHINFO_EXTENSION) ?: 'jpg';
            $zip->addFile(Storage::disk('public')->path($image->path), sprintf('%02d-%s.%s', $index + 1, $property->slug, $extension));
        }
        $zip->close();

        return response()->download($temporaryPath, $property->slug.'-facebook-images.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function updateLead(Request $request, Shop $shop, RealEstateLead $lead): RedirectResponse
    {
        $this->authorizeCompany($request, $shop);
        abort_unless($lead->shop_id === $shop->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'viewing', 'won', 'lost'])],
            'viewing_at' => ['nullable', 'date'],
        ]);
        $lead->update($data);

        return back()->with('status', 'تم تحديث حالة العميل.');
    }

    public function qr(Shop $shop): Response
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'real_estate', 404);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(600, 2), new SvgImageBackEnd)))
            ->writeString(route('real-estate.company', $shop));

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function validatedProperty(Request $request, Shop $shop, ?RealEstateProperty $property = null): array
    {
        return $request->validate([
            'reference' => ['nullable', 'string', 'max:80', Rule::unique('real_estate_properties', 'reference')->where('shop_id', $shop->id)->ignore($property)],
            'slug' => ['nullable', 'alpha_dash', 'max:180', Rule::unique('real_estate_properties', 'slug')->where('shop_id', $shop->id)->ignore($property)],
            'purpose' => ['required', Rule::in(RealEstateProperty::PURPOSES)],
            'property_type' => ['required', Rule::in(['apartment', 'villa', 'house', 'land', 'office', 'shop', 'warehouse', 'building', 'commercial'])],
            'title' => ['required', 'string', 'max:255'],
            'title_he' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_he' => ['nullable', 'string', 'max:10000'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['required', Rule::in(['ILS', 'USD', 'JOD', 'EUR'])],
            'monthly_fees' => ['nullable', 'numeric', 'min:0'],
            'city' => ['required', 'string', 'max:120'],
            'neighborhood' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'rooms' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'floor' => ['nullable', 'integer', 'min:-10', 'max:300'],
            'building_floors' => ['nullable', 'integer', 'min:0', 'max:300'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:100'],
            'available_from' => ['nullable', 'date'],
            'status' => ['required', Rule::in(RealEstateProperty::STATUSES)],
            'amenities_text' => ['nullable', 'string', 'max:3000'],
            'nearby_places_text' => ['nullable', 'string', 'max:3000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'virtual_tour_url' => ['nullable', 'url', 'active_url', 'max:500'],
            'furnished' => ['nullable', 'boolean'],
            'is_new_project' => ['nullable', 'boolean'],
            'has_elevator' => ['nullable', 'boolean'],
            'has_balcony' => ['nullable', 'boolean'],
            'has_garden' => ['nullable', 'boolean'],
            'has_storage' => ['nullable', 'boolean'],
            'has_air_conditioning' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], $this->arabicValidationMessages(), $this->arabicPropertyAttributes());
    }

    private function arabicValidationMessages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'array' => 'حقل :attribute يجب أن يكون قائمة.',
            'boolean' => 'القيمة المحددة في :attribute غير صحيحة.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'url' => 'حقل :attribute يجب أن يكون رابطًا صحيحًا يبدأ بـ http:// أو https://.',
            'active_url' => 'حقل :attribute يجب أن يحتوي على رابط فعّال ومتاح على الإنترنت.',
            'alpha_dash' => 'حقل :attribute يجب أن يحتوي على أحرف إنجليزية أو أرقام أو شرطات فقط.',
            'unique' => 'قيمة :attribute مستخدمة مسبقًا، اختر قيمة أخرى.',
            'in' => 'القيمة المحددة في :attribute غير صحيحة.',
            'between.numeric' => 'قيمة :attribute يجب أن تكون بين :min و:max.',
            'min.numeric' => 'قيمة :attribute يجب ألا تقل عن :min.',
            'max.numeric' => 'قيمة :attribute يجب ألا تزيد عن :max.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'max.array' => 'لا يمكن إضافة أكثر من :max عنصرًا في :attribute.',
            'max.file' => 'حجم كل ملف في :attribute يجب ألا يتجاوز :max كيلوبايت.',
            'image' => 'الملف المرفوع في :attribute يجب أن يكون صورة.',
            'mimes' => 'صيغة الصورة في :attribute يجب أن تكون: :values.',
        ];
    }

    private function arabicPropertyAttributes(): array
    {
        return [
            'reference' => 'المرجع الداخلي',
            'slug' => 'رابط العقار المخصص',
            'purpose' => 'الغرض من العقار',
            'property_type' => 'نوع العقار',
            'title' => 'عنوان العقار بالعربية',
            'title_he' => 'عنوان العقار بالعبرية',
            'title_en' => 'عنوان العقار بالإنجليزية',
            'description' => 'الوصف بالعربية',
            'description_he' => 'الوصف بالعبرية',
            'description_en' => 'الوصف بالإنجليزية',
            'price' => 'السعر',
            'currency' => 'العملة',
            'monthly_fees' => 'الرسوم الشهرية',
            'city' => 'المدينة',
            'neighborhood' => 'المنطقة أو الحي',
            'address' => 'العنوان',
            'latitude' => 'موقع العقار على الخريطة',
            'longitude' => 'موقع العقار على الخريطة',
            'rooms' => 'عدد الغرف',
            'bedrooms' => 'غرف النوم',
            'bathrooms' => 'الحمامات',
            'area' => 'المساحة',
            'floor' => 'الطابق',
            'building_floors' => 'عدد طوابق المبنى',
            'parking_spaces' => 'مواقف السيارات',
            'available_from' => 'تاريخ توفر العقار',
            'status' => 'حالة العقار',
            'amenities_text' => 'المزايا الإضافية',
            'nearby_places_text' => 'الأماكن القريبة',
            'video_url' => 'رابط الفيديو',
            'virtual_tour_url' => 'رابط الجولة الافتراضية',
            'images' => 'صور العقار',
            'images.*' => 'صور العقار',
        ];
    }

    private function normalizePropertyData(array $data, ?RealEstateProperty $property = null): array
    {
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        $data['title_translations'] = array_filter(['he' => $data['title_he'] ?? null, 'en' => $data['title_en'] ?? null]);
        $data['description_translations'] = array_filter(['he' => $data['description_he'] ?? null, 'en' => $data['description_en'] ?? null]);
        $data['amenities'] = $this->lines($data['amenities_text'] ?? null);
        $data['nearby_places'] = $this->lines($data['nearby_places_text'] ?? null);
        foreach (['furnished', 'is_new_project', 'has_elevator', 'has_balcony', 'has_garden', 'has_storage', 'has_air_conditioning', 'is_featured'] as $boolean) {
            $data[$boolean] = (bool) ($data[$boolean] ?? false);
        }
        $data['published_at'] = $data['status'] === 'published' ? ($property?->published_at ?? now()) : null;

        return Arr::except($data, ['title_he', 'title_en', 'description_he', 'description_en', 'amenities_text', 'nearby_places_text', 'images']);
    }

    private function storeImages(Request $request, RealEstateProperty $property): void
    {
        $position = (int) $property->images()->max('position');
        $hasCover = $property->images()->where('is_cover', true)->exists();
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('real-estate/'.$property->shop_id.'/'.$property->id, 'public');
            $property->images()->create([
                'path' => $path,
                'alt_text' => $property->title,
                'position' => ++$position,
                'is_cover' => ! $hasCover,
            ]);
            $hasCover = true;
        }
    }

    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))->map(fn ($line) => trim($line))->filter()->values()->all();
    }

    private function authorizeCompany(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
        abort_unless($shop->catalog_type === 'real_estate', 404);
    }

    private function authorizeProperty(Request $request, Shop $shop, RealEstateProperty $property): void
    {
        $this->authorizeCompany($request, $shop);
        abort_unless($property->shop_id === $shop->id, 404);
    }
}
