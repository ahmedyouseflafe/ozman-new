(() => {
    'use strict';

    const init = () => {
        const $ = id => document.getElementById(id);
        const dialog = $('bankaiWelcome');
        if (!dialog) return;
        const ui = window.BANKAI_WELCOME_COPY;
        const key = dialog.dataset.storageKey;
        const form = $('bankaiWelcomeForm');
        const edit = $('bankaiProfileEdit');
        const name = $('bankaiName'), phone = $('bankaiWhatsapp'), address = $('bankaiAddress');
        let profile = null, point = null, deferredLocation = false, locationRequest = 0;
        let storageWarningShown = false, previousOverflow = '';
        const normalizePhone = value => String(value || '').replace(/[٠-٩۰-۹]/g, digit => {
            const code = digit.charCodeAt(0);
            return String(code - (code >= 0x6f0 ? 0x6f0 : 0x660));
        }).replace(/[\s()+-]/g, '').replace(/^00/, '');
        const validPhone = value => /^(?:05[02345689]\d{7}|9705[69]\d{7}|9725[023458]\d{7})$/.test(normalizePhone(value));
        const validPoint = value => value && typeof value.latitude === 'number' && typeof value.longitude === 'number'
            && Number.isFinite(value.latitude) && Number.isFinite(value.longitude)
            && Math.abs(value.latitude) <= 90 && Math.abs(value.longitude) <= 180;
        const validProfile = value => value && typeof value.name === 'string' && value.name.trim().length > 0
            && value.name.length <= 120 && validPhone(value.whatsapp)
            && typeof value.address === 'string' && value.address.trim().length > 0 && value.address.length <= 500
            && (validPoint(value.location) || value.locationDeferred === true);
        const status = (id, message, state = '') => {
            $(id).textContent = message;
            $(id).dataset.state = state;
            $(id).hidden = !message;
        };
        const persist = () => {
            try { localStorage.setItem(key, JSON.stringify(profile)); return true; }
            catch (_) { return false; }
        };
        const applyToOrder = () => {
            if (!profile) return;
            $('name').value = profile.name;
            $('phone').value = profile.whatsapp;
            $('address').value = profile.address;
            $('latitude').value = profile.location?.latitude ?? '';
            $('longitude').value = profile.location?.longitude ?? '';
            if ($('locationStatus')) {
                $('locationStatus').textContent = profile.location ? ui.locationReady : ui.locationDeferred;
                $('locationStatus').classList.toggle('ready', Boolean(profile.location));
            }
        };
        const showLocation = () => {
            $('bankaiMap').hidden = !point;
            if (point) {
                $('bankaiMap').href = `https://www.google.com/maps?q=${point.latitude},${point.longitude}`;
                status('bankaiLocationStatus', ui.locationReady, 'success');
            } else {
                $('bankaiMap').removeAttribute('href');
                status('bankaiLocationStatus', deferredLocation ? ui.locationDeferred : '');
            }
        };
        const close = () => {
            locationRequest++;
            dialog.close();
            document.body.style.overflow = previousOverflow;
            edit?.focus({ preventScroll: true });
        };
        const open = () => {
            name.value = profile?.name || '';
            phone.value = profile?.whatsapp || '';
            address.value = profile?.address || '';
            point = profile?.location || null;
            deferredLocation = profile?.locationDeferred || false;
            $('bankaiWelcomeCancel').hidden = !profile;
            $('bankaiLocate').disabled = false;
            status('bankaiWelcomeMessage', '');
            showLocation();
            previousOverflow = document.body.style.overflow;
            dialog.showModal();
            document.body.style.overflow = 'hidden';
            // Start at the welcome heading on mobile without opening the keyboard.
            $('bankaiWelcomeTitle').tabIndex = -1;
            $('bankaiWelcomeTitle').focus({ preventScroll: true });
            dialog.scrollTop = 0;
            refreshPushStatus();
        };
        const pushSupported = () => Boolean(window.isSecureContext && 'Notification' in window
            && 'PushManager' in window && 'serviceWorker' in navigator && window.OzmanOfferNotifications);
        const bounded = promise => new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(new Error('timeout')), 15000);
            Promise.resolve(promise).then(value => { clearTimeout(timer); resolve(value); }, error => { clearTimeout(timer); reject(error); });
        });
        let enablingPush = false;
        const enablePush = async (requestPermission = true) => {
            if (enablingPush || !pushSupported()) return;
            enablingPush = true;
            const button = $('bankaiEnablePush');
            button.disabled = true;
            button.textContent = ui.pushBusy;
            status('bankaiPushStatus', '');
            try {
                // Call the browser prompt immediately within the user's click gesture.
                const permission = requestPermission && Notification.permission === 'default'
                    ? await bounded(Notification.requestPermission()) : Notification.permission;
                if (permission !== 'granted') {
                    status('bankaiPushStatus', permission === 'denied' ? ui.pushDenied : ui.pushDismissed, 'error');
                    return;
                }
                // Permission alone is not a subscription: also persist it on our server.
                await bounded(window.OzmanOfferNotifications.subscribe());
                status('bankaiPushStatus', ui.pushReady, 'success');
                button.hidden = true;
            } catch (_) {
                status('bankaiPushStatus', ui.pushError, 'error');
            } finally {
                enablingPush = false;
                button.disabled = false;
                button.textContent = ui.pushEnable;
            }
        };
        function refreshPushStatus() {
            $('bankaiEnablePush').hidden = false;
            if (!pushSupported()) {
                $('bankaiEnablePush').hidden = true;
                status('bankaiPushStatus', ui.pushUnavailable);
            } else if (Notification.permission === 'granted') {
                enablePush(false);
            } else {
                status('bankaiPushStatus', Notification.permission === 'denied' ? ui.pushDenied : '', 'error');
            }
        }
        $('bankaiEnablePush').addEventListener('click', () => enablePush());
        $('bankaiLocate').addEventListener('click', () => {
            if (!navigator.geolocation) { status('bankaiLocationStatus', ui.locationError, 'error'); return; }
            const request = ++locationRequest;
            $('bankaiLocate').disabled = true;
            status('bankaiLocationStatus', ui.locating);
            navigator.geolocation.getCurrentPosition(position => {
                if (request !== locationRequest || !dialog.open) return;
                $('bankaiLocate').disabled = false;
                const candidate = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                if (!validPoint(candidate)) { status('bankaiLocationStatus', ui.locationError, 'error'); return; }
                point = candidate;
                deferredLocation = false;
                showLocation();
            }, () => {
                if (request !== locationRequest || !dialog.open) return;
                $('bankaiLocate').disabled = false;
                status('bankaiLocationStatus', ui.locationError, 'error');
            }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
        });
        $('bankaiLocateLater').addEventListener('click', () => {
            locationRequest++;
            $('bankaiLocate').disabled = false;
            deferredLocation = true;
            point = null;
            showLocation();
        });
        address.addEventListener('input', () => {
            locationRequest++;
            $('bankaiLocate').disabled = false;
            point = null;
            deferredLocation = false;
            showLocation();
        });
        phone.addEventListener('input', () => phone.setCustomValidity(''));
        form.addEventListener('submit', event => {
            event.preventDefault();
            status('bankaiWelcomeMessage', '');
            if (!name.value.trim() || !address.value.trim()) { status('bankaiWelcomeMessage', ui.required, 'error'); return; }
            if (!validPhone(phone.value)) { phone.setCustomValidity(ui.phoneError); phone.reportValidity(); return; }
            if (!point && !deferredLocation) { status('bankaiWelcomeMessage', ui.locationChoice, 'error'); $('bankaiLocate').focus(); return; }
            profile = { name: name.value.trim(), whatsapp: normalizePhone(phone.value), address: address.value.trim(), location: point, locationDeferred: deferredLocation };
            applyToOrder();
            if (!persist() && !storageWarningShown) {
                storageWarningShown = true;
                status('bankaiWelcomeMessage', ui.storageError);
                $('bankaiWelcomeSave').textContent = ui.continue;
                return;
            }
            close();
        });
        dialog.addEventListener('cancel', event => {
            event.preventDefault();
            if (profile) close();
        });
        $('bankaiWelcomeCancel').addEventListener('click', close);
        edit?.addEventListener('click', open);
        // Keep the next visit consistent with details changed in the order panel.
        const saveOrderDetails = () => {
            if (!profile) return;
            const candidate = {
                name: $('name').value.trim(), whatsapp: normalizePhone($('phone').value), address: $('address').value.trim(),
                location: $('latitude').value !== '' && $('longitude').value !== ''
                    ? { latitude: Number($('latitude').value), longitude: Number($('longitude').value) } : null,
                locationDeferred: $('latitude').value === '' || $('longitude').value === '',
            };
            if (validProfile(candidate)) { profile = candidate; persist(); }
        };
        ['name', 'phone'].forEach(id => $(id).addEventListener('change', saveOrderDetails));
        $('address').addEventListener('change', () => {
            $('latitude').value = ''; $('longitude').value = '';
            if ($('locationStatus')) {
                $('locationStatus').textContent = ui.locationDeferred;
                $('locationStatus').classList.remove('ready');
            }
            saveOrderDetails();
        });
        document.addEventListener('restaurant:location-selected', saveOrderDetails);
        try {
            const stored = JSON.parse(localStorage.getItem(key) || 'null');
            if (validProfile(stored)) profile = stored;
        } catch (_) { /* A damaged or unavailable store should still show the welcome form. */ }
        if (profile) applyToOrder(); else open();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
