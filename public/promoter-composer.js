(() => {
    'use strict';
    const panels = [...document.querySelectorAll('[data-profile-panel]')];
    const tabs = [...document.querySelectorAll('[data-profile-tab]')];
    function selectTab(id) {
        panels.forEach(panel => { panel.hidden = panel.id !== id; });
        tabs.forEach(tab => tab.setAttribute('aria-pressed', String(tab.dataset.profileTab === id)));
    }
    tabs.forEach(tab => tab.addEventListener('click', () => selectTab(tab.dataset.profileTab)));
    if (location.hash === '#promoterScreen') selectTab('promoterScreen');
    const dialog = document.getElementById('profileComposer');
    if (!dialog) return;
    const form = document.getElementById('profileComposerForm');
    const fileInput = document.getElementById('composerFile');
    const preview = document.getElementById('composerPreview');
    const placeholder = preview.firstElementChild;
    const publish = document.getElementById('composerPublish');
    const status = document.getElementById('composerStatus');
    const progress = document.getElementById('composerProgress');
    const close = document.getElementById('composerClose');
    let objectUrl = null, busy = false, opener = null, overflow = '', videoOnly = false, selectedFile = null;
    const types = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'];
    const camera = new window.OzmanCreatorCamera(dialog.querySelector('[data-camera]'), file => selectFile(file), text => status.textContent = text, active => {
        preview.hidden = active;publish.disabled = active || busy || !selectedFile;
    });
    function clearPreview() {
        const video = preview.querySelector('video');
        if (video) { video.pause(); video.removeAttribute('src'); video.load(); }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        preview.replaceChildren(placeholder);
    }
    function open(kind, button) {
        if (dialog.open) return;
        camera.stop();form.reset();selectedFile = null;fileInput.required = true;clearPreview();status.textContent = '';progress.hidden = true;publish.disabled = true;
        form.elements.kind.value = kind === 'story' ? 'story' : 'post';
        videoOnly = kind === 'video';
        document.getElementById('composerPhoto').hidden = videoOnly;
        document.getElementById('nativePhoto').closest('label').hidden = videoOnly;
        fileInput.accept = videoOnly ? 'video/mp4,video/webm' : types.join(',');
        document.getElementById('composerTitle').textContent = kind === 'story' ? 'إضافة ستوري' : videoOnly ? 'إضافة فيديو' : 'منشور جديد';
        document.getElementById('composerHint').textContent = kind === 'story' ? 'الستوري بتظهر بصفحتك لمدة 24 ساعة.' : 'المنشور بيظهر على صفحتك.';
        opener = button;overflow = document.body.style.overflow;document.body.style.overflow = 'hidden';
        dialog.showModal();
    }
    document.querySelectorAll('[data-compose]').forEach(button => button.addEventListener('click', () => open(button.dataset.compose, button)));
    document.getElementById('composerChoose').addEventListener('click', () => {if (!busy) {camera.stop();fileInput.click();}});
    document.getElementById('composerPhoto').addEventListener('click', () => {if(!busy) camera.open('photo');});
    document.getElementById('composerVideo').addEventListener('click', () => {if(!busy) camera.open('video');});
    function selectFile(file) {
        camera.stop();selectedFile = null;fileInput.required = true;clearPreview();status.textContent = '';publish.disabled = true;
        if (!file) return;
        if (!types.includes(file.type) || (videoOnly && !file.type.startsWith('video/'))) {
            status.textContent = videoOnly ? 'اختار فيديو MP4 أو WebM.' : 'اختار صورة JPG أو PNG أو WebP، أو فيديو MP4 أو WebM.';
            fileInput.value = '';return;
        }
        if (file.size > 20 * 1024 * 1024) { status.textContent = 'حجم الملف أكبر من 20 ميجابايت.';fileInput.value = '';return; }
        const media = document.createElement(file.type.startsWith('video/') ? 'video' : 'img');
        objectUrl = URL.createObjectURL(file);media.src = objectUrl;
        if (media.tagName === 'VIDEO') {media.controls = true;media.playsInline = true;media.preload = 'metadata';}
        else media.alt = 'معاينة قبل النشر';
        media.addEventListener('error', () => {status.textContent = 'تعذّرت المعاينة على جهازك. يمكنك اختيار ملف آخر أو محاولة نشر الملف المدعوم.';});
        preview.replaceChildren(media);selectedFile = file;fileInput.required = false;publish.disabled = false;
    }
    fileInput.addEventListener('change', () => selectFile(fileInput.files[0]));
    ['nativePhoto','nativeVideo'].forEach(id => document.getElementById(id).addEventListener('change', event => selectFile(event.target.files[0])));
    close.addEventListener('click', () => {if (!busy) dialog.close();});
    dialog.addEventListener('cancel', event => {if (busy) event.preventDefault();});
    dialog.addEventListener('close', () => {camera.stop();selectedFile = null;clearPreview();form.reset();document.body.style.overflow = overflow;opener?.focus({preventScroll:true});});
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (busy || !selectedFile || !form.reportValidity()) return;
        camera.stop();
        const body = new FormData(form);
        body.set('media', selectedFile, selectedFile.name);
        busy = true;publish.disabled = true;close.disabled = true;
        form.querySelectorAll('input,textarea,select').forEach(field => field.disabled = true);
        progress.hidden = false;progress.value = 0;publish.textContent = 'جارٍ النشر…';status.textContent = 'جارٍ رفع الملف…';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);xhr.setRequestHeader('Accept', 'application/json');xhr.timeout = 180000;
        function finish(message) {
            busy = false;publish.disabled = false;close.disabled = false;publish.textContent = 'نشر';
            form.querySelectorAll('input,textarea,select').forEach(field => field.disabled = false);
            progress.hidden = true;status.textContent = message;
        }
        xhr.upload.addEventListener('progress', event => {
            if (event.lengthComputable) {progress.value = Math.round(event.loaded / event.total * 100);status.textContent = progress.value === 100 ? 'اكتمل الرفع، جارٍ حفظ المنشور…' : `جارٍ الرفع ${progress.value}%`;}
        });
        xhr.addEventListener('load', () => {
            let result = {};try {result = JSON.parse(xhr.responseText);} catch (_) {}
            if (xhr.status === 201 && result.published === true) {
                status.textContent = 'تم النشر. جارٍ تحديث الصفحة…';location.reload();return;
            }
            const errors = result.errors ? Object.values(result.errors).flat().filter(value => typeof value === 'string').join(' ') : '';
            finish(xhr.status === 419 ? 'انتهت الجلسة. حدّث الصفحة وسجّل الدخول مجددًا.' : xhr.status === 401 || xhr.status === 403 ? 'سجّل الدخول بحساب صاحب الصفحة لإضافة المحتوى.' : xhr.status === 413 ? 'الملف أكبر من الحد المسموح على السيرفر. اختار ملفًا أصغر.' : xhr.status === 429 ? 'انتظر قليلًا ثم حاول النشر مجددًا.' : errors || 'تعذّر تأكيد النشر. راجع صفحتك قبل إعادة المحاولة لتجنّب تكرار المنشور.');
        });
        xhr.addEventListener('error', () => finish('انقطع الاتصال. راجع صفحتك قبل إعادة النشر للتأكد إن المنشور ما انحفظ.'));
        xhr.addEventListener('timeout', () => finish('تأخر الرفع. راجع صفحتك قبل إعادة النشر للتأكد إن المنشور ما انحفظ.'));
        xhr.send(body);
    });
})();
