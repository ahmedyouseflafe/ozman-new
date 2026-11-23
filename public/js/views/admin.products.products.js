const prodSearch = document.getElementById("prodSearch");
const catFilter = document.getElementById("catFilter");
const prodRows = document.querySelectorAll("#productCards .meal-card");

function filterProducts() {
    const query = (prodSearch?.value || "").trim().toLowerCase();
    const category = catFilter?.value || "";

    prodRows.forEach((row) => {
        const matchesQuery = [row.dataset.name, row.dataset.shop, row.dataset.categoryName]
            .join(" ")
            .toLowerCase()
            .includes(query);
        const matchesCategory = !category || row.dataset.cat === category;
        row.style.display = matchesQuery && matchesCategory ? "" : "none";
    });
}

prodSearch?.addEventListener("input", filterProducts);
catFilter?.addEventListener("change", filterProducts);

async function saveQuickMeal(form, previousActive = form.querySelector('input[name="is_active"]')?.value) {
    if (form.dataset.saving === "true") return;

    const card = form.closest(".meal-card");
    const feedback = form.querySelector(".meal-quick-feedback");
    const activeInput = form.querySelector('input[name="is_active"]');
    const priceInput = form.querySelector('input[name="price"]');
    const powerButton = form.querySelector("[data-meal-toggle]");
    const saveButton = form.querySelector(".meal-save");
    const controls = [powerButton, saveButton].filter(Boolean);

    form.dataset.saving = "true";
    controls.forEach((control) => (control.disabled = true));
    if (feedback) {
        feedback.textContent = "جارٍ الحفظ...";
        feedback.className = "meal-quick-feedback";
    }

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validationError = Object.values(data.errors || {}).flat()[0];
            throw new Error(validationError || data.message || "تعذر حفظ المنتج.");
        }

        const isActive = Boolean(data.is_active);
        activeInput.value = isActive ? "1" : "0";
        if (priceInput && data.price !== undefined) priceInput.value = data.price;
        card?.classList.toggle("is-inactive", !isActive);
        powerButton?.classList.toggle("is-active", isActive);
        powerButton?.setAttribute("aria-label", isActive ? "إيقاف المنتج" : "تشغيل المنتج");
        const powerCopy = powerButton?.querySelector("[data-meal-power-copy]");
        if (powerCopy) powerCopy.textContent = isActive ? "متاحة" : "موقوفة";
        if (feedback) {
            feedback.textContent = "تم الحفظ ✓";
            feedback.classList.add("is-success");
        }
    } catch (error) {
        if (activeInput && previousActive !== undefined) activeInput.value = previousActive;
        if (feedback) {
            feedback.textContent = error.message || "تعذر حفظ المنتج.";
            feedback.classList.add("is-error");
        }
    } finally {
        delete form.dataset.saving;
        controls.forEach((control) => (control.disabled = false));
    }
}

document.querySelectorAll(".js-meal-quick-form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        saveQuickMeal(form);
    });

    form.querySelector("[data-meal-toggle]")?.addEventListener("click", () => {
        const activeInput = form.querySelector('input[name="is_active"]');
        if (!activeInput || form.dataset.saving === "true") return;
        const previousActive = activeInput.value;
        activeInput.value = activeInput.value === "1" ? "0" : "1";
        saveQuickMeal(form, previousActive);
    });
});

document.querySelectorAll("[data-category-editor]").forEach((editor) => {
    const card = editor.closest(".meal-card");
    const toggle = card.querySelector("[data-move-category]");
    const select = editor.querySelector("[data-category-select]");
    const save = editor.querySelector("[data-category-save]");
    const cancel = editor.querySelector("[data-category-cancel]");
    const feedback = editor.querySelector("[data-category-feedback]");
    const close = () => {
        editor.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
    };
    toggle.addEventListener("click", () => {
        if (!editor.hidden) {
            close();
            return;
        }
        select.value = card.dataset.cat;
        feedback.textContent = "";
        editor.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
        select.focus();
    });
    cancel.addEventListener("click", close);
    editor.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !save.disabled) {
            event.preventDefault();
            close();
        }
        if (event.key === "Enter") {
            event.preventDefault();
            save.click();
        }
    });
    save.addEventListener("click", async () => {
        if (save.disabled) return;
        if (!select.value) {
            feedback.textContent = "اختر القسم أولًا.";
            return;
        }
        if (select.value === card.dataset.cat) {
            close();
            return;
        }
        [save, cancel, select, toggle].forEach((control) => (control.disabled = true));
        feedback.textContent = "جارٍ نقل المنتج...";
        feedback.className = "meal-quick-feedback";
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(editor.dataset.url, {
                method: "PATCH",
                credentials: "same-origin",
                signal: controller.signal,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": card.querySelector('input[name="_token"]').value,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ category_id: Number(select.value) }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(
                    response.status === 419
                        ? "انتهت الجلسة. حدّث الصفحة وحاول مجددًا."
                        : Object.values(data.errors || {}).flat()[0] || data.message || "تعذر نقل المنتج.",
                );
            }
            if (!data.category_id || typeof data.category_name !== "string")
                throw new Error("تعذر تأكيد النقل. حدّث الصفحة للتحقق.");
            card.dataset.cat = String(data.category_id);
            card.dataset.categoryName = data.category_name;
            card.querySelector(".meal-category").textContent = data.category_name;
            const notice = document.querySelector("[data-product-feedback]");
            notice.textContent = `تم نقل ${card.dataset.name} إلى ${data.category_name}.`;
            notice.className = "meal-quick-feedback is-success";
            close();
            filterProducts();
        } catch (error) {
            feedback.textContent =
                error.name === "AbortError"
                    ? "تأخر الرد. حدّث الصفحة للتحقق من القسم قبل المحاولة مجددًا."
                    : error.message || "تعذر نقل المنتج. حاول مجددًا.";
            feedback.classList.add("is-error");
        } finally {
            clearTimeout(timer);
            [save, cancel, select, toggle].forEach((control) => (control.disabled = false));
            if (editor.hidden && card.style.display !== "none") toggle.focus();
        }
    });
});
