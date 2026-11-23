document.querySelectorAll("[data-copy-target]").forEach((button) => {
    button.addEventListener("click", async function () {
        const target = document.getElementById(this.dataset.copyTarget);
        if (!target) return;

        const value = target.value;

        try {
            await navigator.clipboard.writeText(value);
        } catch (error) {
            target.select();
            document.execCommand("copy");
            target.blur();
        }

        const originalText = this.innerHTML;
        this.innerHTML = '<i class="ti ti-check" aria-hidden="true"></i> تم النسخ';
        setTimeout(() => {
            this.innerHTML = originalText;
        }, 1400);
    });
});
