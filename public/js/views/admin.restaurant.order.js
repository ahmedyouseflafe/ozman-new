(() => {
    const form = document.getElementById("order-status-form");
    if (!form) return;
    const minutesInput = document.getElementById("preparation-minutes");
    const feedback = document.getElementById("order-status-feedback");
    const choices = [...form.querySelectorAll(".order-preparation-choice")];
    choices.forEach((choice) =>
        choice.addEventListener("click", async () => {
            minutesInput.value = choice.dataset.preparationMinutes;
            choices.forEach((c) => c.classList.toggle("is-selected", c === choice));
            choices.forEach((c) => (c.disabled = true));
            feedback.className = "order-status-feedback";
            feedback.textContent = "جارٍ الحفظ...";
            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok)
                    throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || "تعذّر حفظ التحديث.");
                feedback.classList.add("is-success");
                feedback.textContent = "تم الحفظ وإشعار الزبون.";
                window.setTimeout(() => window.location.reload(), 600);
            } catch (error) {
                feedback.classList.add("is-error");
                feedback.textContent = error.message;
                choices.forEach((c) => (c.disabled = false));
            }
        }),
    );
})();
