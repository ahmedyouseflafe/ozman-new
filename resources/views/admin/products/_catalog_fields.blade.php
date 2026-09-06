@php
    $savedCatalogAttributes = old('catalog_attributes', isset($product) ? ($product->catalog_attributes ?? []) : []);
@endphp

<style>
    /*
     * form-grid has an explicit display:grid in the product pages. Some
     * browsers therefore render catalog groups even when the hidden attribute
     * is present. Keep every non-selected business type completely out of the
     * layout.
     */
    .catalog-fields[hidden],
    #productVariantsPanel[hidden] {
        display: none !important;
    }
</style>

<section class="form-section" id="catalogSpecificSection">
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-adjustments-horizontal"></i></div>
        <div>
            <h2 id="catalogSpecificTitle">{{ ($isRestaurantForm ?? false) ? 'تفاصيل الوجبة' : 'تفاصيل المنتج حسب نوع المتجر' }}</h2>
            <p id="catalogSpecificDescription">{{ ($isRestaurantForm ?? false) ? 'بيانات الوجبة المخصصة لمنيو المطعم.' : 'اختر المتجر لعرض الحقول المناسبة لنشاطه.' }}</p>
        </div>
    </div>

    @foreach($catalogTypes as $typeKey => $type)
        @continue(($lockShopSelection ?? false) && $formShop && $typeKey !== ($formShop->catalog_type ?: 'general'))
        <div class="catalog-fields form-grid" data-catalog-fields="{{ $typeKey }}" hidden>
            @foreach($type['fields'] ?? [] as $fieldKey => $field)
                @php
                    $value = data_get($savedCatalogAttributes, $fieldKey);
                    if (($field['type'] ?? '') === 'list' && is_array($value)) {
                        $value = implode(', ', $value);
                    }
                @endphp
                <div class="form-group {{ ($field['type'] ?? '') === 'textarea' ? 'full' : '' }}">
                    <label class="form-label" for="catalog_{{ $typeKey }}_{{ $fieldKey }}">
                        {{ $field['label'] }}
                    </label>

                    @if(($field['type'] ?? '') === 'select')
                        <select id="catalog_{{ $typeKey }}_{{ $fieldKey }}"
                            name="catalog_attributes[{{ $fieldKey }}]" disabled>
                            <option value="">اختر</option>
                            @foreach($field['options'] ?? [] as $option)
                                <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif(($field['type'] ?? '') === 'textarea')
                        <textarea id="catalog_{{ $typeKey }}_{{ $fieldKey }}"
                            name="catalog_attributes[{{ $fieldKey }}]" disabled
                            placeholder="{{ $field['placeholder'] ?? '' }}">{{ $value }}</textarea>
                    @elseif(($field['type'] ?? '') === 'boolean')
                        <label class="switch-card" style="min-height:54px">
                            <input type="hidden" name="catalog_attributes[{{ $fieldKey }}]" value="0" disabled>
                            <input id="catalog_{{ $typeKey }}_{{ $fieldKey }}" type="checkbox"
                                name="catalog_attributes[{{ $fieldKey }}]" value="1" @checked((bool) $value) disabled>
                            <span>{{ $field['label'] }}</span>
                        </label>
                    @else
                        <input id="catalog_{{ $typeKey }}_{{ $fieldKey }}"
                            type="{{ ($field['type'] ?? '') === 'number' ? 'number' : 'text' }}"
                            min="{{ ($field['type'] ?? '') === 'number' ? '0' : '' }}"
                            name="catalog_attributes[{{ $fieldKey }}]"
                            value="{{ $value }}"
                            placeholder="{{ $field['placeholder'] ?? (($field['type'] ?? '') === 'list' ? 'افصل القيم بفاصلة' : '') }}"
                            disabled>
                        @if(($field['type'] ?? '') === 'list')
                            <small style="color:rgba(255,255,255,.5)">اكتب أكثر من قيمة وافصل بينها بفاصلة.</small>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    @php
        $variantRows = old('variants', isset($product) ? $product->variants->toArray() : []);
        $variantRows = count($variantRows) ? $variantRows : [['size' => '', 'storage' => '', 'ram' => '', 'color' => '', 'color_name' => '', 'sku' => '', 'price' => '', 'quantity' => 0, 'is_active' => true]];
    @endphp
    <div id="productVariantsPanel" hidden style="margin-top:20px;padding-top:18px;border-top:1px solid rgba(255,255,255,.1)">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px">
            <div>
                <h3 id="productVariantsTitle" style="margin:0;color:#00e5ff">مخزون الخيارات</h3>
                <small style="color:rgba(255,255,255,.55)">كل صف يمثل خيارًا مستقلاً، والكمية الإجمالية تُحسب تلقائيًا.</small>
            </div>
            <button type="button" class="btn" id="addProductVariant"><i class="ti ti-plus"></i> إضافة خيار</button>
        </div>
        <div id="productVariantsList" style="display:grid;gap:10px">
            @foreach($variantRows as $index => $variant)
                <div data-variant-row class="form-grid" style="padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:16px">
                    <div class="form-group"><label class="form-label">المقاس/النمرة</label><input name="variants[{{ $index }}][size]" value="{{ data_get($variant, 'size') }}"></div>
                    <div class="form-group" data-electronics-variant data-variant-shared><label class="form-label">سعة التخزين</label><input name="variants[{{ $index }}][storage]" value="{{ data_get($variant, 'storage') }}" placeholder="128GB"></div>
                    <div class="form-group" data-electronics-variant data-variant-shared><label class="form-label">الرام</label><input name="variants[{{ $index }}][ram]" value="{{ data_get($variant, 'ram') }}" placeholder="8GB"></div>
                    <div class="form-group" data-standard-color><label class="form-label">اللون</label><input name="variants[{{ $index }}][color]" value="{{ data_get($variant, 'color') }}"></div>
                    <div class="form-group" data-electronics-color hidden><label class="form-label">لون الجهاز</label><input type="color" name="variants[{{ $index }}][color]" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) data_get($variant, 'color')) ? data_get($variant, 'color') : '#000000' }}" disabled style="height:54px;padding:6px;cursor:pointer"></div>
                    <div class="form-group" data-electronics-variant><label class="form-label">اسم اللون للزبون والطلب</label><input name="variants[{{ $index }}][color_name]" value="{{ data_get($variant, 'color_name') }}" placeholder="مثال: أسود تيتانيوم"></div>
                    <div class="form-group"><label class="form-label">SKU للخيار</label><input name="variants[{{ $index }}][sku]" value="{{ data_get($variant, 'sku') }}" dir="ltr"></div>
                    <div class="form-group" data-variant-shared><label class="form-label" data-variant-price-label>سعر خاص (اختياري)</label><input type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ data_get($variant, 'price') }}"></div>
                    <div class="form-group"><label class="form-label">الكمية</label><input type="number" min="0" name="variants[{{ $index }}][quantity]" value="{{ data_get($variant, 'quantity', 0) }}"></div>
                    <div class="form-group" style="justify-content:flex-end"><input type="hidden" name="variants[{{ $index }}][is_active]" value="0"><label class="visibility-check"><input type="checkbox" name="variants[{{ $index }}][is_active]" value="1" @checked((bool) data_get($variant, 'is_active', true))>متوفر للبيع</label><button type="button" class="btn" data-add-variant-color hidden><i class="ti ti-palette"></i> إضافة لون لنفس السعة</button><button type="button" class="btn" data-remove-variant style="color:#ff6070">حذف الخيار</button></div>
                </div>
            @endforeach
        </div>
    </div>

    @php
        $restaurantSizes = collect(data_get($savedCatalogAttributes, 'meal_size_prices', []))
            ->map(fn($item) => array_pad(explode(':', (string) $item, 2), 2, ''));
        $restaurantAddons = collect(data_get($savedCatalogAttributes, 'addon_prices', []))
            ->map(fn($item) => array_pad(explode(':', (string) $item, 2), 2, ''));
        $restaurantRemovable = collect(data_get($savedCatalogAttributes, 'removable_ingredients', []));
        $restaurantBasePrice = old('customer_package_price', isset($product) ? $product->customer_package_price : null);
    @endphp
    <div id="restaurantMenuEditor" hidden style="margin-top:20px;display:none">
        <div class="section-head">
            <div class="section-icon"><i class="ti ti-tools-kitchen-2"></i></div>
            <div><h2>تسعير وتوفر الوجبة</h2><p>أدخل سعر الوجبة، الأحجام، الإضافات والمكونات التي يستطيع الزبون حذفها.</p></div>
        </div>
        <input type="hidden" name="show_customer_package_price" value="1" data-restaurant-input>
        @foreach(['show_customer_carton_price','show_customer_pallet_price','show_package_price','show_carton_price','show_pallet_price'] as $visibilityField)
            <input type="hidden" name="{{ $visibilityField }}" value="0" data-restaurant-input>
        @endforeach
        <div class="form-grid">
            <div class="form-group full"><label class="form-label">السعر الأساسي للوجبة</label><input type="number" step="0.01" min="0" name="customer_package_price" value="{{ $restaurantBasePrice }}" data-restaurant-input required></div>
        </div>
        <h3 style="color:#00e5ff;margin-top:22px">أحجام الوجبة وأسعارها</h3>
        <div class="form-grid">
            @for($i=0;$i<5;$i++)
                <div class="form-group"><label class="form-label">الحجم {{ $i+1 }}</label><input data-restaurant-input data-priced-name="size" value="{{ data_get($restaurantSizes, "$i.0") }}" placeholder="مثال: كبير"></div>
                <div class="form-group"><label class="form-label">سعر الحجم</label><input data-restaurant-input data-priced-value="size" type="number" min="0" step="0.01" value="{{ data_get($restaurantSizes, "$i.1") }}"></div>
                <input type="hidden" name="catalog_attributes[meal_size_prices][]" data-restaurant-input data-priced-output="size">
            @endfor
        </div>
        <h3 style="color:#00e5ff;margin-top:22px">الإضافات وأسعارها</h3>
        <div class="form-grid">
            @for($i=0;$i<6;$i++)
                <div class="form-group"><label class="form-label">الإضافة {{ $i+1 }}</label><input data-restaurant-input data-priced-name="addon" value="{{ data_get($restaurantAddons, "$i.0") }}" placeholder="مثال: جبنة"></div>
                <div class="form-group"><label class="form-label">سعر الإضافة</label><input data-restaurant-input data-priced-value="addon" type="number" min="0" step="0.01" value="{{ data_get($restaurantAddons, "$i.1") }}"></div>
                <input type="hidden" name="catalog_attributes[addon_prices][]" data-restaurant-input data-priced-output="addon">
            @endfor
        </div>
        <div class="form-group full" style="margin-top:20px"><label class="form-label">المكونات التي يمكن للزبون حذفها</label>
            <input name="catalog_attributes[removable_ingredients]" data-restaurant-input value="{{ $restaurantRemovable->implode(', ') }}" placeholder="مثال: بصل، مخلل، بندورة">
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const shopSelect = document.getElementById('shop_id');
        const groups = [...document.querySelectorAll('[data-catalog-fields]')];
        const definitions = @json(collect($catalogTypes)->map(fn ($type) => [
            'label' => $type['label'],
            'description' => $type['description'],
        ]));
        const title = document.getElementById('catalogSpecificTitle');
        const description = document.getElementById('catalogSpecificDescription');
        const variantsPanel = document.getElementById('productVariantsPanel');
        const variantsList = document.getElementById('productVariantsList');
        const addVariant = document.getElementById('addProductVariant');
        const restaurantEditor = document.getElementById('restaurantMenuEditor');
        const legacyPricing = document.getElementById('legacyProductPricingSection');
        const legacyCampaigns = [...document.querySelectorAll('.form-section')]
            .find((section) => section.querySelector('h2')?.textContent.trim() === 'حملات المنتج');
        let variantIndex = variantsList?.querySelectorAll('[data-variant-row]').length || 0;

        function updateCatalogFields() {
            const type = shopSelect?.selectedOptions?.[0]?.dataset?.catalogType
                || shopSelect?.dataset?.catalogType
                || 'general';
            groups.forEach((group) => {
                const active = group.dataset.catalogFields === type;
                group.hidden = !active;
                group.style.display = active ? '' : 'none';
                group.setAttribute('aria-hidden', active ? 'false' : 'true');
                group.querySelectorAll('input, select, textarea').forEach((field) => field.disabled = !active);
            });
            if (definitions[type]) {
                title.textContent = type === 'restaurant' ? 'تفاصيل الوجبة' : `تفاصيل ${definitions[type].label}`;
                description.textContent = type === 'restaurant' ? 'بيانات الوجبة المخصصة لمنيو المطعم.' : definitions[type].description;
            }
            const usesVariants = ['clothing', 'shoes', 'electronics'].includes(type);
            variantsPanel.hidden = !usesVariants;
            variantsPanel.style.display = usesVariants ? '' : 'none';
            variantsPanel.setAttribute('aria-hidden', usesVariants ? 'false' : 'true');
            variantsPanel.querySelectorAll('input, button').forEach((field) => field.disabled = !usesVariants);
            const isElectronics = type === 'electronics';
            document.getElementById('productVariantsTitle').textContent = isElectronics
                ? 'مخزون التخزين والرام والألوان'
                : 'مخزون المقاسات والألوان';
            if (addVariant) {
                addVariant.innerHTML = isElectronics
                    ? '<i class="ti ti-plus"></i> إضافة سعة جديدة'
                    : '<i class="ti ti-plus"></i> إضافة خيار';
            }
            variantsPanel.querySelectorAll('[data-variant-price-label]').forEach((label) => {
                label.textContent = isElectronics ? 'سعر الخيار (مطلوب)' : 'سعر خاص (اختياري)';
            });
            variantsPanel.querySelectorAll('[data-electronics-variant]').forEach((field) => {
                field.hidden = !isElectronics;
                field.querySelectorAll('input').forEach((input) => input.disabled = !isElectronics || !usesVariants);
            });
            variantsPanel.querySelectorAll('[data-standard-color]').forEach((field) => {
                field.hidden = isElectronics;
                field.querySelectorAll('input').forEach((input) => input.disabled = isElectronics || !usesVariants);
            });
            variantsPanel.querySelectorAll('[data-electronics-color]').forEach((field) => {
                field.hidden = !isElectronics;
                field.querySelectorAll('input').forEach((input) => input.disabled = !isElectronics || !usesVariants);
            });
            variantsPanel.querySelectorAll('[data-add-variant-color]').forEach((button) => {
                button.hidden = !isElectronics;
            });
            variantsPanel.querySelectorAll('input[name$="[size]"]').forEach((input) => {
                input.closest('.form-group').hidden = isElectronics;
                input.disabled = isElectronics || !usesVariants;
            });
            updateElectronicsVariantStructure(isElectronics);

            const isRestaurant = type === 'restaurant';
            const usesSpecializedPricing = isRestaurant || isElectronics;
            restaurantEditor.hidden = !isRestaurant;
            restaurantEditor.style.display = isRestaurant ? '' : 'none';
            restaurantEditor.querySelectorAll('[data-restaurant-input]').forEach((field) => field.disabled = !isRestaurant);
            if (legacyPricing) {
                legacyPricing.hidden = usesSpecializedPricing;
                legacyPricing.style.display = usesSpecializedPricing ? 'none' : '';
                legacyPricing.querySelectorAll('input,select,textarea').forEach((field) => field.disabled = usesSpecializedPricing);
            }
            if (legacyCampaigns) {
                legacyCampaigns.hidden = usesSpecializedPricing;
                legacyCampaigns.style.display = usesSpecializedPricing ? 'none' : '';
                legacyCampaigns.querySelectorAll('input,select,textarea,button').forEach((field) => field.disabled = usesSpecializedPricing);
            }
            const agentField = document.getElementById('agent_id')?.closest('.form-group');
            if (agentField) {
                agentField.hidden = usesSpecializedPricing;
                agentField.style.display = usesSpecializedPricing ? 'none' : '';
                agentField.querySelectorAll('select,input').forEach((field) => field.disabled = usesSpecializedPricing);
            }
            document.querySelectorAll('[data-catalog-fields="restaurant"] input').forEach((field) => {
                const key = field.name.match(/catalog_attributes\[([^\]]+)\]/)?.[1];
                if (['meal_sizes','meal_size_prices','addons','addon_prices','removable_ingredients'].includes(key)) {
                    field.closest('.form-group')?.setAttribute('hidden', '');
                    field.disabled = true;
                }
            });
        }

        function syncRestaurantPricedOptions() {
            ['size', 'addon'].forEach((kind) => {
                const names = [...restaurantEditor.querySelectorAll(`[data-priced-name="${kind}"]`)];
                const prices = [...restaurantEditor.querySelectorAll(`[data-priced-value="${kind}"]`)];
                const outputs = [...restaurantEditor.querySelectorAll(`[data-priced-output="${kind}"]`)];
                outputs.forEach((output, index) => {
                    const name = names[index]?.value.trim() || '';
                    const price = prices[index]?.value;
                    output.value = name && price !== '' ? `${name}:${price}` : '';
                });
            });
        }
        restaurantEditor?.addEventListener('input', syncRestaurantPricedOptions);
        restaurantEditor?.closest('form')?.addEventListener('submit', syncRestaurantPricedOptions);

        function variantTemplate(index) {
            return `<div data-variant-row class="form-grid" style="padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:16px">
                <div class="form-group"><label class="form-label">المقاس/النمرة</label><input name="variants[${index}][size]"></div>
                <div class="form-group" data-electronics-variant data-variant-shared><label class="form-label">سعة التخزين</label><input name="variants[${index}][storage]" placeholder="128GB"></div>
                <div class="form-group" data-electronics-variant data-variant-shared><label class="form-label">الرام</label><input name="variants[${index}][ram]" placeholder="8GB"></div>
                <div class="form-group" data-standard-color><label class="form-label">اللون</label><input name="variants[${index}][color]"></div>
                <div class="form-group" data-electronics-color hidden><label class="form-label">لون الجهاز</label><input type="color" name="variants[${index}][color]" value="#000000" disabled style="height:54px;padding:6px;cursor:pointer"></div>
                <div class="form-group" data-electronics-variant><label class="form-label">اسم اللون للزبون والطلب</label><input name="variants[${index}][color_name]" placeholder="مثال: أسود تيتانيوم"></div>
                <div class="form-group"><label class="form-label">SKU للخيار</label><input name="variants[${index}][sku]" dir="ltr"></div>
                <div class="form-group" data-variant-shared><label class="form-label" data-variant-price-label>سعر خاص (اختياري)</label><input type="number" step="0.01" min="0" name="variants[${index}][price]"></div>
                <div class="form-group"><label class="form-label">الكمية</label><input type="number" min="0" name="variants[${index}][quantity]" value="0"></div>
                <div class="form-group" style="justify-content:flex-end"><input type="hidden" name="variants[${index}][is_active]" value="0"><label class="visibility-check"><input type="checkbox" name="variants[${index}][is_active]" value="1" checked>متوفر للبيع</label><button type="button" class="btn" data-add-variant-color hidden><i class="ti ti-palette"></i> إضافة لون لنفس السعة</button><button type="button" class="btn" data-remove-variant style="color:#ff6070">حذف الخيار</button></div>
            </div>`;
        }

        function colorListFor(row) {
            let list = row.querySelector(':scope > [data-variant-colors-list]');
            if (!list) {
                list = document.createElement('div');
                list.dataset.variantColorsList = '';
                list.style.cssText = 'grid-column:1/-1;display:grid;gap:10px;margin-top:4px;padding-top:12px;border-top:1px dashed rgba(0,229,255,.28)';
                list.innerHTML = '<strong style="color:#00e5ff">ألوان إضافية لنفس السعة</strong>';
                row.appendChild(list);
            }
            return list;
        }

        function markAsExtraColor(row, rootRow) {
            row.dataset.variantColorRow = '';
            row.style.cssText = 'padding:10px;border:1px solid rgba(0,229,255,.2);border-radius:14px;background:rgba(0,229,255,.025)';
            row.querySelector(':scope > [data-variant-colors-list]')?.remove();
            row.querySelectorAll('[data-variant-shared]').forEach((field) => field.hidden = true);
            const size = row.querySelector('input[name$="[size]"]')?.closest('.form-group');
            if (size) size.hidden = true;
            const addButton = row.querySelector('[data-add-variant-color]');
            if (addButton) addButton.hidden = true;
            syncExtraColor(row, rootRow);
        }

        function syncExtraColor(row, rootRow) {
            ['storage', 'ram', 'price'].forEach((key) => {
                const source = rootRow.querySelector(`input[name$="[${key}]"]`);
                const target = row.querySelector(`input[name$="[${key}]"]`);
                if (source && target) target.value = source.value;
            });
        }

        function updateElectronicsVariantStructure(isElectronics) {
            if (!isElectronics) {
                variantsList.querySelectorAll(':scope > [data-variant-row]').forEach((rootRow) => {
                    const list = rootRow.querySelector(':scope > [data-variant-colors-list]');
                    [...(list?.querySelectorAll(':scope > [data-variant-row]') || [])].forEach((row) => {
                        delete row.dataset.variantColorRow;
                        row.removeAttribute('style');
                        rootRow.insertAdjacentElement('afterend', row);
                    });
                    list?.remove();
                });
                return;
            }

            const groups = new Map();
            [...variantsList.querySelectorAll(':scope > [data-variant-row]')].forEach((row) => {
                const storage = row.querySelector('input[name$="[storage]"]')?.value.trim() || '';
                const ram = row.querySelector('input[name$="[ram]"]')?.value.trim() || '';
                const price = row.querySelector('input[name$="[price]"]')?.value.trim() || '';
                const key = `${storage.toLowerCase()}|${ram.toLowerCase()}|${price}`;

                if (!storage || !groups.has(key)) {
                    if (storage) groups.set(key, row);
                    return;
                }

                const rootRow = groups.get(key);
                markAsExtraColor(row, rootRow);
                colorListFor(rootRow).appendChild(row);
            });

            variantsList.querySelectorAll(':scope > [data-variant-row]').forEach((rootRow) => {
                rootRow.querySelectorAll(':scope > [data-variant-colors-list] > [data-variant-row]').forEach((row) => {
                    markAsExtraColor(row, rootRow);
                });
            });
        }

        addVariant?.addEventListener('click', () => {
            variantsList.insertAdjacentHTML('beforeend', variantTemplate(variantIndex++));
            updateCatalogFields();
        });
        variantsList?.addEventListener('click', (event) => {
            const addColorButton = event.target.closest('[data-add-variant-color]');
            if (addColorButton) {
                const sourceRow = addColorButton.closest('[data-variant-row]');
                const newRow = sourceRow.cloneNode(true);
                const newIndex = variantIndex++;

                newRow.querySelector(':scope > [data-variant-colors-list]')?.remove();
                newRow.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/variants\[\d+\]/, `variants[${newIndex}]`);
                });
                newRow.querySelectorAll('input[name$="[color]"]').forEach((field) => {
                    field.value = field.type === 'color' ? '#000000' : '';
                });
                newRow.querySelector('input[name$="[color_name]"]').value = '';
                newRow.querySelector('input[name$="[sku]"]').value = '';
                newRow.querySelector('input[name$="[quantity]"]').value = '0';

                markAsExtraColor(newRow, sourceRow);
                colorListFor(sourceRow).appendChild(newRow);
                newRow.querySelector('input[type="color"]')?.focus();
                return;
            }
            if (!event.target.closest('[data-remove-variant]')) return;
            const row = event.target.closest('[data-variant-row]');
            const list = row?.parentElement?.closest('[data-variant-colors-list]');
            row?.remove();
            if (list && !list.querySelector('[data-variant-row]')) list.remove();
        });
        variantsList?.addEventListener('input', (event) => {
            if (!event.target.closest('[data-variant-shared]')) return;
            const rootRow = event.target.closest('[data-variant-row]');
            rootRow?.querySelectorAll(':scope > [data-variant-colors-list] > [data-variant-row]').forEach((row) => {
                syncExtraColor(row, rootRow);
            });
        });
        variantsList?.closest('form')?.addEventListener('submit', () => {
            variantsList.querySelectorAll(':scope > [data-variant-row]').forEach((rootRow) => {
                rootRow.querySelectorAll(':scope > [data-variant-colors-list] > [data-variant-row]').forEach((row) => {
                    syncExtraColor(row, rootRow);
                });
            });
        });

        shopSelect?.addEventListener('change', updateCatalogFields);
        updateCatalogFields();
        syncRestaurantPricedOptions();
    });
</script>
