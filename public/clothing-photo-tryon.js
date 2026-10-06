export function initPhotoTryOn(root = document) {
    const $ = id => root.getElementById(id), layer = $('tryOnLayer');
    if (!layer) return;
    const video=$('photoTryVideo'), capture=$('photoTryCapture'), countdown=$('photoTryCountdown');
    let stream=null, timer=null, generation=0, shot=null, shotUrl=null, productId=null, busy=false, opener=null, aborter=null;
    const status=text=>$('photoTryStatus').textContent=text;
    const stopStream=()=>{stream?.getTracks().forEach(t=>t.stop());stream=null;video.srcObject=null;};
    const cancelTimer=()=>{clearInterval(timer);timer=null;countdown.hidden=true;$('photoTryCancel').hidden=true;$('photoTryShoot').hidden=!stream;};
    const clearShot=()=>{shot=null;if(shotUrl)URL.revokeObjectURL(shotUrl);shotUrl=null;capture.removeAttribute('src');capture.hidden=true;};
    const sync=()=>{$('photoTryGenerate').disabled=busy||!shot||layer.dataset.enabled!=='1'||!$('photoTryConsent')?.checked;};
    const reset=()=>{generation++;aborter?.abort();aborter=null;cancelTimer();stopStream();clearShot();busy=false;
        $('photoTryShoot').hidden=true;$('photoTryRetake').hidden=true;$('photoTryCamera').hidden=false;
        $('photoTryResult').hidden=true;$('photoTryResultImage').removeAttribute('src');$('photoTryStage').hidden=true;
        $('photoTryCamera').disabled=false;$('photoTryRetake').disabled=false;
        if($('photoTryConsent'))$('photoTryConsent').checked=false;sync();};
    const close=()=>{reset();layer.classList.remove('open');opener?.focus();};
    const open=(url,id,button)=>{reset();opener=button;productId=id||null;$('photoTryGarment').src=url;
        $('photoTryExample').open=true;layer.classList.add('open');status('افتح الكاميرا، ثم اضغط التقاط بعد 5 ثوانٍ.');$('photoTryCamera').focus();};
    const startCamera=async()=>{
        reset();const run=generation;$('photoTryCamera').disabled=true;status('اسمح باستخدام الكاميرا…');
        try {
            if(!navigator.mediaDevices?.getUserMedia)throw new Error('الكاميرا تحتاج اتصال HTTPS ومتصفحًا يدعمها.');
            const next=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:960},height:{ideal:1280}},audio:false});
            if(run!==generation){next.getTracks().forEach(t=>t.stop());return;}
            stream=next;video.srcObject=next;video.hidden=false;$('photoTryStage').hidden=false;await video.play();
            if(run!==generation)return;
            $('photoTryCamera').hidden=true;$('photoTryShoot').hidden=false;$('photoTryExample').open=false;
            status('لما تكون جاهز اضغط التقاط. عندك 5 ثوانٍ لتوقف من الأمام وتبعد إيديك شوي.');
            next.getVideoTracks()[0].addEventListener('ended',()=>{if(run===generation&&stream){cancelTimer();stopStream();$('photoTryShoot').hidden=true;$('photoTryCamera').hidden=false;$('photoTryCamera').disabled=false;status('انقطع اتصال الكاميرا. افتحها مجددًا.');}});
        }catch(error){if(run!==generation)return;stopStream();$('photoTryCamera').disabled=false;status(error.name==='NotAllowedError'?'اسمح للكاميرا من إعدادات المتصفح وحاول مجددًا.':error.name==='NotFoundError'?'لا توجد كاميرا متصلة.':error.message||'تعذّر فتح الكاميرا.');}
    };
    $('photoTryCamera').onclick=startCamera;$('photoTryRetake').onclick=startCamera;
    $('photoTryCancel').onclick=()=>{cancelTimer();status('تم إلغاء العدّ. اضغط التقاط لما تكون جاهز.');};
    $('photoTryShoot').onclick=()=>{
        if(timer||!stream||video.readyState<2)return;
        const run=generation,deadline=performance.now()+5000;
        countdown.hidden=false;countdown.textContent='5';$('photoTryShoot').hidden=true;$('photoTryCancel').hidden=false;
        status('قف من الأمام، وخلي الرأس والكتفين والورك داخل الصورة.');
        timer=setInterval(()=>{
            const remaining=Math.ceil((deadline-performance.now())/1000);
            if(remaining>0){countdown.textContent=String(remaining);return;}
            cancelTimer();$('photoTryShoot').hidden=true;
            if(run!==generation||!video.videoWidth||!video.videoHeight)return;
            const canvas=root.createElement('canvas'),scale=Math.min(1,1280/Math.max(video.videoWidth,video.videoHeight));
            canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);
            canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
            canvas.toBlob(blob=>{
                if(run!==generation)return;
                if(!blob){status('تعذّر التقاط الصورة. أعد المحاولة.');$('photoTryShoot').hidden=false;return;}
                shot=blob;shotUrl=URL.createObjectURL(blob);capture.src=shotUrl;capture.hidden=false;video.hidden=true;stopStream();
                $('photoTryRetake').hidden=false;status('تم التقاط الصورة. راجعها قبل التجربة، أو أعد التصوير.');sync();
            },'image/jpeg',.92);
        },100);
    };
    $('photoTryConsent')?.addEventListener('change',sync);
    const json=async response=>{const data=await response.json();if(!response.ok)throw new Error(response.status===429?'وصلت حد التجارب مؤقتًا. حاول لاحقًا.':data.message||'تعذّرت معالجة الصورة.');return data;};
    $('photoTryGenerate').onclick=async()=>{
        if($('photoTryGenerate').disabled)return;
        busy=true;sync();$('photoTryRetake').disabled=true;const run=generation;aborter=new AbortController();
        const requestController=aborter;
        const timeout=setTimeout(()=>{if(run===generation){status('انتهت مهلة المعالجة. حاول لاحقًا.');requestController.abort();}},150000);
        const signal=aborter.signal,body=new FormData();body.append('photo',shot,'capture.jpg');body.append('consent','1');if(productId)body.append('product_id',productId);
        status('جاري تجهيز صورتك بالقطعة… قد تستغرق المعالجة بعض الوقت.');
        try {
            const job=await json(await fetch(layer.dataset.endpoint,{method:'POST',body,signal,headers:{'Accept':'application/json','X-CSRF-TOKEN':layer.dataset.csrf}}));
            const deadline=Date.now()+120000;
            while(run===generation&&Date.now()<deadline){
                await new Promise(r=>setTimeout(r,2200));if(run!==generation)return;
                const result=await json(await fetch(layer.dataset.endpoint+'/'+encodeURIComponent(job.job),{signal,headers:{Accept:'application/json'},cache:'no-store'}));
                if(result.status==='completed'){
                    $('photoTryResultImage').src=result.image;$('photoTryResult').hidden=false;$('photoTryResult').scrollIntoView({block:'nearest',behavior:'smooth'});
                    status('هاي نتيجتك. المعاينة مولّدة وقد تختلف تفاصيل القطعة أو المقاس.');return;
                }
            }
            throw new Error('المعالجة أخذت وقتًا طويلًا. حاول لاحقًا.');
        }catch(error){if(run===generation&&error.name!=='AbortError')status(error.message||'تعذّر الاتصال بخدمة الصور.');}
        finally{clearTimeout(timeout);if(run===generation){busy=false;$('photoTryRetake').disabled=false;sync();}}
    };
    $('demoTryOn')?.addEventListener('click',event=>open(event.currentTarget.dataset.image,null,event.currentTarget));
    $('tryOn')?.addEventListener('click',event=>open($('quickImage').src,$('quickImage').dataset.productId,event.currentTarget));
    root.querySelectorAll('[data-close="tryOnLayer"]').forEach(button=>button.addEventListener('click',close));
    layer.addEventListener('click',event=>{if(event.target===layer)close();});
    root.addEventListener('keydown',event=>{if(!layer.classList.contains('open'))return;if(event.key==='Escape')close();
        if(event.key==='Tab'){const focusable=[...layer.querySelectorAll('button,input,summary')].filter(el=>!el.disabled&&el.getClientRects().length);const first=focusable[0],last=focusable.at(-1);if(event.shiftKey&&root.activeElement===first){event.preventDefault();last?.focus();}else if(!event.shiftKey&&root.activeElement===last){event.preventDefault();first?.focus();}}});
    window.addEventListener('pagehide',close);root.addEventListener('visibilitychange',()=>{if(root.hidden&&layer.classList.contains('open'))close();});
}
if(typeof document!=='undefined')initPhotoTryOn();
