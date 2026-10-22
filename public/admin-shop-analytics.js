(() => {
    'use strict';
    const config = window.OzmanAnalyticsConfig;
    if (!config) return;
    const $ = id => document.getElementById(id), form = $('analyticsFilters');
    const num = v => new Intl.NumberFormat('en-US', {maximumFractionDigits: 0}).format(Number(v) || 0);
    const money = v => new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(v) || 0) + ' ₪';
    const date = v => v ? new Intl.DateTimeFormat('ar', {dateStyle: 'short', timeStyle: 'short'}).format(new Date(v)) : '—';
    const el = (tag, text, cls) => { const n = document.createElement(tag); if (text != null) n.textContent = text; if (cls) n.className = cls; return n; };
    const empty = (target, text = 'لا توجد بيانات خلال الفترة المحددة.') => target.replaceChildren(el('div', text, 'empty'));
    let report, cursor, generation = 0, activityGeneration = 0, refreshing = false, detailGeneration = 0, historyExpanded = false;
    const params = () => { const p = new URLSearchParams(new FormData(form)); if (!p.get('shop_id')) p.delete('shop_id'); return p; };
    const query = new URLSearchParams(location.search);
    for (const key of ['shop_id', 'from', 'to']) if (query.has(key)) form.elements[key].value = query.get(key);
    async function get(path, search = '') {
        const response = await fetch(config.base + path + (search ? '?' + search : ''), {credentials: 'same-origin', headers: {Accept: 'application/json'}, cache: 'no-store', signal: AbortSignal.timeout(25000)});
        if (response.status === 401 || response.status === 403) throw new Error('انتهت الجلسة أو لا توجد صلاحية. سجّل الدخول بحساب الإدارة الرئيسية.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'تعذّر تحميل البيانات. حاول مرة أخرى.');
        return data;
    }
    function metric(label, value, hint, icon) {
        const node = el('article', null, 'metric'), title = el('div', null, 'metric-label');
        title.append(el('span', label), el('span', icon, 'metric-icon'));
        node.append(title, el('div', value, 'metric-value'), el('small', hint)); return node;
    }
    function render(data) {
        report = data; const s = data.summary;
        $('metrics').replaceChildren(
            metric('قيمة المبيعات المؤكدة', money(s.revenue), 'بعد الخصومات • حسب تاريخ إتمام البيع', '↗'),
            metric('عمليات البيع', num(s.sales), num(s.units) + ' وحدة مباعة', '✓'),
            metric('الطلبات الجديدة', num(s.orders), num(s.pending) + ' غير مكتمل • ' + num(s.cancelled) + ' ملغي', '▤'),
            metric('متوسط قيمة البيع', money(s.average_order), 'لكل طلب مكتمل', '≈'),
            metric('مشاهدات المحلات', num(s.views), num(s.visitors) + ' متصفح فريد تقديري', '◉'),
            metric('تسجيلات العملاء', num(s.registrations), 'سجلات بيانات العملاء الجديدة', '+'),
            metric('دخول أصحاب المحلات', num(s.owner_logins), 'جلسات الدخول المسجلة خلال الفترة', '↪'),
            metric('المحلات', num(s.shops), num(s.active_shops) + ' فعال حاليًا', '▦')
        );
        renderShops(); renderChart(data.chart);
        $('comparison').textContent = 'الفترة السابقة المماثلة: ' + money(s.previous_revenue) + (s.revenue_change === null ? ' • لا تتوفر قاعدة للمقارنة بالنسبة المئوية.' : ' • التغيّر ' + (s.revenue_change > 0 ? '+' : '') + s.revenue_change + '%');
        $('orderStatuses').replaceChildren();
        for (const item of data.statuses) {
            const row = el('div', null, 'status-row'), title = el('div'), bar = el('div', null, 'bar'), fill = el('i');
            title.append(el('span', item.name), el('b', num(item.count))); fill.style.width = (s.orders ? item.count / s.orders * 100 : 0) + '%'; bar.append(fill); row.append(title, bar); $('orderStatuses').append(row);
        }
        if (!data.statuses.length) empty($('orderStatuses'));
        $('topProducts').replaceChildren();
        data.top_products.forEach((p, i) => { const row = el('div', null, 'rank-item'), name = el('div', p.name, 'rank-name'); name.append(el('small', p.shop)); row.append(el('span', i + 1, 'rank-number'), name, el('b', num(p.units) + ' وحدة')); $('topProducts').append(row); });
        if (!data.top_products.length) empty($('topProducts'), 'ستظهر المنتجات هنا بعد تأكيد أول عملية بيع.');
        $('customerStats').replaceChildren();
        for (const [label, value] of [['عملاء مشترون لأول مرة', num(s.new_buyers)], ['عملاء عائدون', num(s.returning_buyers)], ['إجمالي المبيعات قبل الخصم', money(s.gross)], ['خصومات الطلبات المكتملة', money(s.discounts)], ['المنتجات الحالية', num(s.products)], ['منتجات غير مفعلة', num(s.inactive_products)]]) {
            const row = el('div'); row.append(el('span', label), el('b', value)); $('customerStats').append(row);
        }
        $('ordersBody').replaceChildren();
        for (const order of data.orders) {
            const tr = el('tr'), cell = el('td'), button = el('button', order.number); button.type = 'button'; button.onclick = () => showOrder(order.id); cell.append(button); tr.append(cell);
            for (const text of [order.shop, order.customer, money(order.total), order.status, date(order.created_at)]) tr.append(el('td', text));
            $('ordersBody').append(tr);
        }
        if (!data.orders.length) tableEmpty($('ordersBody'), 6);
        $('trackingSince').textContent = 'بدأ تسجيل الزيارات والدخول وسجل النشاط بتاريخ ' + date(data.tracked_since.replace(' ', 'T')) + '. لا توجد بيانات لهذه المؤشرات قبل هذا التاريخ.';
        $('historyNotice').textContent = (s.estimated_sales_dates ? 'هناك ' + num(s.estimated_sales_dates) + ' عملية بيع قديمة بتاريخ إتمام تقديري مأخوذ من آخر تحديث للطلب. ' : '') + (s.unassigned_orders ? num(s.unassigned_orders) + ' طلب غير مربوط بمتجر، يظهر في الإجمالي فقط. ' : '');
    }
    function tableEmpty(target, columns) { const row = el('tr'), cell = el('td', 'لا توجد نتائج.', 'empty'); cell.colSpan = columns; row.append(cell); target.append(row); }
    function renderShops() {
        if (!report) return; const q = $('shopSearch').value.trim().toLowerCase(); $('shopsBody').replaceChildren();
        const rows = report.shops.filter(s => s.name.toLowerCase().includes(q));
        rows.forEach(shop => {
            const tr = el('tr'), cell = el('td'), box = el('div', null, 'shop-cell'), button = el('button', shop.name); button.type = 'button';
            button.append(el('small', shop.active ? (shop.accepting_orders ? 'فعال • يستقبل الطلبات' : 'فعال • الطلبات متوقفة') : 'غير فعال'));
            button.onclick = () => { form.elements.shop_id.value = shop.id; refresh(); $('overview').scrollIntoView(); };
            box.append(el('span', shop.name.charAt(0), 'shop-avatar'), button); cell.append(box); tr.append(cell);
            for (const key of ['views', 'visitors', 'registrations', 'logins', 'orders', 'sales', 'revenue']) tr.append(el('td', key === 'revenue' ? money(shop[key]) : num(shop[key])));
            $('shopsBody').append(tr);
        });
        if (!rows.length) tableEmpty($('shopsBody'), 8);
    }
    function renderChart(points) {
        const root = $('salesChart'); root.replaceChildren();
        const ns = 'http://www.w3.org/2000/svg', svg = document.createElementNS(ns, 'svg'); svg.setAttribute('viewBox', '0 0 800 200'); svg.setAttribute('role', 'img'); svg.setAttribute('aria-label', 'المبيعات اليومية بالشيكل');
        const make = (tag, attrs) => { const n = document.createElementNS(ns, tag); for (const [k,v] of Object.entries(attrs)) n.setAttribute(k,v); return n; };
        const max = Math.max(1, ...points.map(p => p.revenue));
        for (let i = 0; i < 4; i++) { const y = 15 + i * 55; svg.append(make('line', {x1:65, x2:785, y1:y, y2:y, stroke:'#e9eef5', 'stroke-dasharray':'4 5'})); const label = make('text', {x:0,y:y+4, fill:'#8b98aa','font-size':11}); label.textContent = num(max * (1 - i / 3)); svg.append(label); }
        const coords = points.map((p,i) => [65 + i / Math.max(1, points.length-1) * 720, 180 - p.revenue / max * 165]);
        if (coords.length) {
            svg.append(make('polygon', {points:[[65,180],...coords,[coords.at(-1)[0],180]].map(p=>p.join(',')).join(' '),fill:'#eef3ff'}));
            svg.append(make('polyline', {points:coords.map(p=>p.join(',')).join(' '),fill:'none',stroke:'#3269ec','stroke-width':3,'stroke-linejoin':'round'}));
            coords.forEach(([x,y],i) => { const dot=make('circle',{cx:x,cy:y,r:points.length>60?2:4,fill:'#3269ec'}), title=make('title',{}); title.textContent=points[i].day+': '+money(points[i].revenue)+' • '+points[i].sales+' مبيعات'; dot.append(title); svg.append(dot); });
        }
        root.append(svg); const labels=el('div',null,'chart-labels'); labels.append(el('span',points[0]?.day || ''),el('span',points.at(-1)?.day || '')); root.append(labels);
    }
    async function activity(append = false) {
        historyExpanded = append;
        const version = ++activityGeneration, p = params(); if ($('activityKind').value) p.set('kind',$('activityKind').value); if (append && cursor) p.set('before',cursor);
        $('moreActivity').disabled = true;
        try {
            const data = await get('/activity', p); if (version !== activityGeneration) return;
            if (!append) $('activityList').replaceChildren();
            for (const event of data.events) {
                const row=el('article',null,'activity-entry'), body=el('div'); body.append(el('h3',event.title),el('p',event.shop+' • '+(event.actor || 'عميل / النظام')));
                if (event.order_id) {const b=el('button','فتح تفاصيل الطلب'); b.type='button'; b.onclick=()=>showOrder(event.order_id); body.append(b);}
                if (event.data && Object.keys(event.data).length) {const details=el('details'); details.append(el('summary','التفاصيل المسجلة'),el('pre',JSON.stringify(event.data,null,2))); body.append(details);}
                row.append(el('span',event.kind==='sale_confirmed'?'✓':'•','activity-dot'),body,el('time',date(event.created_at))); $('activityList').append(row);
            }
            if (!append && !data.events.length) empty($('activityList'));
            cursor=data.next; $('moreActivity').hidden=!cursor;
        } catch(e) {if(version===activityGeneration) {$('analyticsError').textContent=e.message; $('analyticsError').hidden=false;}}
        finally {if(version===activityGeneration) $('moreActivity').disabled=false;}
    }
    async function refresh(background = false) {
        if (!config.ready || (background && refreshing)) return;
        const version=++generation; if(!background) ++activityGeneration; refreshing=true; $('syncStatus').textContent='جارٍ تحديث البيانات…';
        if (!background) history.replaceState(null,'',location.pathname+'?'+params());
        try {const data=await get('/data',params()); if(version!==generation)return; render(data); $('analyticsError').hidden=true; $('syncStatus').textContent='آخر تحديث '+new Date().toLocaleTimeString('ar'); if(!background || (!historyExpanded && !document.querySelector('#activityList details[open]'))) await activity();}
        catch(e) {if(version===generation){$('analyticsError').textContent=e.message; $('analyticsError').hidden=false; $('syncStatus').textContent='تعذّر التحديث • البيانات قديمة';}}
        finally {if(version===generation)refreshing=false;}
    }
    async function showOrder(id) {
        const version=++detailGeneration, dialog=$('orderDetail'); empty($('orderDetailBody'),'جارٍ تحميل الطلب…'); if(!dialog.open)dialog.showModal();
        try {const order=await get('/orders/'+encodeURIComponent(id)); if(version!==detailGeneration)return; const body=$('orderDetailBody'); body.replaceChildren();
            for (const [label,value] of [['رقم الطلب',order.number],['المتجر',order.shop],['العميل',order.customer],['الهاتف',order.phone],['واتساب',order.whatsapp],['العنوان',order.address],['ملاحظات',order.notes],['الحالة',order.status],['حالة الدفع المسجلة',order.payment],['تاريخ الطلب',date(order.created_at)]]) {
                const line=el('div',null,'order-detail-line'); line.append(el('span',label),el('b',value || '—')); body.append(line);
            }
            for (const item of order.items) {const line=el('div',null,'order-detail-line'); line.append(el('b',item.name+' × '+item.qty),el('span',money(Number(item.price)*item.qty)));body.append(line);}
            for(const [label,value] of [['قبل الخصم',order.subtotal],['الخصم',order.discount],['الإجمالي',order.total]]) {const line=el('div',null,'order-detail-line');line.append(el('span',label),el('b',money(value)));body.append(line);}
            const link=el('a','فتح إدارة الطلبات','button primary');link.href=order.manage_url;body.append(link);
        } catch(e) {if(version===detailGeneration)empty($('orderDetailBody'),e.message);}
    }
    window.OzmanAnalyticsOpenOrder = showOrder;
    $('closeOrder').onclick=()=>{$('orderDetail').close();++detailGeneration;};
    $('orderDetail').addEventListener('click',e=>{if(e.target===$('orderDetail')){$('orderDetail').close();++detailGeneration;}});
    form.addEventListener('submit',e=>{e.preventDefault();refresh();});
    $('shopSearch').addEventListener('input',renderShops);
    $('activityKind').onchange=()=>activity(); $('moreActivity').onclick=()=>activity(true);
    $('exportReport').onclick=()=>{if(config.ready)location.assign(config.base+'/export?'+params());};
    const isoLocal=d=>[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
    document.querySelectorAll('[data-days]').forEach(b=>b.onclick=()=>{const to=new Date(),from=new Date();from.setDate(from.getDate()-Number(b.dataset.days)+1);form.elements.from.value=isoLocal(from);form.elements.to.value=isoLocal(to);refresh();});
    refresh(); setInterval(()=>{if(!document.hidden&&!$('orderDetail').open)refresh(true);},60000);
    document.addEventListener('ozman:shop-event',()=>refresh(true));
    if (query.get('order')) showOrder(query.get('order'));
})();
