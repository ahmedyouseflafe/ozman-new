(() => {
    const config=window.OZMAN_COMMUNITY_PUSH, push=window.OzmanWebPush;
    if(!config || !push) return;
    const enable=document.getElementById('communityPushEnable'), disable=document.getElementById('communityPushDisable'), status=document.getElementById('communityPushStatus');
    let current=null, busy=false;
    const ui=enabled=>{enable.hidden=enabled;disable.hidden=!enabled;};
    async function activate(ask) {
        if(busy)return; busy=true;enable.disabled=true;status.textContent='جارٍ تجهيز إشعاراتك…';
        try {
            if(!('Notification' in window) || !('PushManager' in window)) throw new Error('هذا المتصفح لا يدعم الإشعارات. على آيفون افتح الموقع من أيقونته بعد إضافته للشاشة الرئيسية.');
            // Permission is requested directly from the click, before any network await.
            const permission=Notification.permission==='granted'?'granted':ask?await Promise.race([Notification.requestPermission(),new Promise((_,reject)=>setTimeout(()=>reject(new Error('طلب الإذن تأخر. جرّب مرة ثانية.')),20000))]):Notification.permission;
            if(permission!=='granted') throw new Error('اسمح بالإشعارات من إعدادات الموقع، ثم جرّب مرة ثانية.');
            const response=await fetch(config.keyUrl,{headers:{Accept:'application/json'},signal:AbortSignal.timeout(15000)});
            if(!response.ok) throw new Error('تعذّر تجهيز الإشعارات. حاول لاحقًا.');
            const key=(await response.json()).public_key;
            const registration=await push.register('/merchant-pwa-sw.js');
            current=await push.subscription(registration,key);
            await push.save(config.storeUrl,current);ui(true);status.textContent='مفعّلة على هذا الجهاز لجديد الحسابات اللي بتتابعها.';
        }catch(error){status.textContent=error.code?push.message(error):error.message;ui(false);}
        finally{busy=false;enable.disabled=false;}
    }
    enable.addEventListener('click',()=>activate(true));
    disable.addEventListener('click',async()=>{
        if(busy || !current)return;busy=true;disable.disabled=true;
        try{await push.request(config.destroyUrl,{method:'DELETE',data:{endpoint:current.endpoint}});ui(false);status.textContent='تم إيقاف إشعارات المتابعة على هذا الجهاز.';}
        catch(error){status.textContent=push.message(error);}
        finally{busy=false;disable.disabled=false;}
    });
    if(config.enabled && window.Notification?.permission==='granted') activate(false);
})();
