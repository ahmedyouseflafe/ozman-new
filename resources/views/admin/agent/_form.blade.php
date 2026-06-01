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
<script>
    const openMapModal = document.getElementById('openMapModal');
    const closeMapModal = document.getElementById('closeMapModal');
    const locationModal = document.getElementById('locationModal');
    const saveLocation = document.getElementById('saveLocation');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const pickedLocationText = document.getElementById('pickedLocationText');
    const locationSummary = document.getElementById('locationSummary');
    const modalLatitude = document.getElementById('modalLatitude');
    const modalLongitude = document.getElementById('modalLongitude');
    const searchCoordinates = document.getElementById('searchCoordinates');
    const coordinateError = document.getElementById('coordinateError');

    let agentMap;
    let agentMarker;
    let pickedLocation = null;

    function formatLocation(lat, lng) {
        return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    }

    function isValidCoordinate(lat, lng) {
        return Number.isFinite(lat) &&
            Number.isFinite(lng) &&
            lat >= -90 &&
            lat <= 90 &&
            lng >= -180 &&
            lng <= 180;
    }

    function setPickedLocation(lat, lng) {
        pickedLocation = { lat, lng };
        latitudeInput.value = lat.toFixed(6);
        longitudeInput.value = lng.toFixed(6);
        pickedLocationText.textContent = formatLocation(lat, lng);
        modalLatitude.value = lat.toFixed(6);
        modalLongitude.value = lng.toFixed(6);
        coordinateError.classList.remove('show');
        saveLocation.disabled = false;

        if (!agentMarker) {
            agentMarker = L.marker([lat, lng], { draggable: true }).addTo(agentMap);
            agentMarker.on('dragend', function(event) {
                const position = event.target.getLatLng();
                setPickedLocation(position.lat, position.lng);
            });
        } else {
            agentMarker.setLatLng([lat, lng]);
        }
    }

    function searchByCoordinates() {
        const lat = parseFloat(modalLatitude.value);
        const lng = parseFloat(modalLongitude.value);

        if (!isValidCoordinate(lat, lng)) {
            coordinateError.classList.add('show');
            return;
        }

        setPickedLocation(lat, lng);
        agentMap.setView([lat, lng], 16);
    }

    function openLocationModal() {
        locationModal.classList.add('open');
        locationModal.setAttribute('aria-hidden', 'false');

        setTimeout(() => {
            if (!agentMap) {
                const initialLat = parseFloat(latitudeInput.value) || 31.501;
                const initialLng = parseFloat(longitudeInput.value) || 34.466;

                agentMap = L.map('agentLocationMap').setView([initialLat, initialLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(agentMap);

                agentMap.on('click', function(event) {
                    setPickedLocation(event.latlng.lat, event.latlng.lng);
                });

                if (latitudeInput.value && longitudeInput.value) {
                    setPickedLocation(initialLat, initialLng);
                } else {
                    modalLatitude.value = initialLat.toFixed(6);
                    modalLongitude.value = initialLng.toFixed(6);
                }
            }

            agentMap.invalidateSize();
        }, 120);
    }

    function closeLocationModal() {
        locationModal.classList.remove('open');
        locationModal.setAttribute('aria-hidden', 'true');
    }

    openMapModal.addEventListener('click', openLocationModal);
    closeMapModal.addEventListener('click', closeLocationModal);
    searchCoordinates.addEventListener('click', searchByCoordinates);

    [modalLatitude, modalLongitude].forEach((input) => {
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchByCoordinates();
            }
        });
    });

    locationModal.addEventListener('click', function(event) {
        if (event.target === locationModal) {
            closeLocationModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && locationModal.classList.contains('open')) {
            closeLocationModal();
        }
    });

    saveLocation.addEventListener('click', function() {
        if (!pickedLocation) {
            return;
        }

        locationSummary.innerHTML = `تم اختيار الموقع: <span class="map-status">${formatLocation(pickedLocation.lat, pickedLocation.lng)}</span>`;
        closeLocationModal();
    });
</script>
