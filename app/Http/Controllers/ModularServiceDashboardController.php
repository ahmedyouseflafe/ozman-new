<?php

namespace App\Http\Controllers;

use App\Models\ModularCategory;
use App\Models\ModularService;
use App\Models\ModularServiceImage;
use App\Models\ModularServiceRequest;
use App\Models\Shop;
use App\Services\ModularCatalogDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModularServiceDashboardController extends Controller
{
    public function index(Request $request, Shop $shop, ModularCatalogDefaults $defaults): View
    {
        $this->authorizeCompany($request, $shop);
        $defaults->ensure($shop);

        $categories = $shop->modularCategories()
            ->with(['services' => fn ($query) => $query->withCount(['optionGroups', 'requests'])])
            ->get();
        $serviceRequests = $shop->modularServiceRequests()
            ->with('service.category')
            ->latest()
            ->paginate(20, ['*'], 'requests_page');
        $stats = [
            'categories' => $categories->count(),
            'services' => $categories->sum(fn ($category) => $category->services->count()),
            'options' => $categories->sum(fn ($category) => $category->services->sum('option_groups_count')),
            'new_requests' => $shop->modularServiceRequests()->where('status', 'new')->count(),
        ];

        return view('admin.real_estate.modular.dashboard', compact('shop', 'categories', 'serviceRequests', 'stats'));
    }

    public function createCategory(Request $request, Shop $shop): View
    {
        $this->authorizeCompany($request, $shop);

        return view('admin.real_estate.modular.category_form', [
            'shop' => $shop,
            'category' => new ModularCategory,
        ]);
    }

    public function storeCategory(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeCompany($request, $shop);
        $data = $this->validatedCategory($request, $shop);
        $data['slug'] = $this->uniqueCategorySlug(($data['slug'] ?? null) ?: $data['name'], $shop);
        $data['name_translations'] = $this->translations($data, 'name');
        $data['description_translations'] = $this->translations($data, 'description');
        $data['is_active'] = $request->boolean('is_active');
        $data['position'] = $data['position'] ?? ((int) $shop->modularCategories()->max('position') + 1);
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('modular-catalog/'.$shop->id.'/categories', 'public');
        }

        $shop->modularCategories()->create($this->onlyCategoryColumns($data));

        return redirect()->route('real-estate.dashboard', $shop)->with('status', 'تمت إضافة القسم بنجاح.');
    }

    public function editCategory(Request $request, Shop $shop, ModularCategory $category): View
    {
        $this->authorizeCategory($request, $shop, $category);

        return view('admin.real_estate.modular.category_form', compact('shop', 'category'));
    }

    public function updateCategory(Request $request, Shop $shop, ModularCategory $category): RedirectResponse
    {
        $this->authorizeCategory($request, $shop, $category);
        $data = $this->validatedCategory($request, $shop, $category);
        $data['slug'] = $this->uniqueCategorySlug(($data['slug'] ?? null) ?: $data['name'], $shop, $category);
        $data['name_translations'] = $this->translations($data, 'name');
        $data['description_translations'] = $this->translations($data, 'description');
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('cover_image')) {
            $this->deleteStoredImage($category->cover_image);
            $data['cover_image'] = $request->file('cover_image')->store('modular-catalog/'.$shop->id.'/categories', 'public');
        }
        $category->update($this->onlyCategoryColumns($data));

        return redirect()->route('real-estate.dashboard', $shop)->with('status', 'تم تحديث القسم.');
    }

    public function destroyCategory(Request $request, Shop $shop, ModularCategory $category): RedirectResponse
    {
        $this->authorizeCategory($request, $shop, $category);
        $category->load('services.images');
        foreach ($category->services as $service) {
            $this->deleteServiceFiles($service);
        }
        $this->deleteStoredImage($category->cover_image);
        $category->delete();

        return back()->with('status', 'تم حذف القسم وخدماته.');
    }

    public function createService(Request $request, Shop $shop): View
    {
        $this->authorizeCompany($request, $shop);
        $categories = $shop->modularCategories()->get();
        abort_if($categories->isEmpty(), 422, 'أضف قسمًا قبل إضافة الخدمة.');

        return view('admin.real_estate.modular.service_form', [
            'shop' => $shop,
            'service' => new ModularService,
            'categories' => $categories,
            'selectedCategoryId' => $request->integer('category') ?: $categories->first()->id,
        ]);
    }

    public function storeService(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeCompany($request, $shop);
        $data = $this->validatedService($request, $shop);
        $category = $shop->modularCategories()->findOrFail($data['modular_category_id']);
        $service = DB::transaction(function () use ($request, $shop, $category, $data): ModularService {
            $serviceData = $this->normalizeServiceData($request, $category, $data);
            if ($request->hasFile('cover_image')) {
                $serviceData['cover_image'] = $request->file('cover_image')->store('modular-catalog/'.$shop->id.'/services', 'public');
            }
            $service = $category->services()->create($serviceData);
            $this->replaceOptionGroups($service, $data['option_groups']);
            $this->storeServiceImages($request, $service, $shop);

            return $service;
        });

        return redirect()->route('real-estate.dashboard.services.edit', [$shop, $service])->with('status', 'تمت إضافة الخدمة وخياراتها.');
    }

    public function editService(Request $request, Shop $shop, ModularService $service): View
    {
        $this->authorizeService($request, $shop, $service);
        $service->load(['images', 'optionGroups.values']);

        return view('admin.real_estate.modular.service_form', [
            'shop' => $shop,
            'service' => $service,
            'categories' => $shop->modularCategories()->get(),
            'selectedCategoryId' => $service->modular_category_id,
        ]);
    }

    public function updateService(Request $request, Shop $shop, ModularService $service): RedirectResponse
    {
        $this->authorizeService($request, $shop, $service);
        $data = $this->validatedService($request, $shop, $service);
        $category = $shop->modularCategories()->findOrFail($data['modular_category_id']);
        DB::transaction(function () use ($request, $shop, $category, $service, $data): void {
            $serviceData = $this->normalizeServiceData($request, $category, $data, $service);
            if ($request->hasFile('cover_image')) {
                $this->deleteStoredImage($service->cover_image);
                $serviceData['cover_image'] = $request->file('cover_image')->store('modular-catalog/'.$shop->id.'/services', 'public');
            }
            $service->update($serviceData);
            $this->replaceOptionGroups($service, $data['option_groups']);
            $this->storeServiceImages($request, $service, $shop);
        });

        return back()->with('status', 'تم حفظ الخدمة والخيارات.');
    }

    public function destroyService(Request $request, Shop $shop, ModularService $service): RedirectResponse
    {
        $this->authorizeService($request, $shop, $service);
        $service->load('images');
        $this->deleteServiceFiles($service);
        $service->delete();

        return back()->with('status', 'تم حذف الخدمة.');
    }

    public function destroyServiceImage(Request $request, Shop $shop, ModularServiceImage $image): RedirectResponse
    {
        $image->load('service.category');
        $this->authorizeService($request, $shop, $image->service);
        $this->deleteStoredImage($image->path);
        $image->delete();

        return back()->with('status', 'تم حذف الصورة.');
    }

    public function updateRequest(Request $request, Shop $shop, ModularServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorizeCompany($request, $shop);
        abort_unless($serviceRequest->shop_id === $shop->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'completed', 'cancelled'])]]);
        $serviceRequest->update($data);

        return back()->with('status', 'تم تحديث حالة الطلب.');
    }

    private function validatedCategory(Request $request, Shop $shop, ?ModularCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_he' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('modular_categories', 'slug')->where('shop_id', $shop->id)->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_he' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon_key' => ['nullable', 'string', 'max:40'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function validatedService(Request $request, Shop $shop, ?ModularService $service = null): array
    {
        return $request->validate([
            'modular_category_id' => ['required', 'integer', Rule::exists('modular_categories', 'id')->where('shop_id', $shop->id)],
            'name' => ['required', 'string', 'max:255'],
            'name_he' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:180'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'short_description_he' => ['nullable', 'string', 'max:2000'],
            'short_description_en' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_he' => ['nullable', 'string', 'max:10000'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'option_groups' => ['required', 'array', 'min:1', 'max:30'],
            'option_groups.*.name' => ['required', 'string', 'max:255'],
            'option_groups.*.name_he' => ['nullable', 'string', 'max:255'],
            'option_groups.*.name_en' => ['nullable', 'string', 'max:255'],
            'option_groups.*.help_text' => ['nullable', 'string', 'max:1000'],
            'option_groups.*.help_text_he' => ['nullable', 'string', 'max:1000'],
            'option_groups.*.help_text_en' => ['nullable', 'string', 'max:1000'],
            'option_groups.*.is_required' => ['nullable', 'boolean'],
            'option_groups.*.values' => ['required', 'array', 'min:1', 'max:50'],
            'option_groups.*.values.*.name' => ['required', 'string', 'max:255'],
            'option_groups.*.values.*.name_he' => ['nullable', 'string', 'max:255'],
            'option_groups.*.values.*.name_en' => ['nullable', 'string', 'max:255'],
            'option_groups.*.values.*.is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function normalizeServiceData(Request $request, ModularCategory $category, array $data, ?ModularService $service = null): array
    {
        return [
            'modular_category_id' => $category->id,
            'name' => $data['name'],
            'name_translations' => $this->translations($data, 'name'),
            'slug' => $this->uniqueServiceSlug(($data['slug'] ?? null) ?: $data['name'], $category, $service),
            'short_description' => $data['short_description'] ?? null,
            'short_description_translations' => $this->translations($data, 'short_description'),
            'description' => $data['description'] ?? null,
            'description_translations' => $this->translations($data, 'description'),
            'position' => $data['position'] ?? ($service?->position ?? ((int) $category->services()->max('position') + 1)),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function replaceOptionGroups(ModularService $service, array $groups): void
    {
        $service->optionGroups()->delete();
        $usedGroupKeys = [];
        foreach (array_values($groups) as $groupIndex => $groupData) {
            $groupKey = $this->availableKey(
                $this->stableKey($groupData['name_en'] ?? $groupData['name'], 'group-'.($groupIndex + 1)),
                $usedGroupKeys
            );
            $group = $service->optionGroups()->create([
                'key' => $groupKey,
                'name' => $groupData['name'],
                'name_translations' => $this->translations($groupData, 'name'),
                'help_text' => $groupData['help_text'] ?? null,
                'help_text_translations' => $this->translations($groupData, 'help_text'),
                'is_required' => filter_var($groupData['is_required'] ?? true, FILTER_VALIDATE_BOOL),
                'position' => $groupIndex + 1,
            ]);
            $usedValueKeys = [];
            foreach (array_values($groupData['values']) as $valueIndex => $valueData) {
                $valueKey = $this->availableKey(
                    $this->stableKey($valueData['name_en'] ?? $valueData['name'], 'value-'.($valueIndex + 1)),
                    $usedValueKeys
                );
                $group->values()->create([
                    'key' => $valueKey,
                    'name' => $valueData['name'],
                    'name_translations' => $this->translations($valueData, 'name'),
                    'is_default' => filter_var($valueData['is_default'] ?? false, FILTER_VALIDATE_BOOL),
                    'is_active' => true,
                    'position' => $valueIndex + 1,
                ]);
            }
        }
    }

    private function storeServiceImages(Request $request, ModularService $service, Shop $shop): void
    {
        $position = (int) $service->images()->max('position');
        foreach ($request->file('images', []) as $file) {
            $service->images()->create([
                'path' => $file->store('modular-catalog/'.$shop->id.'/services/'.$service->id, 'public'),
                'alt_text' => $service->name,
                'position' => ++$position,
            ]);
        }
    }

    private function translations(array $data, string $field): array
    {
        return array_filter([
            'he' => $data[$field.'_he'] ?? null,
            'en' => $data[$field.'_en'] ?? null,
        ], fn ($value) => filled($value));
    }

    private function onlyCategoryColumns(array $data): array
    {
        return collect($data)->only([
            'name', 'name_translations', 'slug', 'description', 'description_translations',
            'icon_key', 'cover_image', 'position', 'is_active',
        ])->all();
    }

    private function uniqueCategorySlug(string $value, Shop $shop, ?ModularCategory $category = null): string
    {
        return $this->uniqueSlug($value, fn ($slug) => $shop->modularCategories()->where('slug', $slug)->when($category, fn ($query) => $query->where('id', '!=', $category->id))->exists());
    }

    private function uniqueServiceSlug(string $value, ModularCategory $category, ?ModularService $service = null): string
    {
        return $this->uniqueSlug($value, fn ($slug) => $category->services()->where('slug', $slug)->when($service, fn ($query) => $query->where('id', '!=', $service->id))->exists());
    }

    private function uniqueSlug(string $value, callable $exists): string
    {
        $base = Str::slug($value) ?: $this->stableKey($value, 'item');
        $slug = $base;
        $counter = 2;
        while ($exists($slug)) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function stableKey(string $value, string $fallback): string
    {
        $key = Str::slug($value);

        return $key !== '' ? $key : $fallback;
    }

    private function availableKey(string $base, array &$used): string
    {
        $key = $base;
        $counter = 2;
        while (in_array($key, $used, true)) {
            $key = $base.'-'.$counter++;
        }
        $used[] = $key;

        return $key;
    }

    private function deleteServiceFiles(ModularService $service): void
    {
        $this->deleteStoredImage($service->cover_image);
        foreach ($service->images as $image) {
            $this->deleteStoredImage($image->path);
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function authorizeCompany(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
        abort_unless($shop->catalog_type === 'real_estate', 404);
    }

    private function authorizeCategory(Request $request, Shop $shop, ModularCategory $category): void
    {
        $this->authorizeCompany($request, $shop);
        abort_unless($category->shop_id === $shop->id, 404);
    }

    private function authorizeService(Request $request, Shop $shop, ModularService $service): void
    {
        $this->authorizeCompany($request, $shop);
        $service->loadMissing('category');
        abort_unless($service->category?->shop_id === $shop->id, 404);
    }
}
