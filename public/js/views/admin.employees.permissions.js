const search = document.getElementById("permissionSearch");
const groups = [...document.querySelectorAll("[data-permission-group]")];
const checks = [...document.querySelectorAll('input[name="permissions[]"]')];
const selectedCount = document.getElementById("selectedCount");
const saveSelectedCount = document.getElementById("saveSelectedCount");
const emptySearch = document.getElementById("emptySearch");
const updateCounts = () => {
    const total = checks.filter((check) => check.checked).length;
    if (selectedCount) selectedCount.textContent = total;
    if (saveSelectedCount) saveSelectedCount.textContent = total;
    groups.forEach((group) => {
        const count = [...group.querySelectorAll('input[name="permissions[]"]')].filter(
            (check) => check.checked,
        ).length;
        const output = group.querySelector("[data-group-selected]");
        if (output) output.textContent = count;
    });
};
const setChecks = (items, checked) => {
    items
        .filter((check) => check.closest("[data-permission-item]")?.style.display !== "none")
        .forEach((check) => (check.checked = checked));
    updateCounts();
};
document.getElementById("selectAll")?.addEventListener("click", () => setChecks(checks, true));
document.getElementById("clearAll")?.addEventListener("click", () => setChecks(checks, false));
groups.forEach((group) => {
    const groupChecks = [...group.querySelectorAll('input[name="permissions[]"]')];
    group.querySelector("[data-group-select]")?.addEventListener("click", () => setChecks(groupChecks, true));
    group.querySelector("[data-group-clear]")?.addEventListener("click", () => setChecks(groupChecks, false));
});
checks.forEach((check) => check.addEventListener("change", updateCounts));
document.querySelectorAll("[data-permission-preset]").forEach((button) =>
    button.addEventListener("click", () => {
        const keys =
            button.dataset.permissionPreset === "orders"
                ? ["front_orders.own.view"]
                : button.dataset.permissionPreset === "catalog"
                  ? ["front_orders.own.view", "products.view", "products.preview", "categories.view"]
                  : [];
        checks.forEach((check) => (check.checked = keys.includes(check.value)));
        updateCounts();
    }),
);
search?.addEventListener("input", () => {
    const query = search.value.trim().toLowerCase();
    document.querySelectorAll("[data-advanced-permissions]").forEach((panel) => (panel.open = query.length > 0));
    let visibleGroups = 0;
    groups.forEach((group) => {
        const items = [...group.querySelectorAll("[data-permission-item]")];
        let visible = false;
        items.forEach((item) => {
            const match = item.textContent.toLowerCase().includes(query);
            item.style.display = match ? "" : "none";
            visible = visible || match;
        });
        group.style.display = visible ? "" : "none";
        if (visible) visibleGroups += 1;
    });
    if (emptySearch) emptySearch.style.display = visibleGroups ? "none" : "block";
});
updateCounts();
