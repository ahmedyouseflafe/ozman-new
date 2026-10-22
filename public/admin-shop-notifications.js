(() => {
    'use strict';
    if (window.ozmanActivityStarted) return;
    window.ozmanActivityStarted = true;
    const config = window.OzmanActivityConfig, $ = id => document.getElementById(id);
    if (!config || !$('ozActivityBell')) return;
    const el=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
    let latest=0, initialized=false, busy=false, stopped=false, sound=false, audio, toastTimer;
    const seenKey='ozman.analytics.seen.'+config.user;
    try {latest=Number(sessionStorage.getItem(seenKey)) || 0;initialized=latest>0;} catch {}
    function panel(open) {$('ozActivityPanel').hidden=!open;$('ozActivityToggle').setAttribute('aria-expanded',String(open));if(open){$('ozActivityToast').hidden=true;$('ozActivityClose').focus();}else $('ozActivityToggle').focus();}
    $('ozActivityToggle').onclick=()=>panel($('ozActivityPanel').hidden);
    $('ozActivityClose').onclick=()=>panel(false);
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!$('ozActivityPanel').hidden)panel(false);});
    document.addEventListener('click',e=>{if(!$('ozActivityBell').contains(e.target)&&!$('ozActivityPanel').hidden){$('ozActivityPanel').hidden=true;$('ozActivityToggle').setAttribute('aria-expanded','false');}});
    $('ozActivitySound').onclick=async()=>{
        try {const Audio=window.AudioContext||window.webkitAudioContext;if(!Audio)throw new Error();audio ||= new Audio();await audio.resume();sound=!sound;$('ozActivitySound').textContent=sound?'إيقاف الصوت':'تفعيل الصوت';$('ozActivitySound').setAttribute('aria-pressed',String(sound));}
        catch {$('ozActivityStatus').textContent='الصوت غير متاح في هذا المتصفح.';}
    };
    function ping(){if(!sound||!audio)return;const o=audio.createOscillator(),gain=audio.createGain();o.connect(gain);gain.connect(audio.destination);o.frequency.value=720;gain.gain.setValueAtTime(.08,audio.currentTime);gain.gain.exponentialRampToValueAtTime(.001,audio.currentTime+.3);o.start();o.stop(audio.currentTime+.3);}
    async function poll(){
        if(busy||stopped||document.hidden)return;busy=true;
        try {
            const response=await fetch(config.base+'/notifications',{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store',signal:AbortSignal.timeout(12000)});
            if(response.status===401||response.status===403){stopped=true;throw new Error('انتهت جلسة الإدارة. سجّل الدخول مجددًا.');}
            if(!response.ok)throw new Error('تعذّر تحديث الإشعارات؛ سنحاول تلقائيًا.');
            const data=await response.json();
            $('ozActivityCount').textContent=data.unread>99?'99+':String(data.unread);
            $('ozActivityStatus').textContent=data.ready?'الإشعارات تشمل كل المحلات، بصرف النظر عن فلتر التقرير.':'يلزم تشغيل تحديث قاعدة البيانات.';
            $('ozActivityStatus').classList.remove('oz-activity-error');
            if(initialized&&data.latest>latest){
                const fresh=data.events.filter(e=>e.id>latest), sale=fresh.find(e=>e.kind==='sale_confirmed'), event=sale||fresh[0];
                if(event){$('ozActivityToast').textContent=event.title+' — '+event.shop;$('ozActivityToast').hidden=!$('ozActivityPanel').hidden;clearTimeout(toastTimer);toastTimer=setTimeout(()=>$('ozActivityToast').hidden=true,6500);ping();}
                document.dispatchEvent(new CustomEvent('ozman:shop-event'));
            }
            latest=data.latest;initialized=true;try{sessionStorage.setItem(seenKey,String(latest));}catch{}
            const list=$('ozActivityItems');list.replaceChildren();
            for(const event of data.events){
                const row=el('div','');row.className='oz-activity-item'+(event.id>data.read_through?' unread':'');
                row.append(el('strong',event.title),el('p',event.shop+(event.data?.total!=null?' • '+Number(event.data.total).toFixed(2)+' ₪':'')),el('p',new Date(event.created_at).toLocaleString('ar')));
                if(event.order_id){const button=el('a','تفاصيل الطلب');button.href=config.base+'?order='+event.order_id;button.onclick=e=>{if(window.OzmanAnalyticsOpenOrder){e.preventDefault();panel(false);window.OzmanAnalyticsOpenOrder(event.order_id);}};row.append(button);}
                list.append(row);
            }
            if(!data.events.length)list.append(el('p','لا توجد إشعارات جديدة بعد.'));
        } catch(e){$('ozActivityStatus').textContent=e.message;$('ozActivityStatus').classList.add('oz-activity-error');}
        finally{busy=false;}
    }
    $('ozActivityRead').onclick=async()=>{
        const button=$('ozActivityRead');button.disabled=true;
        try {const response=await fetch(config.base+'/read',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':config.csrf},credentials:'same-origin',body:JSON.stringify({through:latest}),signal:AbortSignal.timeout(12000)});if(!response.ok)throw new Error('تعذّر حفظ القراءة. حدّث الصفحة وحاول مجددًا.');await poll();}
        catch(e){$('ozActivityStatus').textContent=e.message;}finally{button.disabled=false;}
    };
    poll();setInterval(poll,15000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll();});
})();
