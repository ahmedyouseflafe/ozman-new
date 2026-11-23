(() => {
    const form = document.querySelector("form");
    const logoInput = document.getElementById("shopLogoInput");
    const logoPreview = document.getElementById("shopLogoPreview");
    const latitudeInput = document.getElementById("shopLatitude");
    const longitudeInput = document.getElementById("shopLongitude");
    const status = document.getElementById("shopLocationStatus");
    const locateButton = document.getElementById("useCurrentLocationBtn");
    const defaultPosition = [31.9, 35.2];
    const savedLatitude = Number.parseFloat(latitudeInput.value);
    const savedLongitude = Number.parseFloat(longitudeInput.value);
    const hasSavedPosition = Number.isFinite(savedLatitude) && Number.isFinite(savedLongitude);
    const initialPosition = hasSavedPosition ? [savedLatitude, savedLongitude] : defaultPosition;
    const map = L.map("shopLocationMap").setView(initialPosition, hasSavedPosition ? 16 : 9);
    let marker = null;

    logoInput.addEventListener("change", () => {
        const file = logoInput.files?.[0];
        if (!file) {
            logoPreview.removeAttribute("src");
            logoPreview.style.display = "none";
            return;
        }

        logoPreview.src = URL.createObjectURL(file);
        logoPreview.style.display = "block";
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
    }).addTo(map);

    function selectLocation(latitude, longitude, center = true) {
        latitudeInput.value = Number(latitude).toFixed(7);
        longitudeInput.value = Number(longitude).toFixed(7);

        if (!marker) {
            marker = L.marker([latitude, longitude], {
                draggable: true,
            }).addTo(map);
            marker.on("dragend", () => {
                const position = marker.getLatLng();
                selectLocation(position.lat, position.lng, false);
            });
        } else {
            marker.setLatLng([latitude, longitude]);
        }

        if (center) map.setView([latitude, longitude], 17);
        status.textContent = `تم تحديد موقع المحل: ${latitudeInput.value}, ${longitudeInput.value}`;
        status.classList.add("selected");
    }

    if (hasSavedPosition) selectLocation(savedLatitude, savedLongitude);
    map.on("click", (event) => selectLocation(event.latlng.lat, event.latlng.lng));

    locateButton.addEventListener("click", () => {
        if (!navigator.geolocation) {
            status.textContent = "الجهاز لا يدعم تحديد الموقع. اختر موقع المحل بالضغط على الخريطة.";
            return;
        }

        locateButton.disabled = true;
        status.textContent = "جاري تحديد موقعك...";
        navigator.geolocation.getCurrentPosition(
            (position) => {
                selectLocation(position.coords.latitude, position.coords.longitude);
                locateButton.disabled = false;
            },
            () => {
                status.textContent = "تعذر الوصول للموقع. اسمح بصلاحية الموقع أو اضغط على الخريطة.";
                locateButton.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 30000,
            },
        );
    });

    form.addEventListener("submit", (event) => {
        if (!latitudeInput.value || !longitudeInput.value) {
            event.preventDefault();
            status.textContent = "يجب تحديد موقع المحل على الخريطة قبل إنشاء الحساب.";
            status.classList.remove("selected");
            document.getElementById("shopLocationMap").scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        }
    });
})();
