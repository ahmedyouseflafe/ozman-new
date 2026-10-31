(() => {
    const section = document.querySelector('#outlets');
    if (!section) return;
    const list = section.querySelector('.network-grid');
    const cards = [...list.querySelectorAll('[data-outlet]')];
    const status = section.querySelector('[data-location-status]');
    let busy = false;
    const coordinate = (value, limit) => value?.trim() && Number.isFinite(Number(value)) && Math.abs(Number(value)) <= limit ? Number(value) : null;
    const distance = (origin, card) => {
        const lat = coordinate(card.dataset.lat, 90), lon = coordinate(card.dataset.lon, 180);
        if (lat === null || lon === null) return Infinity;
        const rad = x => x * Math.PI / 180;
        const a = Math.sin(rad(lat - origin.latitude) / 2) ** 2 + Math.cos(rad(origin.latitude)) * Math.cos(rad(lat)) * Math.sin(rad(lon - origin.longitude) / 2) ** 2;
        return 6371 * 2 * Math.asin(Math.sqrt(Math.min(1, Math.max(0, a))));
    };
    document.querySelectorAll('a[href="#outlets"], [data-locate-outlets]').forEach(button => button.addEventListener('click', () => {
        if (busy) return;
        if (!cards.length) { status.textContent = 'نقاط البيع ستُضاف قريبًا.'; return; }
        if (!navigator.geolocation) { status.textContent = 'تحديد الموقع غير متاح. يمكنك تصفح المحلات وفتح مواقعها مباشرة.'; return; }
        busy = true;
        status.textContent = 'جارٍ تحديد موقعك… اسمح بالوصول للموقع لعرض الأقرب.';
        let settled = false;
        const finish = () => { settled = true; busy = false; clearTimeout(timer); };
        const fail = error => {
            if (settled) return;
            finish();
            status.textContent = error?.code === 1 ? 'لم تسمح بالوصول للموقع. اسمح به من إعدادات المتصفح ثم أعد المحاولة، أو تصفح المحلات أدناه.' : 'تعذر تحديد موقعك. حاول مجددًا أو افتح موقع أي محل أدناه.';
        };
        const timer = setTimeout(() => fail(), 15000);
        navigator.geolocation.getCurrentPosition(position => {
            if (settled) return;
            finish();
            const ranked = cards.map((card, index) => ({card, index, km: distance(position.coords, card)})).sort((a,b) => (a.km - b.km) || a.index - b.index);
            ranked.forEach(({card, km}) => {
                card.querySelector('[data-distance]').textContent = Number.isFinite(km) ? (km < 1 ? Math.round(km * 1000) + ' متر' : km.toFixed(1) + ' كم') + ' تقريبًا' : 'المسافة غير محددة';
                list.appendChild(card);
            });
            status.textContent = ranked.some(item => Number.isFinite(item.km)) ? 'المحلات مرتّبة من الأقرب للأبعد. المسافة بخط مستقيم وليست مسافة الطريق.' : 'إحداثيات المحلات لم تُضف بعد؛ يمكنك فتح مواقعها حسب العنوان.';
        }, fail, {enableHighAccuracy:true, timeout:12000, maximumAge:60000});
    }));
})();
