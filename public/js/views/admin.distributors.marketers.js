(() => {
    const form = document.querySelector(".create-form[data-action-template]");
    const select = document.getElementById("marketerDistributorSelect");

    if (!form || !select) return;

    const syncAction = () => {
        form.action = form.dataset.actionTemplate.replace("__DISTRIBUTOR__", encodeURIComponent(select.value));
    };

    select.addEventListener("change", syncAction);
    form.addEventListener("submit", syncAction);
    syncAction();
})();

(() => {
    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        modal.querySelector("input, select, button")?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
    };

    document.querySelectorAll("[data-edit-open]").forEach((button) => {
        button.addEventListener("click", () => {
            openModal(document.getElementById(button.dataset.editOpen));
        });
    });

    document.querySelectorAll("[data-edit-close]").forEach((button) => {
        button.addEventListener("click", () => closeModal(button.closest(".modal")));
    });

    document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;
        document.querySelectorAll(".modal.show").forEach(closeModal);
    });
})();
