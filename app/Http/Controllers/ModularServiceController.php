<?php

namespace App\Http\Controllers;

use App\Models\ModularCategory;
use App\Models\ModularService;
use App\Models\ModularServiceRequest;
use App\Models\Shop;
use App\Services\ModularCatalogDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ModularServiceController extends Controller
{
    public function category(
        Request $request,
        Shop $shop,
        ModularCategory $modularCategory,
        ModularCatalogDefaults $defaults
    ): RedirectResponse {
        $category = $modularCategory;
        $this->ensureCompany($shop);
        $defaults->ensure($shop);
        $this->ensureCategory($shop, $category);

        if ($redirect = $this->canonicalRedirect($request, $shop, $category)) {
            return $redirect;
        }

        return redirect()->route('real-estate.company', [
            'shop' => $shop,
            'category' => $category->slug,
        ]);
    }

    public function show(
        Request $request,
        Shop $shop,
        ModularCategory $modularCategory,
        ModularService $service,
        ModularCatalogDefaults $defaults
    ): RedirectResponse {
        $category = $modularCategory;
        $this->ensureCompany($shop);
        $defaults->ensure($shop);
        $this->ensureService($shop, $category, $service);

        if ($redirect = $this->canonicalRedirect($request, $shop, $category, $service)) {
            return $redirect;
        }

        abort_unless($service->is_active && $category->is_active, 404);

        return redirect()->route('real-estate.company', [
            'shop' => $shop,
            'category' => $category->slug,
            'service' => $service->slug,
        ]);
    }

    public function whatsapp(
        Request $request,
        Shop $shop,
        ModularCategory $modularCategory,
        ModularService $service
    ): RedirectResponse {
        $category = $modularCategory;
        $this->ensureCompany($shop);
        $this->ensureService($shop, $category, $service);
        abort_unless($service->is_active && $category->is_active, 404);

        $service->load(['optionGroups.values' => fn ($query) => $query->where('is_active', true), 'category.shop.social']);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'options' => ['required', 'array'],
            'website' => ['nullable', 'max:0'],
        ]);

        $selections = $this->validatedSelections($service, $data['options']);
        $companyWhatsapp = $this->whatsappNumber((string) ($shop->whatsapp ?: $shop->social?->whatsapp ?: $shop->phone));
        if (! $companyWhatsapp) {
            throw ValidationException::withMessages([
                'customer_phone' => 'رقم واتساب الشركة غير مُسجّل. يرجى التواصل مع الشركة مباشرة.',
            ]);
        }

        $serviceRequest = ModularServiceRequest::create([
            'shop_id' => $shop->id,
            'modular_service_id' => $service->id,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'selections' => $selections->values()->all(),
            'locale' => app()->getLocale(),
            'status' => 'new',
            'whatsapp_opened_at' => now(),
        ]);

        $message = $this->whatsappMessage($shop, $category, $service, $serviceRequest, $selections);

        return redirect()->away('https://wa.me/'.$companyWhatsapp.'?text='.rawurlencode($message));
    }

    private function validatedSelections(ModularService $service, array $submitted): Collection
    {
        return $service->optionGroups->map(function ($group) use ($submitted): array {
            $valueId = $submitted[$group->id] ?? null;
            $value = $group->values->firstWhere('id', (int) $valueId);

            if ($group->is_required && ! $value) {
                throw ValidationException::withMessages([
                    "options.{$group->id}" => 'يرجى اختيار '.$group->localized('name').'.',
                ]);
            }

            return [
                'group_key' => $group->key,
                'group_name' => $group->localized('name'),
                'value_key' => $value?->key,
                'value_name' => $value?->localized('name'),
            ];
        })->filter(fn (array $selection) => filled($selection['value_key']));
    }

    private function whatsappMessage(
        Shop $shop,
        ModularCategory $category,
        ModularService $service,
        ModularServiceRequest $serviceRequest,
        Collection $selections
    ): string {
        $lines = [
            'مرحباً '.$shop->name.'،',
            'أرغب بطلب: *'.$service->localized('name').'*',
            'القسم: '.$category->localized('name'),
            '',
            '*المواصفات المختارة:*',
        ];

        foreach ($selections as $selection) {
            $lines[] = '• '.$selection['group_name'].': '.$selection['value_name'];
        }

        $lines = array_merge($lines, [
            '',
            '*بيانات العميل:*',
            'الاسم: '.$serviceRequest->customer_name,
            'الهاتف: '.$serviceRequest->customer_phone,
        ]);

        if ($serviceRequest->city) {
            $lines[] = 'المدينة/المنطقة: '.$serviceRequest->city;
        }
        if ($serviceRequest->notes) {
            $lines[] = 'ملاحظات: '.$serviceRequest->notes;
        }

        $lines[] = '';
        $lines[] = 'رقم الطلب: #'.$serviceRequest->id;
        $lines[] = $service->publicUrl();

        return implode("\n", $lines);
    }

    private function whatsappNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?: '';
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $countryCode = preg_replace('/\D+/', '', (string) config('services.whatsapp_cloud.default_country_code', '972')) ?: '972';

            return $countryCode.ltrim($digits, '0');
        }

        return $digits;
    }

    private function ensureCompany(Shop $shop): void
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'real_estate', 404);
    }

    private function ensureCategory(Shop $shop, ModularCategory $category): void
    {
        abort_unless($category->shop_id === $shop->id && $category->is_active, 404);
    }

    private function ensureService(Shop $shop, ModularCategory $category, ModularService $service): void
    {
        $this->ensureCategory($shop, $category);
        abort_unless($service->modular_category_id === $category->id, 404);
    }

    private function canonicalRedirect(
        Request $request,
        Shop $shop,
        ModularCategory $category,
        ?ModularService $service = null
    ): ?RedirectResponse {
        if ($request->route()?->originalParameter('shop') === $shop->slug) {
            return null;
        }

        return $service
            ? redirect()->route('real-estate.services.show', [$shop, $category, $service], 301)
            : redirect()->route('real-estate.services.category', [$shop, $category], 301);
    }
}
