(() => {
    const latInput = document.querySelector('[name="latitude"]'),
        lngInput = document.querySelector('[name="longitude"]');
    if (!latInput || !lngInput || !window.L) return;
    const latWrap = latInput.closest(".field-wrap"),
        lngWrap = lngInput.closest(".field-wrap"),
        grid = latWrap?.parentElement;
    if (!grid) return;
    latWrap.style.display = "none";
    lngWrap.style.display = "none";
    const picker = document.createElement("div");
    picker.className = "map-picker";
    picker.innerHTML = `<div class="map-tools"><input class="field" id="map-address-search" placeholder="ابحث عن المدينة، الشارع أو اسم المكان"><button class="btn btn-primary" type="button" id="map-search-button"><i class="ti ti-search"></i> بحث</button><button class="btn" type="button" id="map-current-location"><i class="ti ti-current-location"></i> موقعي الحالي</button></div><div id="property-location-map"></div><div class="map-help"><span><i class="ti ti-map-pin"></i> اضغط على الخريطة أو حرّك العلامة لتحديد موقع العقار بدقة.</span><span class="map-coordinates" id="map-coordinates"></span></div><div class="location-error" id="map-location-error"></div>`;
    grid.insertBefore(picker, latWrap);
    const initialLat = parseFloat(latInput.value),
        initialLng = parseFloat(lngInput.value),
        hasInitial = Number.isFinite(initialLat) && Number.isFinite(initialLng);
    const map = L.map("property-location-map").setView(
        hasInitial ? [initialLat, initialLng] : [31.9, 35.2],
        hasInitial ? 16 : 8,
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "© OpenStreetMap" }).addTo(map);
    let marker = null;
    const coordinates = document.getElementById("map-coordinates");
    function setLocation(lat, lng, zoom = true) {
        lat = Number(lat.toFixed(7));
        lng = Number(lng.toFixed(7));
        latInput.value = lat;
        lngInput.value = lng;
        coordinates.textContent = `${lat}, ${lng}`;
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on("dragend", (event) => {
                const point = event.target.getLatLng();
                setLocation(point.lat, point.lng, false);
            });
        } else marker.setLatLng([lat, lng]);
        if (zoom) map.setView([lat, lng], 16);
    }
    if (hasInitial) setLocation(initialLat, initialLng, false);
    map.on("click", (event) => setLocation(event.latlng.lat, event.latlng.lng));
    const searchInput = document.getElementById("map-address-search"),
        errorBox = document.getElementById("map-location-error");
    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.add("show");
    }
    async function searchLocation() {
        const typed = searchInput.value.trim();
        const city = document.querySelector('[name="city"]')?.value || "";
        const neighborhood = document.querySelector('[name="neighborhood"]')?.value || "";
        const address = document.querySelector('[name="address"]')?.value || "";
        const query = typed || [address, neighborhood, city].filter(Boolean).join(", ");
        if (!query) {
            showError("اكتب اسم مدينة أو عنوانًا للبحث.");
            return;
        }
        errorBox.classList.remove("show");
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&accept-language=ar&q=${encodeURIComponent(query)}`,
                { headers: { Accept: "application/json" } },
            );
            const results = await response.json();
            if (!results.length) {
                showError("لم نعثر على الموقع. جرّب كتابة العنوان بطريقة أخرى أو حدده يدويًا على الخريطة.");
                return;
            }
            setLocation(parseFloat(results[0].lat), parseFloat(results[0].lon));
        } catch (error) {
            showError("تعذر البحث الآن. ما زلت تستطيع تحديد الموقع بالضغط على الخريطة.");
        }
    }
    document.getElementById("map-search-button").addEventListener("click", searchLocation);
    searchInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            searchLocation();
        }
    });
    document.getElementById("map-current-location").addEventListener("click", () => {
        if (!navigator.geolocation) {
            showError("المتصفح لا يدعم تحديد الموقع.");
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => setLocation(position.coords.latitude, position.coords.longitude),
            () => showError("تعذر الوصول إلى موقعك. اسمح للموقع باستخدام خدمة الموقع ثم حاول مجددًا."),
            { enableHighAccuracy: true, timeout: 10000 },
        );
    });
    setTimeout(() => map.invalidateSize(), 200);
})();
