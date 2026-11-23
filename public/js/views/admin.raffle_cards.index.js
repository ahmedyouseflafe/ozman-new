const manualRaffleCardForm = document.getElementById("manualRaffleCardForm");
const manualRaffleSubmit = manualRaffleCardForm?.querySelector('button[type="submit"]');
const manualRaffleDefaultSubmitHtml = manualRaffleSubmit?.innerHTML || "";
const manualRaffleFeedback = document.createElement("div");
manualRaffleFeedback.className = "ajax-feedback";
manualRaffleFeedback.id = "manualRaffleCardFeedback";
manualRaffleFeedback.setAttribute("role", "status");
manualRaffleFeedback.setAttribute("aria-live", "polite");
manualRaffleFeedback.hidden = true;
manualRaffleCardForm?.append(manualRaffleFeedback);

function showManualRaffleFeedback(message, type) {
    manualRaffleFeedback.className = `ajax-feedback ${type}`;
    manualRaffleFeedback.textContent = message;
    manualRaffleFeedback.hidden = false;
}

manualRaffleCardForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!manualRaffleCardForm.reportValidity()) return;

    manualRaffleFeedback.hidden = true;
    manualRaffleCardForm.classList.add("is-submitting");
    if (manualRaffleSubmit) {
        manualRaffleSubmit.disabled = true;
        manualRaffleSubmit.innerHTML = '<i class="ti ti-loader-2"></i> جاري الإضافة...';
    }

    try {
        const response = await fetch(manualRaffleCardForm.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: new FormData(manualRaffleCardForm),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const errors = Object.values(payload.errors || {}).flat();
            throw new Error(errors[0] || payload.message || "تعذر إضافة البطاقة. حاول مرة أخرى.");
        }

        const tableBody = document.getElementById("winningCardsTableBody");
        tableBody?.querySelector('td[colspan="6"]')?.closest("tr")?.remove();
        tableBody?.insertAdjacentHTML("afterbegin", payload.row_html || "");
        const insertedCheckbox = tableBody?.querySelector("[data-winning-card-row] [data-winning-card-checkbox]");
        if (insertedCheckbox) {
            winningCardCheckboxes.unshift(insertedCheckbox);
            insertedCheckbox.addEventListener("change", syncWinningCardBulkActions);
        }
        const totalCards = document.querySelector(".grid .card strong");
        if (totalCards && Number.isFinite(Number(payload.total_cards))) totalCards.textContent = payload.total_cards;

        manualRaffleCardForm.reset();
        const activeInput = manualRaffleCardForm.querySelector('[name="is_active"]');
        if (activeInput) activeInput.checked = true;
        const imageLabel = manualRaffleCardForm.querySelector("[data-file-label]");
        if (imageLabel) imageLabel.textContent = "اختر صورة الجائزة";
        showManualRaffleFeedback(payload.message || "تمت إضافة بطاقة الربح بنجاح.", "status");
        document.getElementById("card_number")?.focus();
    } catch (error) {
        showManualRaffleFeedback(error.message || "تعذر إضافة البطاقة. حاول مرة أخرى.", "errors");
    } finally {
        manualRaffleCardForm.classList.remove("is-submitting");
        if (manualRaffleSubmit) {
            manualRaffleSubmit.disabled = false;
            manualRaffleSubmit.innerHTML = manualRaffleDefaultSubmitHtml;
        }
    }
});

document.querySelectorAll("[data-file-input]").forEach((input) => {
    input.addEventListener("change", () => {
        const label = document.querySelector(`label[for="${input.id}"] [data-file-label]`);
        if (label) {
            label.textContent = input.files?.[0]?.name || "ارفع صورة";
        }
    });
});

const bookletStart = document.querySelector("[data-booklet-start]");
const bookletRangePreview = document.getElementById("bookletRangePreview");
const bookletGiftTotal = document.getElementById("bookletGiftTotal");
const bookletGiftCounts = Array.from(document.querySelectorAll("[data-gift-count]"));

function updateBookletPreview() {
    const start = String(bookletStart?.value || "").replace(/\D/g, "");
    if (bookletRangePreview) {
        bookletRangePreview.textContent =
            start.length === 6 ? `${start} — ${String(Number(start) + 47).padStart(6, "0")}` : "أدخل رقم البداية";
    }

    const total = bookletGiftCounts.reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0), 0);
    if (bookletGiftTotal) {
        bookletGiftTotal.textContent = `${total} هدية من 48`;
        bookletGiftTotal.classList.toggle("is-over", total > 48);
    }
}

bookletStart?.addEventListener("input", updateBookletPreview);
bookletGiftCounts.forEach((input) => input.addEventListener("input", updateBookletPreview));
updateBookletPreview();

const liveDrawPickBtn = document.getElementById("liveDrawPickBtn");
const liveDrawModal = document.getElementById("liveDrawModal");
const liveDrawCloseBtn = document.getElementById("liveDrawCloseBtn");
const liveDrawTitle = document.getElementById("liveDrawTitle");
const liveDrawNumber = document.getElementById("liveDrawNumber");
const liveDrawInfo = document.getElementById("liveDrawInfo");
const liveDrawError = document.getElementById("liveDrawError");
const selectAllLiveEntries = document.getElementById("selectAllLiveEntries");
const liveEntryCheckboxes = Array.from(document.querySelectorAll("[data-live-entry-checkbox]"));
const deleteSelectedLiveEntriesBtn = document.getElementById("deleteSelectedLiveEntriesBtn");
const selectAllWinningCards = document.getElementById("selectAllWinningCards");
const winningCardCheckboxes = Array.from(document.querySelectorAll("[data-winning-card-checkbox]"));
const deleteSelectedWinningCardsBtn = document.getElementById("deleteSelectedWinningCardsBtn");

function syncWinningCardBulkActions() {
    const checkedCount = winningCardCheckboxes.filter((checkbox) => checkbox.checked).length;
    if (deleteSelectedWinningCardsBtn) deleteSelectedWinningCardsBtn.disabled = checkedCount === 0;
    if (selectAllWinningCards) {
        selectAllWinningCards.checked = checkedCount > 0 && checkedCount === winningCardCheckboxes.length;
        selectAllWinningCards.indeterminate = checkedCount > 0 && checkedCount < winningCardCheckboxes.length;
    }
}

selectAllWinningCards?.addEventListener("change", () => {
    winningCardCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllWinningCards.checked;
    });
    syncWinningCardBulkActions();
});

winningCardCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", syncWinningCardBulkActions);
});

syncWinningCardBulkActions();

function syncLiveEntryBulkActions() {
    const checkedCount = liveEntryCheckboxes.filter((checkbox) => checkbox.checked).length;
    if (deleteSelectedLiveEntriesBtn) deleteSelectedLiveEntriesBtn.disabled = checkedCount === 0;
    if (selectAllLiveEntries) {
        selectAllLiveEntries.checked = checkedCount > 0 && checkedCount === liveEntryCheckboxes.length;
        selectAllLiveEntries.indeterminate = checkedCount > 0 && checkedCount < liveEntryCheckboxes.length;
    }
}

selectAllLiveEntries?.addEventListener("change", () => {
    liveEntryCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllLiveEntries.checked;
    });
    syncLiveEntryBulkActions();
});

liveEntryCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", syncLiveEntryBulkActions);
});

syncLiveEntryBulkActions();

function escapeLiveDrawText(value) {
    return String(value ?? "").replace(
        /[&<>"']/g,
        (char) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[char],
    );
}

function openLiveDrawModal() {
    liveDrawModal?.classList.add("show");
    liveDrawModal?.setAttribute("aria-hidden", "false");
}

function closeLiveDrawModal() {
    liveDrawModal?.classList.remove("show");
    liveDrawModal?.setAttribute("aria-hidden", "true");
}

function randomSixDigits() {
    return String(Math.floor(Math.random() * 1000000)).padStart(6, "0");
}

liveDrawPickBtn?.addEventListener("click", async () => {
    openLiveDrawModal();
    if (liveDrawTitle) liveDrawTitle.textContent = "جاري اختيار الفائز...";
    if (liveDrawInfo) liveDrawInfo.innerHTML = "";
    if (liveDrawError) {
        liveDrawError.hidden = true;
        liveDrawError.textContent = "";
    }
    liveDrawNumber?.classList.add("spinning");

    const spinTimer = window.setInterval(() => {
        if (liveDrawNumber) liveDrawNumber.textContent = randomSixDigits();
    }, 70);

    liveDrawPickBtn.disabled = true;

    try {
        const response = await fetch(liveDrawPickBtn.dataset.url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": liveDrawPickBtn.dataset.token || "",
            },
            body: JSON.stringify({}),
        });
        const payload = await response.json().catch(() => ({}));

        window.setTimeout(() => {
            window.clearInterval(spinTimer);
            liveDrawNumber?.classList.remove("spinning");

            if (!response.ok || !payload.ok) {
                if (liveDrawTitle) liveDrawTitle.textContent = "لم يتم اختيار فائز";
                if (liveDrawNumber) liveDrawNumber.textContent = "------";
                if (liveDrawError) {
                    liveDrawError.hidden = false;
                    liveDrawError.textContent = payload.message || "تعذر اختيار فائز الآن.";
                }
                return;
            }

            if (liveDrawTitle) liveDrawTitle.textContent = payload.title || "مبروك الفوز";
            if (liveDrawNumber) liveDrawNumber.textContent = payload.card_number || "------";
            if (liveDrawInfo) {
                liveDrawInfo.innerHTML = `
                            <div>الفائز: <strong>${escapeLiveDrawText(payload.customer_name || "-")}</strong></div>
                            <div dir="ltr">${escapeLiveDrawText(payload.customer_whatsapp || payload.customer_phone || "-")}</div>
                            <div>تاريخ التسجيل: <strong>${escapeLiveDrawText(payload.created_at || "-")}</strong></div>
                        `;
            }
        }, 1100);
    } catch (error) {
        window.clearInterval(spinTimer);
        liveDrawNumber?.classList.remove("spinning");
        if (liveDrawTitle) liveDrawTitle.textContent = "تعذر اختيار الفائز";
        if (liveDrawError) {
            liveDrawError.hidden = false;
            liveDrawError.textContent = "راجع الاتصال وحاول مرة أخرى.";
        }
    } finally {
        window.setTimeout(() => {
            liveDrawPickBtn.disabled = false;
        }, 1200);
    }
});

liveDrawCloseBtn?.addEventListener("click", closeLiveDrawModal);
liveDrawModal?.addEventListener("click", (event) => {
    if (event.target === liveDrawModal) closeLiveDrawModal();
});
