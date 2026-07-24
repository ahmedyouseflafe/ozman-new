<div class="form-group full" id="catalogCategoryGuidance"
    style="padding:16px;border:1px solid rgba(0,229,255,.22);border-radius:16px;background:rgba(0,229,255,.06)">
    <strong id="catalogCategoryTitle" style="display:block;color:#00e5ff;margin-bottom:6px"></strong>
    <span id="catalogCategoryDescription" style="display:block;color:rgba(255,255,255,.62);font-size:13px"></span>
    <div id="catalogCategorySuggestions" style="display:flex;flex-wrap:wrap;gap:7px;margin-top:12px"></div>
</div>

<script>
    (() => {
        const select = document.getElementById('shop_id');
        const types = @json(config('catalog_types', []));
        const title = document.getElementById('catalogCategoryTitle');
        const description = document.getElementById('catalogCategoryDescription');
        const suggestions = document.getElementById('catalogCategorySuggestions');

        function renderGuidance() {
            const typeKey = select?.selectedOptions?.[0]?.dataset?.catalogType || 'general';
            const type = types[typeKey] || types.general;
            title.textContent = `فئات مناسبة لنشاط: ${type.label}`;
            description.textContent = type.description;
            suggestions.innerHTML = '';
            (type.suggested_categories || []).forEach((category) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = category;
                button.style.cssText = 'border:1px solid rgba(255,255,255,.16);background:rgba(0,0,0,.25);color:#fff;border-radius:999px;padding:7px 11px;font-family:inherit;cursor:pointer';
                button.addEventListener('click', () => {
                    const nameInput = document.getElementById('name');
                    if (!nameInput) return;
                    nameInput.value = category;
                    nameInput.dispatchEvent(new Event('input', { bubbles: true }));
                });
                suggestions.appendChild(button);
            });
        }

        select?.addEventListener('change', renderGuidance);
        renderGuidance();
    })();
</script>
