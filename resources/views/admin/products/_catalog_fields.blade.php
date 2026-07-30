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
            <h2 id="catalogSpecificTitle">تفاصيل المنتج حسب نوع المتجر</h2>
            <p id="catalogSpecificDescription">اختر المتجر لعرض الحقول المناسبة لنشاطه.</p>
        </div>
    </div>

    @foreach($catalogTypes as $typeKey => $type)
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
        $variantRows = count($variantRows) ? $variantRows : [['size' => '', 'color' => '', 'sku' => '', 'price' => '', 'quantity' => 0, 'is_active' => true]];
    @endphp
    <div id="productVariantsPanel" hidden style="margin-top:20px;padding-top:18px;border-top:1px solid rgba(255,255,255,.1)">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px">
            <div>
                <h3 style="margin:0;color:#00e5ff">مخزون المقاسات والألوان</h3>
                <small style="color:rgba(255,255,255,.55)">كل صف يمثل خيارًا مستقلاً، والكمية الإجمالية تُحسب تلقائيًا.</small>
            </div>
            <button type="button" class="btn" id="addProductVariant"><i class="ti ti-plus"></i> إضافة خيار</button>
        </div>
        <div id="productVariantsList" style="display:grid;gap:10px">
            @foreach($variantRows as $index => $variant)
                <div data-variant-row class="form-grid" style="padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:16px">
                    <div class="form-group"><label class="form-label">المقاس/النمرة</label><input name="variants[{{ $index }}][size]" value="{{ data_get($variant, 'size') }}"></div>
                    <div class="form-group"><label class="form-label">اللون</label><input name="variants[{{ $index }}][color]" value="{{ data_get($variant, 'color') }}"></div>
                    <div class="form-group"><label class="form-label">SKU للخيار</label><input name="variants[{{ $index }}][sku]" value="{{ data_get($variant, 'sku') }}" dir="ltr"></div>
                    <div class="form-group"><label class="form-label">سعر خاص (اختياري)</label><input type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ data_get($variant, 'price') }}"></div>
                    <div class="form-group"><label class="form-label">الكمية</label><input type="number" min="0" name="variants[{{ $index }}][quantity]" value="{{ data_get($variant, 'quantity', 0) }}"></div>
                    <div class="form-group" style="justify-content:flex-end"><input type="hidden" name="variants[{{ $index }}][is_active]" value="0"><label class="visibility-check"><input type="checkbox" name="variants[{{ $index }}][is_active]" value="1" @checked((bool) data_get($variant, 'is_active', true))>متوفر للبيع</label><button type="button" class="btn" data-remove-variant style="color:#ff6070">حذف الخيار</button></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
    (() => {
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
        let variantIndex = variantsList?.querySelectorAll('[data-variant-row]').length || 0;

        function updateCatalogFields() {
            const type = shopSelect?.selectedOptions?.[0]?.dataset?.catalogType || 'general';
            groups.forEach((group) => {
                const active = group.dataset.catalogFields === type;
                group.hidden = !active;
                group.style.display = active ? '' : 'none';
                group.setAttribute('aria-hidden', active ? 'false' : 'true');
                group.querySelectorAll('input, select, textarea').forEach((field) => field.disabled = !active);
            });
            if (definitions[type]) {
                title.textContent = `تفاصيل ${definitions[type].label}`;
                description.textContent = definitions[type].description;
            }
            const usesVariants = ['clothing', 'shoes'].includes(type);
            variantsPanel.hidden = !usesVariants;
            variantsPanel.style.display = usesVariants ? '' : 'none';
            variantsPanel.setAttribute('aria-hidden', usesVariants ? 'false' : 'true');
            variantsPanel.querySelectorAll('input, button').forEach((field) => field.disabled = !usesVariants);
        }

        function variantTemplate(index) {
            return `<div data-variant-row class="form-grid" style="padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:16px">
                <div class="form-group"><label class="form-label">المقاس/النمرة</label><input name="variants[${index}][size]"></div>
                <div class="form-group"><label class="form-label">اللون</label><input name="variants[${index}][color]"></div>
                <div class="form-group"><label class="form-label">SKU للخيار</label><input name="variants[${index}][sku]" dir="ltr"></div>
                <div class="form-group"><label class="form-label">سعر خاص (اختياري)</label><input type="number" step="0.01" min="0" name="variants[${index}][price]"></div>
                <div class="form-group"><label class="form-label">الكمية</label><input type="number" min="0" name="variants[${index}][quantity]" value="0"></div>
                <div class="form-group" style="justify-content:flex-end"><input type="hidden" name="variants[${index}][is_active]" value="0"><label class="visibility-check"><input type="checkbox" name="variants[${index}][is_active]" value="1" checked>متوفر للبيع</label><button type="button" class="btn" data-remove-variant style="color:#ff6070">حذف الخيار</button></div>
            </div>`;
        }

        addVariant?.addEventListener('click', () => {
            variantsList.insertAdjacentHTML('beforeend', variantTemplate(variantIndex++));
        });
        variantsList?.addEventListener('click', (event) => {
            if (!event.target.closest('[data-remove-variant]')) return;
            event.target.closest('[data-variant-row]')?.remove();
        });

        shopSelect?.addEventListener('change', updateCatalogFields);
        updateCatalogFields();
    })();
</script>
