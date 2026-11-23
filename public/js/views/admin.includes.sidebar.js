document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector(".sidebar.admin-neon-sidebar");
    const overlay = document.querySelector(".admin-mobile-menu-overlay");
    const openButton = document.querySelector("[data-admin-menu-open]");
    const closeButtons = document.querySelectorAll("[data-admin-menu-close]");

    if (!sidebar || !overlay || !openButton) return;
    const setOpen = (isOpen) => {
        sidebar.classList.toggle("mobile-open", isOpen);
        overlay.classList.toggle("visible", isOpen);
        openButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
        document.body.style.overflow = isOpen ? "hidden" : "";
    };

    openButton.addEventListener("click", () => setOpen(true));
    closeButtons.forEach((button) => button.addEventListener("click", () => setOpen(false)));
    sidebar.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => setOpen(false)));
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") setOpen(false);
    });
    window.addEventListener("resize", () => {
        if (window.innerWidth > 900) setOpen(false);
    });
});
