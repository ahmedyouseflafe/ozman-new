@php
    $editingProduct = $product ?? null;
@endphp

<section class="form-section" id="simpleProductPricingSection" hidden>
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-currency-shekel"></i></div>
        <div>
            <h2>السعر والتوفر</h2>
            <p>سعر واضح لهذا {{ $itemLabel }} وبيانات المخزون فقط، بدون عبوات أو كراتين.</p>
        </div>
    </div>
    <div class="pricing-sections">
        <div class="pricing-card customer" style="grid-column:1 / -1">
            <div class="pricing-card-head"><i class="ti ti-tag"></i><div><h3>سعر {{ $itemLabel }}</h3><p>السعر الذي يظهر للعميل في صفحة المتجر.</p></div></div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="simple_price">السعر الأساسي</label><input type="number" step="0.01" min="0" id="simple_price" name="price" value="{{ old('price', $editingProduct?->price) }}" required></div>
                <div class="form-group"><label class="form-label" for="simple_discount_price">سعر التخفيض <small style="color:var(--dim)">(اختياري)</small></label><input type="number" step="0.01" min="0" id="simple_discount_price" name="discount_price" value="{{ old('discount_price', $editingProduct?->discount_price) }}"></div>
            </div>
        </div>
        <div class="pricing-card inventory" style="grid-column:1 / -1">
            <div class="pricing-card-head"><i class="ti ti-box"></i><div><h3>التوفر والتعريف</h3><p>أدخل فقط ما تحتاجه لإدارة هذا {{ $itemLabel }}.</p></div></div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="simple_quantity">الكمية</label><input type="number" min="0" id="simple_quantity" name="quantity" value="{{ old('quantity', $editingProduct?->quantity ?? 0) }}"></div>
                <div class="form-group"><label class="form-label" for="simple_sku">رمز المنتج SKU <small style="color:var(--dim)">(اختياري)</small></label><input type="text" id="simple_sku" name="sku" value="{{ old('sku', $editingProduct?->sku) }}" dir="ltr"></div>
                <div class="form-group"><label class="form-label" for="simple_barcode">الباركود <small style="color:var(--dim)">(اختياري)</small></label><input type="text" id="simple_barcode" name="barcode" value="{{ old('barcode', $editingProduct?->barcode) }}" dir="ltr"></div>
            </div>
        </div>
    </div>
</section>
