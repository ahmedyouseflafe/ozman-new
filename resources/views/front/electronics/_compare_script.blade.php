(() => {
    const bar = document.getElementById('compareBar');
    if (!bar) return;

    const list = document.getElementById('compareList');
    const count = document.getElementById('compareCount');
    const modal = document.getElementById('compareModal');
    const table = document.getElementById('compareTable');
    const openButton = document.getElementById('openCompare');
    const storageKey = `ozman:electronics:compare:${bar.dataset.shopId}`;
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char]);
    let selected = [];

    try {
        selected = JSON.parse(localStorage.getItem(storageKey) || '[]').filter(item => item && item.id).slice(0, 3);
    } catch (_) {
        selected = [];
    }

    const productFrom = button => {
        try {
            return JSON.parse(button.closest('[data-compare]')?.dataset.compare || '');
        } catch (_) {
            return null;
        }
    };
    const save = () => {
        try { localStorage.setItem(storageKey, JSON.stringify(selected)); } catch (_) {}
    };
    const closeComparison = () => {
        modal.classList.remove('open');
        document.body.classList.remove('compare-open');
    };
    const syncButtons = () => document.querySelectorAll('button.compare').forEach(button => {
        const item = productFrom(button);
        const on = item && selected.some(saved => Number(saved.id) === Number(item.id));
        button.classList.toggle('selected', Boolean(on));
        button.setAttribute('aria-label', on ? 'إزالة من المقارنة' : 'إضافة للمقارنة');
        button.setAttribute('aria-pressed', String(Boolean(on)));
    });
    const draw = () => {
        bar.classList.toggle('show', selected.length > 0);
        count.textContent = selected.length === 1 ? 'تمت الإضافة — اختر جهازاً آخر' : `${selected.length} من 3 أجهزة`;
        openButton.disabled = selected.length < 2;
        list.innerHTML = selected.map(item => `<div class="compare-item"><img src="${escapeHtml(item.image)}" alt=""><span><b>${escapeHtml(item.name)}</b><br><span style="color:var(--cyan)">${Number(item.price || 0).toLocaleString('ar', { minimumFractionDigits: 2 })} ₪</span></span><button type="button" class="compare-remove" data-remove="${Number(item.id)}" aria-label="إزالة"><i class="ti ti-x"></i></button></div>`).join('');
        syncButtons();
        save();
    };
    const rows = [['price', 'السعر', value => `${Number(value || 0).toLocaleString('ar', { minimumFractionDigits: 2 })} ₪`], ['brand', 'الماركة'], ['model', 'الموديل'], ['condition', 'الحالة'], ['network', 'الشبكة'], ['screen', 'الشاشة'], ['processor', 'المعالج'], ['storage', 'سعات التخزين'], ['ram', 'الرام'], ['colors', 'الألوان'], ['battery', 'البطارية'], ['battery_health', 'صحة البطارية', value => value ? `${value}%` : '—'], ['cameras', 'الكاميرات'], ['warranty', 'الضمان', value => value ? `${value} شهر` : '—']];
    const openComparison = () => {
        if (selected.length < 2) return;
        const heading = `<tr><th>المواصفة</th>${selected.map(item => `<th class="compare-product"><img src="${escapeHtml(item.image)}" alt=""><b>${escapeHtml(item.name)}</b><a href="${escapeHtml(item.url)}">عرض الجهاز <i class="ti ti-arrow-up-left"></i></a></th>`).join('')}</tr>`;
        const body = rows.map(([key, label, format]) => {
            const values = selected.map(item => item[key] ?? '');
            const different = new Set(values.map(value => String(value).trim().toLowerCase())).size > 1;
            return `<tr class="${different ? 'different' : ''}"><td>${label}</td>${values.map(value => `<td>${escapeHtml(format ? format(value) : (value || '—'))}</td>`).join('')}</tr>`;
        }).join('');
        table.innerHTML = `<table class="compare-table"><thead>${heading}</thead><tbody>${body}</tbody></table>`;
        modal.classList.add('open');
        document.body.classList.add('compare-open');
        document.getElementById('closeCompare').focus();
    };

    window.electronicsCompareToggle = (compareButton, event) => {
        event?.preventDefault();
        event?.stopPropagation();
        const item = productFrom(compareButton);
        if (!item) return false;
        const index = selected.findIndex(saved => Number(saved.id) === Number(item.id));
        if (index >= 0) selected.splice(index, 1);
        else if (selected.length < 3) selected.push(item);
        draw();
        return false;
    };

    document.addEventListener('click', event => {
        const compareButton = event.target.closest('button.compare');
        if (!compareButton) return;
        window.electronicsCompareToggle(compareButton, event);
        event.stopImmediatePropagation();
    }, true);
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove]');
        if (!button) return;
        selected = selected.filter(item => Number(item.id) !== Number(button.dataset.remove));
        draw();
        if (selected.length < 2) closeComparison();
    });
    document.getElementById('clearCompare').addEventListener('click', () => { selected = []; draw(); closeComparison(); });
    openButton.addEventListener('click', openComparison);
    document.getElementById('closeCompare').addEventListener('click', closeComparison);
    modal.addEventListener('click', event => { if (event.target === modal) closeComparison(); });
    window.addEventListener('keydown', event => { if (event.key === 'Escape') closeComparison(); });
    draw();
})();
