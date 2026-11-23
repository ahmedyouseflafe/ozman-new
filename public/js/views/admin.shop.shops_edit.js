const openMapModal = document.getElementById("openMapModal");
const closeMapModal = document.getElementById("closeMapModal");
const locationModal = document.getElementById("locationModal");
const saveLocation = document.getElementById("saveLocation");
const latitudeInput = document.getElementById("latitude");
const longitudeInput = document.getElementById("longitude");
const pickedLocationText = document.getElementById("pickedLocationText");
const locationSummary = document.getElementById("locationSummary");
const modalLatitude = document.getElementById("modalLatitude");
const modalLongitude = document.getElementById("modalLongitude");
const searchCoordinates = document.getElementById("searchCoordinates");
const coordinateError = document.getElementById("coordinateError");

let shopMap;
let shopMarker;
let pickedLocation = null;

function formatLocation(lat, lng) {
    return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
}

function isValidCoordinate(lat, lng) {
    return Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
}

function setPickedLocation(lat, lng) {
    pickedLocation = { lat, lng };
    pickedLocationText.textContent = formatLocation(lat, lng);
    modalLatitude.value = lat.toFixed(6);
    modalLongitude.value = lng.toFixed(6);
    coordinateError.classList.remove("show");
    saveLocation.disabled = false;

    if (!shopMarker) {
        shopMarker = L.marker([lat, lng], { draggable: true }).addTo(shopMap);
        shopMarker.on("dragend", function (event) {
            const position = event.target.getLatLng();
            setPickedLocation(position.lat, position.lng);
        });
    } else {
        shopMarker.setLatLng([lat, lng]);
    }
}

function searchByCoordinates() {
    const lat = parseFloat(modalLatitude.value);
    const lng = parseFloat(modalLongitude.value);

    if (!isValidCoordinate(lat, lng)) {
        coordinateError.classList.add("show");
        return;
    }

    setPickedLocation(lat, lng);
    shopMap.setView([lat, lng], 16);
}

function openLocationModal() {
    locationModal.classList.add("open");
    locationModal.setAttribute("aria-hidden", "false");

    setTimeout(() => {
        if (!shopMap) {
            const initialLat = parseFloat(latitudeInput.value) || 31.501;
            const initialLng = parseFloat(longitudeInput.value) || 34.466;

            shopMap = L.map("shopLocationMap").setView([initialLat, initialLng], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution: "&copy; OpenStreetMap",
            }).addTo(shopMap);

            shopMap.on("click", function (event) {
                setPickedLocation(event.latlng.lat, event.latlng.lng);
            });

            modalLatitude.value = initialLat.toFixed(6);
            modalLongitude.value = initialLng.toFixed(6);

            if (latitudeInput.value && longitudeInput.value) {
                setPickedLocation(initialLat, initialLng);
            }
        }

        shopMap.invalidateSize();
    }, 120);
}

function closeLocationModal() {
    locationModal.classList.remove("open");
    locationModal.setAttribute("aria-hidden", "true");
}

openMapModal.addEventListener("click", openLocationModal);
closeMapModal.addEventListener("click", closeLocationModal);
searchCoordinates.addEventListener("click", searchByCoordinates);

[modalLatitude, modalLongitude].forEach((input) => {
    input.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            searchByCoordinates();
        }
    });
});

locationModal.addEventListener("click", function (event) {
    if (event.target === locationModal) {
        closeLocationModal();
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && locationModal.classList.contains("open")) {
        closeLocationModal();
    }
});

saveLocation.addEventListener("click", function () {
    if (!pickedLocation) {
        return;
    }

    latitudeInput.value = pickedLocation.lat.toFixed(6);
    longitudeInput.value = pickedLocation.lng.toFixed(6);
    locationSummary.innerHTML = `تم اختيار الموقع: <span class="map-status">${formatLocation(pickedLocation.lat, pickedLocation.lng)}</span>`;
    closeLocationModal();
});
