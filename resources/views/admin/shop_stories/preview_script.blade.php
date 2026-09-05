<script>
(() => {
    const file = document.getElementById('media'), caption = document.getElementById('caption'), shop = document.getElementById('shop');
    const preview = document.getElementById('storyPreviewMedia'), feedback = document.getElementById('selectedStoryFile');
    const placeholder = preview.innerHTML;
    let objectUrl = null;
    const updateText = () => {
        document.getElementById('previewShopName').textContent = shop.selectedOptions[0]?.textContent || 'محلك';
        document.getElementById('previewCaption').textContent = caption.value;
        document.getElementById('captionCount').textContent = caption.value.length + ' / 300';
    };
    caption.addEventListener('input', updateText); shop.addEventListener('change', updateText); updateText();
    file.addEventListener('change', () => {
        preview.querySelector('video')?.pause();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null; preview.innerHTML = placeholder; file.setCustomValidity('');
        const selected = file.files[0];
        if (!selected) { feedback.textContent = 'لم يتم اختيار ملف بعد'; return; }
        if (selected.size > 20 * 1024 * 1024) {
            file.setCustomValidity('حجم الملف أكبر من 20 ميغابايت'); feedback.textContent = 'اختر ملفًا بحجم 20 ميغابايت أو أقل'; file.reportValidity(); return;
        }
        const supported = ['image/jpeg','image/png','image/webp','video/mp4','video/webm'];
        if (!supported.includes(selected.type)) { feedback.textContent = 'هذا التنسيق غير مدعوم'; file.setCustomValidity('اختر صورة أو فيديو بالتنسيقات الموضحة'); file.reportValidity(); return; }
        objectUrl = URL.createObjectURL(selected);
        const element = document.createElement(selected.type.startsWith('video/') ? 'video' : 'img');
        if (element.tagName === 'VIDEO') { element.controls = true; element.playsInline = true; element.preload = 'metadata'; } else element.alt = 'معاينة الصورة المختارة';
        element.src = objectUrl; preview.replaceChildren(element);
        feedback.textContent = selected.name + ' · ' + (selected.size / 1024 / 1024).toFixed(1) + ' MB';
    });
    document.getElementById('storyComposer').addEventListener('submit', event => {
        if (!event.target.checkValidity()) return;
        const button = event.target.querySelector('[type="submit"]'); button.disabled = true; button.textContent = 'جارٍ رفع ونشر الستوري…';
    });
    window.addEventListener('pageshow', () => { const button = document.querySelector('.publish-button'); button.disabled = false; button.textContent = 'نشر الستوري ↗'; });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
})();
</script>
