document.addEventListener("DOMContentLoaded", () => {
    const wrapper = document.querySelector("[data-admin-notifications]");
    if (!wrapper) return;

    const toggle = wrapper.querySelector("[data-admin-notifications-toggle]");
    const filters = wrapper.querySelectorAll("[data-notification-filter]");
    const items = wrapper.querySelectorAll("[data-notification-type]");

    toggle?.addEventListener("click", (event) => {
        event.stopPropagation();
        wrapper.classList.toggle("open");
    });

    document.addEventListener("click", (event) => {
        if (!wrapper.contains(event.target)) {
            wrapper.classList.remove("open");
        }
    });

    filters.forEach((filter) => {
        filter.addEventListener("click", () => {
            const type = filter.dataset.notificationFilter;

            filters.forEach((item) => item.classList.remove("active"));
            filter.classList.add("active");

            items.forEach((item) => {
                item.style.display = type === "all" || item.dataset.notificationType === type ? "grid" : "none";
            });
        });
    });
});
