(() => {
    const form = document.getElementById('promoterFollowForm');
    if (!form) return;
    const button = form.querySelector('button');
    const method = form.querySelector('[name="_method"]');
    const status = document.getElementById('promoterFollowStatus');
    let t = {};
    try { t = JSON.parse(form.dataset.i18n || '{}'); } catch (_) {}
    const PROMPT_KEY = 'ozmanGuestFollowPrompted';

    const setFollowing = following => {
        method.value = following ? 'DELETE' : 'PUT';
        button.setAttribute('aria-pressed', String(following));
        button.classList.toggle('outline', following);
        button.classList.toggle('is-following', following);
    };

    // Small, non-blocking invitation shown once per device after the first guest follow.
    const showGuestPrompt = () => {
        try { if (localStorage.getItem(PROMPT_KEY)) return; localStorage.setItem(PROMPT_KEY, '1'); } catch (_) {}
        document.querySelector('.follow-toast')?.remove();
        const toast = document.createElement('div');
        toast.className = 'follow-toast';
        toast.setAttribute('role', 'status');
        const text = document.createElement('p');
        text.textContent = t.guestPrompt || '';
        const actions = document.createElement('div');
        const signup = document.createElement('a');
        signup.className = 'button small';
        signup.href = form.dataset.signup;
        signup.textContent = t.createAccount || '';
        const later = document.createElement('button');
        later.type = 'button';
        later.className = 'text-link';
        later.textContent = t.later || '';
        later.addEventListener('click', () => toast.remove());
        actions.append(signup, later);
        toast.append(text, actions);
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('is-visible'));
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        button.classList.add('is-busy');
        button.setAttribute('aria-busy', 'true');
        status.textContent = '';
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(form.action, {
                method: method.value, credentials: 'same-origin', signal: controller.signal,
                headers: {Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            });
            if (!response.ok) {
                throw new Error(response.status === 419 ? t.expired : response.status === 429 ? t.tooMany : t.failed);
            }
            const data = await response.json();
            if (typeof data.following !== 'boolean' || !Number.isInteger(data.followers_count)) throw new Error(t.unconfirmed);
            setFollowing(data.following);
            // Keep showing "following" under the pointer that just clicked; the unfollow hint returns after it leaves.
            if (data.following) {
                button.classList.add('hover-paused');
                button.addEventListener('mouseleave', () => button.classList.remove('hover-paused'), {once: true});
            }
            document.getElementById('promoterFollowerCount').textContent = String(data.followers_count);
            status.textContent = data.following ? t.followed : t.unfollowed;
            if (data.guest_prompt) showGuestPrompt();
        } catch (error) {
            status.textContent = error.name === 'AbortError' ? t.timeout : error.message;
        } finally {
            clearTimeout(timer);
            button.disabled = false;
            button.classList.remove('is-busy');
            button.removeAttribute('aria-busy');
        }
    });
})();
