(() => {
    'use strict';
    const cars = window.carMarketExamples || [];
    const $ = id => document.getElementById(id);
    const cards = new Map([...document.querySelectorAll('[data-car]')].map(card => [Number(card.dataset.car), card]));
    const state = {condition:'', body:'', savedOnly:false};
    const favoriteKey = 'ozman.cars.design-preview.favorites';
    let saved = new Set();
    try { const value=JSON.parse(localStorage.getItem(favoriteKey)||'[]'); if(Array.isArray(value)) saved=new Set(value.filter(id=>cars.some(car=>car.id===id))); } catch {}
    let toastTimer, uploadUrl, dialogScroll;
    const format = value => Number(value).toLocaleString('en-US');
    const normalize = value => String(value).toLowerCase().normalize('NFKC').replace(/[أإآ]/g,'ا').trim();
    function toast(message) {
        const node=$('marketToast');node.textContent=message;node.hidden=false;
        // A toast inside an open native dialog must also be in the top layer.
        (document.querySelector('dialog[open]')||document.body).append(node);
        clearTimeout(toastTimer);toastTimer=setTimeout(()=>{node.hidden=true;document.body.append(node);},4500);
    }
    function syncFavorites() {
        document.querySelectorAll('[data-save]').forEach(button=>{
            const active=saved.has(Number(button.dataset.save));
            button.setAttribute('aria-pressed',String(active));
            const car=cars.find(car=>car.id===Number(button.dataset.save));
            button.setAttribute('aria-label',`${active?'إزالة':'حفظ'} ${car.name} ${active?'من':'في'} المفضلة`);
        });
        $('savedCount').textContent=saved.size;
        $('savedToggle').setAttribute('aria-pressed',String(state.savedOnly));
    }
    function applyFilters(scroll=false) {
        const query=normalize($('searchText').value), make=$('makeFilter').value, city=$('cityFilter').value, price=Number($('priceFilter').value);
        const filtered=cars.filter(car=>(!state.condition||car.condition===state.condition)&&(!state.body||car.body===state.body)&&(!make||car.make===make)&&(!city||car.city===city)&&(!price||car.price<=price)&&(!state.savedOnly||saved.has(car.id))&&(!query||normalize(`${car.name} ${car.make} ${car.model} ${car.year} ${car.city}`).includes(query)));
        const sort=$('sortCars').value;
        filtered.sort((a,b)=>sort==='priceAsc'?a.price-b.price:sort==='priceDesc'?b.price-a.price:sort==='newest'?b.year-a.year:Number(b.featured)-Number(a.featured)||a.id-b.id);
        const ids=new Set(filtered.map(car=>car.id));
        cards.forEach((card,id)=>card.hidden=!ids.has(id));
        filtered.forEach(car=>$('carGrid').append(cards.get(car.id)));
        $('emptyResults').hidden=filtered.length>0;
        $('resultCount').textContent=`${filtered.length} ${state.savedOnly?'سيارات محفوظة':'سيارات في معاينة التصميم'}`;
        $('resetFilters').hidden=!(query||make||city||price||state.condition||state.body||state.savedOnly);
        document.querySelectorAll('[data-condition]').forEach(button=>{const active=button.dataset.condition===state.condition;button.classList.toggle('selected',active);button.setAttribute('aria-pressed',String(active));});
        document.querySelectorAll('[data-body]').forEach(button=>{const active=button.dataset.body===state.body;button.classList.toggle('selected',active);button.setAttribute('aria-pressed',String(active));});
        syncFavorites();
        if(scroll)$('inventory').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
    }
    function resetFilters(){Object.assign(state,{condition:'',body:'',savedOnly:false});$('carSearch').reset();$('sortCars').value='featured';applyFilters();}
    $('carSearch').addEventListener('submit',event=>{event.preventDefault();applyFilters(true);});
    $('searchText').addEventListener('input',()=>applyFilters());
    ['makeFilter','priceFilter','cityFilter','sortCars'].forEach(id=>$(id).addEventListener('change',()=>applyFilters()));
    document.querySelectorAll('[data-condition]').forEach(button=>button.addEventListener('click',()=>{state.condition=button.dataset.condition;applyFilters();}));
    document.querySelectorAll('[data-condition-link]').forEach(button=>button.addEventListener('click',()=>{state.condition=button.dataset.conditionLink;applyFilters(true);}));
    document.querySelectorAll('[data-body]').forEach(button=>button.addEventListener('click',()=>{state.body=button.dataset.body;applyFilters();}));
    document.querySelectorAll('[data-save]').forEach(button=>button.addEventListener('click',()=>{const id=Number(button.dataset.save);saved.has(id)?saved.delete(id):saved.add(id);try{localStorage.setItem(favoriteKey,JSON.stringify([...saved]));}catch{}applyFilters();}));
    ['savedToggle','savedMobile'].forEach(id=>$(id).addEventListener('click',()=>{state.savedOnly=!state.savedOnly;applyFilters(true);}));
    ['resetFilters','emptyReset'].forEach(id=>$(id).addEventListener('click',resetFilters));
    function openDialog(id){
        const dialog=$(id);if(dialog.open)return;
        dialogScroll={x:scrollX,y:scrollY,position:document.body.style.position,top:document.body.style.top,width:document.body.style.width};
        document.body.style.position='fixed';document.body.style.top=`-${dialogScroll.y}px`;document.body.style.width='100%';
        dialog.showModal();dialog.scrollTop=0;dialog.querySelector('[data-close]').focus({preventScroll:true});
    }
    document.querySelectorAll('[data-dialog]').forEach(button=>button.addEventListener('click',()=>openDialog(button.dataset.dialog)));
    document.querySelectorAll('dialog').forEach(dialog=>{
        dialog.querySelector('[data-close]').addEventListener('click',()=>dialog.close());
        dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();});
        dialog.addEventListener('close',()=>{
            $('marketToast').hidden=true;document.body.append($('marketToast'));
            if(dialogScroll){const old=dialogScroll;dialogScroll=null;Object.assign(document.body.style,{position:old.position,top:old.top,width:old.width});window.scrollTo({left:old.x,top:old.y,behavior:'instant'});}
            if(dialog.id==='accountDialog')$('accountPreview').reset();
            if(dialog.id==='sellDialog'){$('sellPreview').reset();if(uploadUrl)URL.revokeObjectURL(uploadUrl);uploadUrl=null;$('uploadThumbnail').hidden=true;$('uploadThumbnail').removeAttribute('src');}
        });
    });
    document.querySelectorAll('[data-details]').forEach(button=>button.addEventListener('click',()=>{
        const car=cars.find(car=>car.id===Number(button.dataset.details));if(!car)return;
        $('detailTitle').textContent=car.name;
        $('detailImage').src=`${window.carMarketMediaBase}/${car.image}`;$('detailImage').alt=`صورة توضيحية: ${car.name}`;
        $('detailLocation').textContent=`${car.city} · ${car.condition==='new'?'جديدة':'مستعملة'} · إعلان توضيحي`;
        $('detailPrice').textContent=`${format(car.price)} ₪`;
        $('detailSpecs').replaceChildren(...[['سنة الصنع',car.year],['المسافة',`${format(car.km)} كم`],['ناقل الحركة',car.gear],['الوقود',car.fuel],['اللون',car.color],['المدينة',car.city]].map(([title,value])=>{const item=document.createElement('div'),label=document.createElement('small'),text=document.createElement('b');label.textContent=title;text.textContent=value;item.append(label,text);return item;}));
        openDialog('detailsDialog');
    }));
    document.querySelector('[data-preview-contact]').addEventListener('click',()=>toast('هذه معاينة تصميم؛ بيانات التواصل تُضاف مع الإعلانات الحقيقية.'));
    document.querySelectorAll('[data-account-tab]').forEach(button=>button.addEventListener('click',()=>{
        const register=button.dataset.accountTab==='register';
        document.querySelectorAll('[data-account-tab]').forEach(tab=>{const selected=tab===button;tab.classList.toggle('selected',selected);tab.setAttribute('aria-pressed',String(selected));});
        $('registerName').hidden=!register;$('registerName').querySelector('input').required=register;
        $('accountSubmit').textContent=register?'معاينة إنشاء الحساب':'معاينة الدخول';
    }));
    $('accountPreview').addEventListener('submit',event=>{event.preventDefault();event.currentTarget.reset();toast('النموذج للمعاينة فقط. لم يُنشأ حساب ولم تُرسل أي بيانات.');});
    $('sellPreview').addEventListener('submit',event=>{event.preventDefault();toast('هيك رح تبدأ إضافة الإعلان. الحفظ والنشر بنربطهم بعد اعتماد التصميم.');});
    $('previewPhoto').addEventListener('change',event=>{
        if(uploadUrl)URL.revokeObjectURL(uploadUrl);uploadUrl=null;$('uploadThumbnail').hidden=true;$('uploadThumbnail').removeAttribute('src');
        const file=event.target.files[0];if(!file)return;
        if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>8*1024*1024){event.target.value='';toast('اختار صورة JPG أو PNG أو WebP، بحجم حتى 8 ميجابايت.');return;}
        uploadUrl=URL.createObjectURL(file);$('uploadThumbnail').src=uploadUrl;$('uploadThumbnail').hidden=false;
    });
    applyFilters();
})();
