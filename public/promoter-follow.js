(() => {
    const form = document.getElementById('promoterFollowForm');
    if (!form) return;
    const button = form.querySelector('button');
    const method = form.querySelector('[name="_method"]');
    const status = document.getElementById('promoterFollowStatus');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        const label = button.textContent;
        button.textContent = 'جاري الحفظ…';
        status.textContent = '';
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(form.action, {
                method: method.value, credentials: 'same-origin', signal: controller.signal,
                headers: {Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            });
            if (response.status === 401) { location.assign(form.dataset.login); return; }
            if (!response.ok) {
                throw new Error(response.status === 419 ? 'انتهت الجلسة. حدّث الصفحة وحاول مجددًا.' : response.status === 429 ? 'انتظر قليلًا ثم حاول مجددًا.' : 'تعذّر حفظ المتابعة. حاول مجددًا.');
            }
            const data = await response.json();
            if (typeof data.following !== 'boolean' || !Number.isInteger(data.followers_count)) throw new Error('تعذّر تأكيد المتابعة. حدّث الصفحة.');
            method.value = data.following ? 'DELETE' : 'PUT';
            button.setAttribute('aria-pressed', String(data.following));
            button.classList.toggle('outline', data.following);
            button.textContent = data.following ? 'إلغاء المتابعة' : 'متابعة +';
            document.getElementById('promoterFollowerCount').textContent = String(data.followers_count);
            status.textContent = data.following ? 'تمت متابعة الحساب.' : 'تم إلغاء المتابعة.';
        } catch (error) {
            button.textContent = label;
            status.textContent = error.name === 'AbortError' ? 'تأخر الاتصال. حاول مجددًا للتأكد من حفظ المتابعة.' : error.message;
        } finally {
            clearTimeout(timer);
            button.disabled = false;
        }
    });
})();
