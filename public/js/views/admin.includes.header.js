// Quick jump: type part of a menu name and press Enter to open that page.
(() => {
    const input = document.currentScript.parentElement.querySelector("[data-admin-page-search]");
    const list = document.getElementById("adminPageSearchList");
    const pages = () =>
        [...document.querySelectorAll(".admin-sidebar-nav a.admin-sidebar-item")].map((link) => ({
            label: link.textContent.trim().replace(/\s+/g, " "),
            href: link.href,
        }));
    const fill = () => {
        list.innerHTML = "";
        pages().forEach((p) => list.append(new Option(p.label)));
    };
    const go = () => {
        const q = input.value.trim();
        if (!q) return;
        const hit = pages().find((p) => p.label === q) || pages().find((p) => p.label.includes(q));
        if (hit) window.location.href = hit.href;
    };
    input.addEventListener("focus", fill, { once: true });
    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            go();
        }
    });
    input.addEventListener("change", () => {
        if (pages().some((p) => p.label === input.value.trim())) go();
    });
})();
