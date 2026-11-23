(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || "";

    document.querySelectorAll("[data-status-form]").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const button = form.querySelector("[data-status-save]");
            const badge = form.querySelector("[data-status-badge]");
            const select = form.querySelector('select[name="status"]');
            const originalText = button?.textContent || "حفظ";

            if (button) {
                button.disabled = true;
                button.textContent = "جاري...";
            }

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": token,
                    },
                    body: new FormData(form),
                });
                const result = await response.json().catch(() => ({}));

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || "تعذر حفظ الحالة.");
                }

                if (badge) {
                    badge.textContent = result.label || select?.selectedOptions?.[0]?.textContent || "";
                    badge.className = `badge ${result.class || ""}`.trim();
                }

                if (button) {
                    button.textContent = "تم";
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.disabled = false;
                    }, 800);
                }
            } catch (error) {
                if (button) {
                    button.textContent = "فشل";
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.disabled = false;
                    }, 1200);
                }
            }
        });
    });
})();
