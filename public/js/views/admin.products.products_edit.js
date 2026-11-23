const shopSelect = document.getElementById("shop_id");
const categorySelect = document.getElementById("category_id");
const agentSelect = document.getElementById("agent_id");
function filterCategories() {
    const shopId = shopSelect.value;
    [...categorySelect.options].forEach((option) => {
        option.hidden = option.value && shopId && option.dataset.shopId !== shopId;
    });

    [...agentSelect.options].forEach((option) => {
        option.hidden = option.value && shopId && option.dataset.shopId !== shopId;
    });

    if (agentSelect.selectedOptions[0]?.hidden) {
        agentSelect.value = "";
    }
}
shopSelect.addEventListener("change", filterCategories);
filterCategories();

const campaignList = document.getElementById("campaignList");
const addCampaign = document.getElementById("addCampaign");
let campaignIndex = campaignList.querySelectorAll("[data-campaign-card]").length;

function refreshCampaignNumbers() {
    campaignList.querySelectorAll("[data-campaign-card]").forEach((card, index) => {
        card.querySelector("[data-campaign-number]").textContent = index + 1;
        card.querySelector("[data-remove-campaign]").hidden = campaignList.children.length === 1;
    });
}

function notifyCampaignsUpdated() {
    document.dispatchEvent(new CustomEvent("product-campaigns-updated"));
}

function refreshOfferFields(scope = document) {
    const cards = scope.matches?.(".campaign-card") ? [scope] : scope.querySelectorAll(".campaign-card");
    cards.forEach((card) => {
        const offerType = card.querySelector('select[name$="[offer_type]"]')?.value || "bundle_price";
        const quantityInput = card.querySelector('input[name$="[offer_quantity]"]');
        const minInput = card.querySelector('input[name$="[min_quantity]"]');
        const maxInput = card.querySelector('input[name$="[max_quantity]"]');
        const showBundle = offerType === "bundle_price";
        const showRange = offerType === "range_price";

        if (quantityInput) {
            quantityInput.closest(".form-group").hidden = !showBundle;
            quantityInput.disabled = !showBundle;
        }
        [minInput, maxInput].forEach((input) => {
            if (!input) return;
            input.closest(".form-group").hidden = !showRange;
            input.disabled = !showRange;
        });
    });
}

document.addEventListener("change", (event) => {
    if (event.target.matches('select[name$="[offer_type]"]'))
        refreshOfferFields(event.target.closest(".campaign-card"));
});

function campaignTemplate(index) {
    return `
                <div class="campaign-card" data-campaign-card>
                    <div class="campaign-head">
                        <div class="campaign-title"><i class="ti ti-ad"></i>حملة جديدة رقم <span data-campaign-number>${index + 1}</span></div>
                        <button type="button" class="campaign-remove" data-remove-campaign>حذف</button>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة</label>
                            <input type="text" name="campaigns[${index}][title]" data-auto-translate-source placeholder="مثال: عرض الصيف">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة بالإنجليزي</label>
                            <input type="text" name="campaigns[${index}][title_en]" dir="ltr">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة بالعبري</label>
                            <input type="text" name="campaigns[${index}][title_he]">
                        </div>
                        <div class="form-group">
                            <label class="form-label">نوع الحملة</label>
                            <select name="campaigns[${index}][type]">
                                <option value="image">صورة</option>
                                <option value="video">فيديو</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">نوع العرض</label>
                            <select name="campaigns[${index}][offer_type]">
                                <option value="bundle_price">عدد بسعر محدد</option>
                                <option value="range_price">سعر حسب نطاق الكمية</option>
                                <option value="custom">عرض مخصص</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">تطبيق الحملة على</label>
                            <select name="campaigns[${index}][unit_key]">
                                <option value="">السعر الأساسي</option>
                                <option value="package">العبوة</option>
                                <option value="pallet">المشطاح</option>
                                <option value="carton">الكرتونة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">عدد القطع في العرض</label>
                            <input type="number" min="1" name="campaigns[${index}][offer_quantity]" placeholder="مثال: 3">
                        </div>
                        <div class="form-group"><label class="form-label">الكمية من</label><input type="number" min="1" name="campaigns[${index}][min_quantity]" placeholder="مثال: 40"></div>
                        <div class="form-group"><label class="form-label">الكمية إلى</label><input type="number" min="1" name="campaigns[${index}][max_quantity]" placeholder="مثال: 100"></div>
                        <div class="form-group">
                            <label class="form-label">سعر الوحدة في العرض</label>
                            <input type="number" min="0" step="0.01" name="campaigns[${index}][offer_price]" placeholder="مثال: 10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">بداية العرض</label>
                            <input type="date" name="campaigns[${index}][starts_at]">
                        </div>
                        <div class="form-group">
                            <label class="form-label">نهاية العرض</label>
                            <input type="date" name="campaigns[${index}][ends_at]">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض</label>
                            <textarea name="campaigns[${index}][offer_note]" data-auto-translate-source placeholder="مثال: اشتري 3 عبوات بسعر 10 بدل 12"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض بالإنجليزي</label>
                            <textarea name="campaigns[${index}][offer_note_en]" dir="ltr"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض بالعبري</label>
                            <textarea name="campaigns[${index}][offer_note_he]"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="upload-box">
                                <input type="file" name="campaigns[${index}][media]" accept="image/*,video/*">
                                <span class="card-icon"><i class="ti ti-upload"></i></span>
                                <span><span class="card-title">ملف الحملة</span><span class="card-sub">اختياري: اختر صورة أو فيديو للحملة</span></span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
}

addCampaign.addEventListener("click", () => {
    campaignList.insertAdjacentHTML("beforeend", campaignTemplate(campaignIndex));
    campaignIndex++;
    refreshCampaignNumbers();
    refreshOfferFields(campaignList.lastElementChild);
    notifyCampaignsUpdated();
});

campaignList.addEventListener("click", (event) => {
    if (!event.target.matches("[data-remove-campaign]") || campaignList.children.length <= 1) {
        return;
    }

    event.target.closest("[data-campaign-card]").remove();
    refreshCampaignNumbers();
    notifyCampaignsUpdated();
});

window.ensureProductCampaignCards = (maxIndex) => {
    while (campaignIndex <= maxIndex) {
        campaignList.insertAdjacentHTML("beforeend", campaignTemplate(campaignIndex));
        campaignIndex++;
    }

    refreshCampaignNumbers();
};

refreshCampaignNumbers();
refreshOfferFields();
