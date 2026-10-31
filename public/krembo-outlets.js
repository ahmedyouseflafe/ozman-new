(() => {
    const section = document.querySelector('#outlets');
    if (!section) return;
    const list = section.querySelector('.network-grid');
    const cards = [...list.querySelectorAll('[data-outlet]')];
    const status = section.querySelector('[data-location-status]');
    let busy = false;
    const dialog = document.createElement('dialog');
    dialog.className = 'outlet-dialog';
    dialog.setAttribute('aria-label', 'نقاط بيع خدود الجدع');
    dialog.innerHTML = '<header><h2>نقاط بيع خدود الجدع</h2><button type="button" data-close aria-label="إغلاق">×</button></header><button type="button" data-retry>تحديد موقعي وترتيب الأقرب</button><p data-map-message role="status"></p><div class="outlet-dialog-grid"><div data-outlet-list></div><div data-outlet-map aria-label="خريطة نقاط البيع"></div></div>';
    document.body.appendChild(dialog);
    let map, loading, previousOverflow, trigger, userPosition, userMarker;
    const locateButton = dialog.querySelector('[data-retry]');
    const showUser = () => {
        if (!map || !userPosition) return;
        const point = [userPosition.latitude, userPosition.longitude];
        if (userMarker) userMarker.setLatLng(point);
        else userMarker = L.circleMarker(point, {radius:9,color:'#fff',weight:3,fillColor:'#1686e8',fillOpacity:1}).addTo(map).bindPopup('موقعك الحالي');
        map.setView(point,13);
        userMarker.openPopup();
    };
    const mapMessage = dialog.querySelector('[data-map-message]');
    const modalList = dialog.querySelector('[data-outlet-list]');
    const refreshList = () => { modalList.replaceChildren(...[...list.children].map(card => card.cloneNode(true))); };
    new MutationObserver(() => { refreshList(); mapMessage.textContent = status.textContent; }).observe(section, {childList:true, subtree:true, characterData:true});
    dialog.querySelector('[data-close]').onclick = () => dialog.close();
    dialog.addEventListener('close', () => { document.body.style.overflow = previousOverflow; trigger?.focus(); });
    const loadMap = async () => {
        if (!loading) loading = new Promise((resolve, reject) => {
            if (window.L) return resolve();
            const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'; document.head.appendChild(css);
            const script = document.createElement('script'); script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            const timer = setTimeout(() => reject(new Error('map-timeout')), 12000);
            script.onload = () => { clearTimeout(timer); resolve(); }; script.onerror = () => { clearTimeout(timer); reject(new Error('map-load')); }; document.head.appendChild(script);
        }).catch(error => { loading = null; throw error; });
        await loading;
        if (!dialog.open) return;
        if (!map) {
            map = L.map(dialog.querySelector('[data-outlet-map]')).setView([32,35],8);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:19}).addTo(map);
            const bounds = [];
            cards.forEach(card => {
                const lat = coordinate(card.dataset.lat,90), lon = coordinate(card.dataset.lon,180);
                if (lat === null || lon === null) return;
                const popup = document.createElement('div');
                const name = document.createElement('strong'); name.textContent = card.querySelector('h3').textContent; popup.appendChild(name);
                const link = card.querySelector('a'); if (link) popup.appendChild(link.cloneNode(true));
                const marker = L.marker([lat,lon]).addTo(map).bindPopup(popup); bounds.push([lat,lon]);
                card.dataset.markerIndex = String(bounds.length - 1);
                card._marker = marker;
            });
            if (bounds.length) map.fitBounds(bounds,{padding:[35,35],maxZoom:14});
        }
        requestAnimationFrame(() => { map.invalidateSize(); showUser(); });
    };
    modalList.addEventListener('click', event => {
        const card = event.target.closest('[data-outlet]');
        if (!card || event.target.closest('a') || !map) return;
        const original = cards.find(item => item.dataset.lat === card.dataset.lat && item.dataset.lon === card.dataset.lon);
        if (original?._marker) { map.setView(original._marker.getLatLng(),15); original._marker.openPopup(); }
    });
    const coordinate = (value, limit) => value?.trim() && Number.isFinite(Number(value)) && Math.abs(Number(value)) <= limit ? Number(value) : null;
    const distance = (origin, card) => {
        const lat = coordinate(card.dataset.lat, 90), lon = coordinate(card.dataset.lon, 180);
        if (lat === null || lon === null) return Infinity;
        const rad = x => x * Math.PI / 180;
        const a = Math.sin(rad(lat - origin.latitude) / 2) ** 2 + Math.cos(rad(origin.latitude)) * Math.cos(rad(lat)) * Math.sin(rad(lon - origin.longitude) / 2) ** 2;
        return 6371 * 2 * Math.asin(Math.sqrt(Math.min(1, Math.max(0, a))));
    };
    document.querySelectorAll('a[href="#outlets"], [data-locate-outlets], [data-retry]').forEach(button => button.addEventListener('click', event => {
        event.preventDefault();
        if (!dialog.open) { trigger = button; previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; dialog.showModal(); refreshList(); }
        loadMap().catch(() => { mapMessage.textContent = 'تعذر تحميل الخريطة. يمكنك فتح لوكيشن المحل من القائمة أو إعادة المحاولة.'; });
        if (busy) return;
        if (!navigator.geolocation) { status.textContent = 'تحديد الموقع غير متاح. يمكنك تصفح المحلات وفتح مواقعها مباشرة.'; return; }
        busy = true;
        locateButton.disabled = true;
        locateButton.textContent = 'جارٍ تحديد موقعك…';
        status.textContent = 'جارٍ تحديد موقعك… اسمح بالوصول للموقع لعرض الأقرب.';
        let settled = false;
        const finish = () => { settled = true; busy = false; clearTimeout(timer); locateButton.disabled = false; locateButton.textContent = 'تحديد موقعي وترتيب الأقرب'; };
        const fail = error => {
            if (settled) return;
            finish();
            status.textContent = error?.code === 1 ? 'لم تسمح بالوصول للموقع. اسمح به من إعدادات المتصفح ثم أعد المحاولة، أو تصفح المحلات أدناه.' : 'تعذر تحديد موقعك. حاول مجددًا أو افتح موقع أي محل أدناه.';
        };
        const timer = setTimeout(() => fail(), 15000);
        navigator.geolocation.getCurrentPosition(position => {
            if (settled) return;
            finish();
            userPosition = position.coords;
            showUser();
            const ranked = cards.map((card, index) => ({card, index, km: distance(position.coords, card)})).sort((a,b) => (a.km - b.km) || a.index - b.index);
            ranked.forEach(({card, km}) => {
                card.querySelector('[data-distance]').textContent = Number.isFinite(km) ? (km < 1 ? Math.round(km * 1000) + ' متر' : km.toFixed(1) + ' كم') + ' تقريبًا' : 'المسافة غير محددة';
                list.appendChild(card);
            });
            status.textContent = 'تم تحديد موقعك. ' + (ranked.some(item => Number.isFinite(item.km)) ? 'المحلات مرتّبة من الأقرب للأبعد. المسافة بخط مستقيم وليست مسافة الطريق.' : 'لم تُضف نقاط بيع بإحداثيات بعد.');
        }, fail, {enableHighAccuracy:true, timeout:12000, maximumAge:60000});
    }));
})();
