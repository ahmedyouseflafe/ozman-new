const adTypeFilter = document.getElementById("adTypeFilter");
const adTypeButton = document.getElementById("adTypeButton");
const adTypeLabel = document.getElementById("adTypeLabel");
const adTypeValue = document.getElementById("adTypeValue");
const adTypeOptions = document.querySelectorAll(".type-filter-option");

function filterAds() {
    const searchInput = document.getElementById("adSearch");
    const q = searchInput.value.toLowerCase();
    const type = adTypeValue.value;

    document.querySelectorAll("#adsTable tbody tr").forEach((row) => {
        if (!row.dataset.type) {
            row.style.display = q || type ? "none" : "";
            return;
        }

        const matchesSearch = row.textContent.toLowerCase().includes(q);
        const matchesType = !type || row.dataset.type === type;
        row.style.display = matchesSearch && matchesType ? "" : "none";
    });
}

adTypeButton.addEventListener("click", function () {
    const isOpen = adTypeFilter.classList.toggle("open");
    adTypeButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
});

adTypeOptions.forEach((option) => {
    option.addEventListener("click", function () {
        adTypeOptions.forEach((item) => item.classList.remove("active"));
        option.classList.add("active");
        adTypeValue.value = option.dataset.value;
        adTypeLabel.textContent = option.textContent;
        adTypeFilter.classList.remove("open");
        adTypeButton.setAttribute("aria-expanded", "false");
        filterAds();
    });
});

document.addEventListener("click", function (event) {
    if (!adTypeFilter.contains(event.target)) {
        adTypeFilter.classList.remove("open");
        adTypeButton.setAttribute("aria-expanded", "false");
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        adTypeFilter.classList.remove("open");
        adTypeButton.setAttribute("aria-expanded", "false");
    }
});

document.getElementById("adSearch").addEventListener("input", filterAds);
