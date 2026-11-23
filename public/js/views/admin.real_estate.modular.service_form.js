(function () {
    const groups = document.getElementById("option-groups");
    let counter = Date.now();
    function valueHtml(g, v) {
        return `<div class="value-row"><input class="control" name="option_groups[${g}][values][${v}][name]" placeholder="الخيار بالعربية" required><input class="control" name="option_groups[${g}][values][${v}][name_he]" placeholder="العبرية"><input class="control" name="option_groups[${g}][values][${v}][name_en]" placeholder="English" dir="ltr"><label class="check"><input type="hidden" name="option_groups[${g}][values][${v}][is_default]" value="0"><input type="checkbox" name="option_groups[${g}][values][${v}][is_default]" value="1"><span>افتراضي</span></label><button class="icon-btn danger remove-value" type="button"><i class="ti ti-x"></i></button></div>`;
    }
    function groupHtml(g) {
        return `<article class="option-group" data-group-index="${g}"><div class="group-head"><strong>قائمة اختيار جديدة</strong><button class="icon-btn danger remove-group" type="button"><i class="ti ti-trash"></i></button></div><div class="form-grid"><label class="field"><span>اسم القائمة بالعربية *</span><input class="control" name="option_groups[${g}][name]" required></label><label class="field"><span>الاسم بالعبرية</span><input class="control" name="option_groups[${g}][name_he]"></label><label class="field"><span>الاسم بالإنجليزية</span><input class="control" name="option_groups[${g}][name_en]" dir="ltr"></label><label class="field"><span>شرح قصير للعميل</span><input class="control" name="option_groups[${g}][help_text]"></label><label class="field"><span>الشرح بالعبرية</span><input class="control" name="option_groups[${g}][help_text_he]"></label><label class="field"><span>الشرح بالإنجليزية</span><input class="control" name="option_groups[${g}][help_text_en]" dir="ltr"></label><label class="check full"><input type="hidden" name="option_groups[${g}][is_required]" value="0"><input type="checkbox" name="option_groups[${g}][is_required]" value="1" checked><span>هذا الاختيار مطلوب من العميل</span></label></div><div class="option-values">${valueHtml(g, ++counter)}</div><button class="btn add-value" type="button" style="margin-top:10px"><i class="ti ti-plus"></i> إضافة خيار</button></article>`;
    }
    document
        .getElementById("add-group")
        .addEventListener("click", () => groups.insertAdjacentHTML("beforeend", groupHtml(++counter)));
    groups.addEventListener("click", function (event) {
        const removeGroup = event.target.closest(".remove-group");
        if (removeGroup) {
            if (groups.querySelectorAll(".option-group").length === 1) {
                alert("يجب أن تحتوي الخدمة على قائمة اختيار واحدة على الأقل.");
                return;
            }
            removeGroup.closest(".option-group").remove();
            return;
        }
        const removeValue = event.target.closest(".remove-value");
        if (removeValue) {
            const box = removeValue.closest(".option-values");
            if (box.querySelectorAll(".value-row").length === 1) {
                alert("يجب أن تحتوي القائمة على خيار واحد على الأقل.");
                return;
            }
            removeValue.closest(".value-row").remove();
            return;
        }
        const addValue = event.target.closest(".add-value");
        if (addValue) {
            const group = addValue.closest(".option-group");
            group
                .querySelector(".option-values")
                .insertAdjacentHTML("beforeend", valueHtml(group.dataset.groupIndex, ++counter));
        }
    });
})();
