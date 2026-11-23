@php($isEdit = isset($agent))

<section class="form-section">
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-user-star" aria-hidden="true"></i></div>
        <div>
            <h2>بيانات الوكيل</h2>
            <p>اختر المتجر وأدخل بيانات التواصل والموقع.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="shop_id">المتجر</label>
            <select id="shop_id" name="shop_id" required>
                <option value="">اختر المتجر</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" @selected(old('shop_id', $agent->shop_id ?? $selectedShopId ?? '') == $shop->id)>{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="name">اسم الوكيل</label>
            <input type="text" id="name" name="name" value="{{ old('name', $agent->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="user_id">حساب دخول الوكيل</label>
            <select id="user_id" name="user_id">
                <option value="">بدون ربط مباشر</option>
                @foreach($agentUsers ?? [] as $agentUser)
                    <option value="{{ $agentUser->id }}" @selected(old('user_id', $agent->user_id ?? '') == $agentUser->id)>
                        {{ $agentUser->name }} - {{ $agentUser->email }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="login_password">كلمة مرور دخول الوكيل</label>
            <input type="password" id="login_password" name="login_password" autocomplete="new-password" placeholder="اتركها فارغة إذا لا تريد إنشاء/تغيير الحساب">
        </div>

        <div class="form-group">
            <label class="form-label" for="phone">الهاتف</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $agent->phone ?? '') }}" dir="ltr">
        </div>

        <div class="form-group">
            <label class="form-label" for="whatsapp">واتساب</label>
            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $agent->whatsapp ?? '') }}" dir="ltr">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" value="{{ old('email', $agent->email ?? '') }}" dir="ltr">
        </div>

        <div class="form-group">
            <label class="form-label" for="address">العنوان</label>
            <input type="text" id="address" name="address" value="{{ old('address', $agent->address ?? '') }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="latitude">Latitude</label>
            <input type="hidden" step="0.000001" min="-90" max="90" id="latitude" name="latitude" value="{{ old('latitude', $agent->latitude ?? '') }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="longitude">Longitude</label>
            <input type="hidden" step="0.000001" min="-180" max="180" id="longitude" name="longitude" value="{{ old('longitude', $agent->longitude ?? '') }}">
        </div>

        <div class="form-group full">
            <div class="location-card">
                <div class="card-copy">
                    <span class="card-icon"><i class="ti ti-map-search" aria-hidden="true"></i></span>
                    <span>
                        <span class="card-title">موقع الوكيل على الخريطة</span>
                        <span class="card-sub" id="locationSummary">
                            @if(old('latitude', $agent->latitude ?? null) && old('longitude', $agent->longitude ?? null))
                                تم اختيار الموقع: <span class="map-status">{{ old('latitude', $agent->latitude ?? '') }}, {{ old('longitude', $agent->longitude ?? '') }}</span>
                            @else
                                لم يتم اختيار موقع بعد.
                            @endif
                        </span>
                    </span>
                </div>
                <button type="button" id="openMapModal" class="btn">
                    <i class="ti ti-map-pin-plus" aria-hidden="true"></i>
                    اختيار الموقع
                </button>
            </div>
        </div>

        <div class="form-group full">
            <label class="upload-box">
                <input type="file" name="image" accept="image/*">
                <span class="card-icon"><i class="ti ti-photo-up" aria-hidden="true"></i></span>
                <span>
                    <span class="card-title">صورة الوكيل</span>
                    <span class="card-sub">{{ $isEdit ? 'اتركها فارغة للاحتفاظ بالصورة الحالية' : 'اختياري، PNG أو JPG' }}</span>
                </span>
            </label>
        </div>

        <div class="form-group full">
            <div class="switch-card">
                <div class="card-copy">
                    <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                    <span>
                        <span class="card-title">تفعيل الوكيل</span>
                        <span class="card-sub">الوكيل النشط يظهر ضمن شبكة الوكلاء.</span>
                    </span>
                </div>
                <label class="switch" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $agent->is_active ?? true))>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
</section>

<div class="modal-backdrop" id="locationModal" aria-hidden="true">
    <div class="location-modal" role="dialog" aria-modal="true" aria-labelledby="locationModalTitle">
        <div class="modal-head">
            <div class="modal-title">
                <span class="card-icon"><i class="ti ti-map-2" aria-hidden="true"></i></span>
                <span>
                    <strong id="locationModalTitle">اختيار موقع الوكيل</strong>
                    <span>اضغط على المكان المطلوب داخل الخريطة أو أدخل الإحداثيات ثم احفظ الموقع.</span>
                </span>
            </div>
            <button type="button" class="modal-close" id="closeMapModal" aria-label="إغلاق">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="coordinate-search">
                <div>
                    <label class="form-label" for="modalLatitude"><i class="ti ti-current-location" aria-hidden="true"></i>Latitude</label>
                    <input type="number" id="modalLatitude" placeholder="31.501000" step="0.000001" min="-90" max="90">
                </div>
                <div>
                    <label class="form-label" for="modalLongitude"><i class="ti ti-world-longitude" aria-hidden="true"></i>Longitude</label>
                    <input type="number" id="modalLongitude" placeholder="34.466000" step="0.000001" min="-180" max="180">
                </div>
                <button type="button" class="btn" id="searchCoordinates">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    بحث
                </button>
            </div>
            <div class="coordinate-error" id="coordinateError">أدخل Latitude بين -90 و 90 و Longitude بين -180 و 180.</div>
            <div id="agentLocationMap"></div>
        </div>

        <div class="modal-footer">
            <div class="picked-location">
                <i class="ti ti-current-location" aria-hidden="true"></i>
                <span id="pickedLocationText">لم يتم تحديد موقع بعد</span>
            </div>
            <button type="button" class="btn btn-primary" id="saveLocation" disabled>
                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                حفظ الموقع
            </button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@viewScript('admin.agent._form')
