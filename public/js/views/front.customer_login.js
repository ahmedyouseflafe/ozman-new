const storageKey = "ozman_customer_profile";
const visitorDoneKey = "ozman_visitor_registration_done_v2";
const visitorTypeKey = "ozman_visitor_type";
const form = document.getElementById("standaloneCustomerForm");
const mapFrame = document.getElementById("customerMapFrame");
const statusText = document.getElementById("customerLocationStatus");
const fields = {
    name: document.getElementById("customerName"),
    phone: document.getElementById("customerPhone"),
    whatsapp: document.getElementById("customerWhatsapp"),
    address: document.getElementById("customerAddress"),
    latitude: document.getElementById("customerLatitude"),
    longitude: document.getElementById("customerLongitude"),
    mapLink: document.getElementById("customerMapLink"),
};

function saveStandaloneCustomerProfile(profile) {
    localStorage.setItem(storageKey, JSON.stringify(profile || {}));
    if (profile?.name && (profile?.phone || profile?.whatsapp)) {
        localStorage.setItem(visitorDoneKey, "1");
        localStorage.setItem(visitorTypeKey, "customer");
    }
}

try {
    const profile = JSON.parse(localStorage.getItem(storageKey) || "{}");
    Object.keys(fields).forEach((key) => (fields[key].value = profile[key] || ""));
    if (profile.latitude && profile.longitude) {
        if (!fields.mapLink.value)
            fields.mapLink.value = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}`;
        mapFrame.src = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}&z=16&output=embed`;
        statusText.textContent = "تم تحميل الموقع المحفوظ مسبقا.";
    }
} catch (error) {}

document.getElementById("detectCustomerLocationBtn").addEventListener("click", () => {
    if (!navigator.geolocation) {
        statusText.textContent = "المتصفح لا يدعم تحديد الموقع تلقائيا.";
        return;
    }

    statusText.textContent = "جاري تحديد موقعك...";
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const latitude = position.coords.latitude.toFixed(7);
            const longitude = position.coords.longitude.toFixed(7);
            fields.latitude.value = latitude;
            fields.longitude.value = longitude;
            fields.mapLink.value = `https://www.google.com/maps?q=${latitude},${longitude}`;
            mapFrame.src = `https://www.google.com/maps?q=${latitude},${longitude}&z=16&output=embed`;
            statusText.textContent = "تم تحديد موقعك على الخريطة.";
            saveStandaloneCustomerProfile(
                Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field.value.trim()])),
            );
        },
        () => {
            statusText.textContent = "لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.";
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 },
    );
});

form.addEventListener("submit", (event) => {
    event.preventDefault();
    const profile = Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field.value.trim()]));
    saveStandaloneCustomerProfile(profile);
    statusText.textContent = "تم حفظ بياناتك. ارجع للموقع وكمل طلبك.";
});
