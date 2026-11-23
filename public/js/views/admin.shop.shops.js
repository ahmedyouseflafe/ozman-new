const shopSearch = document.getElementById("shopSearch");
const statusFilter = document.getElementById("statusFilter");
const shopRows = document.querySelectorAll("#shopsTable tbody tr");

function filterShops() {
    const query = (shopSearch?.value || "").trim().toLowerCase();
    const status = statusFilter?.value || "";

    shopRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = !status || row.dataset.status === status;
        row.style.display = matchesQuery && matchesStatus ? "" : "none";
    });
}

shopSearch?.addEventListener("input", filterShops);
statusFilter?.addEventListener("change", filterShops);

const assignModal = document.getElementById("assignDistributorModal");
const assignForm = document.getElementById("assignDistributorForm");
const assignSelect = document.getElementById("assignDistributorSelect");
const assignShopName = document.getElementById("assignDistributorShopName");
document.querySelectorAll("[data-assign-distributor]").forEach((button) => {
    button.addEventListener("click", () => {
        assignForm.action = button.dataset.action;
        assignSelect.value = button.dataset.distributorId || "";
        assignShopName.textContent = button.dataset.shopName || "";
        assignModal.classList.add("open");
    });
});
document.querySelector("[data-close-assign]")?.addEventListener("click", () => assignModal?.classList.remove("open"));
assignModal?.addEventListener("click", (event) => {
    if (event.target === assignModal) assignModal.classList.remove("open");
});
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") assignModal?.classList.remove("open");
});
