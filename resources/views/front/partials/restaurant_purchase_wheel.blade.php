@php
    $purchaseWheels = \App\Models\RewardWheel::where('shop_id', $shop->id)->where('wheel_type', 'purchase_amount')->where('is_active', true)
        ->whereHas('segments', fn($q) => $q->where('is_active', true), '>=', 2)
        ->with(['segments' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])->orderBy('min_order_total')->get();
    $previewSegments = $purchaseWheels->first()?->segments ?? collect();
    $previewGradient = $previewSegments->map(function($segment, $index) use ($previewSegments) {
        $color = preg_match('/^#[0-9a-f]{6}$/i', $segment->color ?? '') ? $segment->color : '#087f93';
        return $color.' '.($index * 360 / $previewSegments->count()).'deg '.(($index + 1) * 360 / $previewSegments->count()).'deg';
    })->implode(',');
    $pwText = match($locale) {
        'en' => ['Purchase wheel','Send an eligible order to unlock the wheel.','Spin','Continue to WhatsApp','Close','Try again','Your prize: ','Order amount'],
        'he' => ['גלגל קנייה','שלחו הזמנה בסכום מתאים לפתיחת הגלגל.','סובבו','המשך לוואטסאפ','סגירה','נסו שוב','הפרס שלך: ','סכום ההזמנה'],
        default => ['عجلة الشراء','أرسل طلباً بالمبلغ المطلوب لفتح العجلة.','لف العجلة','متابعة إلى واتساب','إغلاق','تعذّر التحميل، حاول مجدداً','جائزتك: ','مبلغ الطلب'],
    };
    if ($shop->slug === 'elegance-perfumes' && $shop->catalog_type === 'cosmetics') {
        $pwText[3] = match($locale) {'en'=>'Send order on WhatsApp', 'he'=>'שליחת ההזמנה בוואטסאפ', default=>'إرسال الطلب على واتساب'};
    }
@endphp
<style>
.restaurant-bottom-actions{position:fixed;bottom:calc(12px + env(safe-area-inset-bottom));left:50%;transform:translateX(-50%);width:min(640px,calc(100% - 24px));display:flex;gap:8px;z-index:15;align-items:stretch}
.restaurant-bottom-actions .mobile-cart{position:static;transform:none;width:auto;flex:1;min-width:0;padding:10px;font-size:clamp(12px,3vw,16px)}
.purchase-wheel-trigger{position:relative;flex:0 0 72px;width:72px;height:72px;align-self:center;border:0;border-radius:50%;background:transparent;padding:0;cursor:pointer;filter:drop-shadow(0 0 9px #00d7ea66)}
.purchase-wheel-trigger:before{content:'';position:absolute;top:-4px;left:calc(50% - 7px);border-left:7px solid transparent;border-right:7px solid transparent;border-top:14px solid #fff;z-index:3}
.purchase-wheel-mini{position:absolute;inset:2px;border:3px solid #07d8ec;border-radius:50%;box-sizing:border-box;box-shadow:inset 0 0 0 2px #071b25}
.purchase-wheel-mini:after{content:'';position:absolute;inset:0;border:3px dotted #ffffffb3;border-radius:50%}
.purchase-wheel-lock{position:absolute;inset:0;display:grid;place-items:center;border-radius:50%;background:#06111c55;transition:opacity .2s}
.purchase-wheel-lock svg{width:32px;height:36px;padding:7px;box-sizing:content-box;border-radius:50%;background:#071922e8;filter:drop-shadow(0 2px 3px #0008)}
.purchase-wheel-trigger.is-ready .purchase-wheel-lock{opacity:0;pointer-events:none}
.purchase-wheel-trigger.is-ready .purchase-wheel-mini{border-color:#50e9a7}
.purchase-wheel-trigger:focus-visible{outline:3px solid #fff;outline-offset:4px}
#purchaseWheelDialog{position:fixed;inset:0;margin:auto;width:min(420px,calc(100vw - 24px));max-height:85dvh;overflow:auto;box-sizing:border-box;padding:20px;border:1px solid #06cde0;border-radius:20px;background:#0b1a22;color:#fff;text-align:center}
#purchaseWheelDialog::backdrop{background:#000b}
#purchaseWheelDialog button{padding:12px 16px;border:1px solid #09dcec;border-radius:12px;background:#123842;color:white;cursor:pointer;margin:5px}
#purchaseWheelDialog button:disabled{opacity:.5;cursor:wait}
.purchase-wheel-frame{position:relative;width:min(270px,100%);aspect-ratio:1;margin:20px auto}
.purchase-wheel-frame:before{content:'▼';position:absolute;top:-16px;left:calc(50% - 12px);z-index:2;color:#fff;font-size:26px}
#purchaseWheelDisc{width:100%;height:100%;border:4px solid #0ad9e9;box-sizing:border-box;border-radius:50%;position:relative;transition:transform 3s cubic-bezier(.12,.8,.15,1)}
#purchaseWheelDisc span{position:absolute;left:50%;top:50%;width:42%;font-size:11px;font-weight:bold;line-height:1.2;text-shadow:0 1px 3px #000;transform-origin:0 0;text-align:center;overflow-wrap:anywhere}
body{padding-bottom:90px}
</style>
@if($purchaseWheels->isNotEmpty())
<button type="button" id="purchaseWheelTrigger" class="purchase-wheel-trigger" aria-label="{{ $pwText[0] }} — {{ $pwText[1] }}" title="{{ $pwText[0] }}" aria-haspopup="dialog">
    <span class="purchase-wheel-mini" aria-hidden="true" style="background:conic-gradient({{ $previewGradient }})"></span>
    <span class="purchase-wheel-lock" aria-hidden="true">
        <svg viewBox="0 0 32 36" fill="none"><path d="M8 15V10a8 8 0 0 1 16 0v5" stroke="#fff" stroke-width="3.5"/><rect x="3" y="14" width="26" height="20" rx="5" fill="#ffd274" stroke="#fff" stroke-width="1.5"/><circle cx="16" cy="23" r="3" fill="#17252e"/><path d="M16 25v4" stroke="#17252e" stroke-width="3" stroke-linecap="round"/></svg>
    </span>
</button>
<dialog id="purchaseWheelDialog" aria-labelledby="purchaseWheelTitle">
    <h3 id="purchaseWheelTitle">{{ $pwText[0] }} — {{ $shop->name }}</h3>
    <p id="purchaseWheelMessage" role="status"></p>
    <div id="purchaseWheelRanges">{{ $pwText[1] }}
        @foreach($purchaseWheels as $pw)<p>{{ $pwText[7] }}: {{ $pw->min_order_total }}–{{ $pw->max_order_total ?? '∞' }} ₪</p>@endforeach
    </div>
    <div class="purchase-wheel-frame" hidden id="purchaseWheelFrame"><div id="purchaseWheelDisc"></div></div>
    <button type="button" id="purchaseWheelSpin" hidden>{{ $pwText[2] }}</button>
    <button type="button" id="purchaseWheelWhatsapp" hidden>{{ $pwText[3] }}</button>
    <button type="button" id="purchaseWheelClose">{{ $pwText[4] }}</button>
</dialog>
<script>
(() => {
 const t=@json($pwText), key='restaurant-purchase-wheel-{{ $shop->id }}', el=id=>document.getElementById(id);
 const dialog=el('purchaseWheelDialog'), trigger=el('purchaseWheelTrigger'), spin=el('purchaseWheelSpin'), msg=el('purchaseWheelMessage');
 let pending=null, busy=false, wheel=null, reward=null;
 try { pending=JSON.parse(sessionStorage.getItem(key)||'null'); } catch (_) {}
 const save=()=>{try{sessionStorage.setItem(key,JSON.stringify(pending));}catch(_){}};
 const ready=()=>{trigger.classList.toggle('is-ready',!!pending);trigger.setAttribute('aria-label',t[0]+(pending?'':' — '+t[1]));};
 const result=r=>{reward=r;msg.textContent=r.discount_type==='none'?r.label:t[6]+r.label;spin.hidden=true;};
 const request=async(url,method='GET')=>{
   const res=await fetch(url,{method,headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||@json(csrf_token())}});
   if(!res.ok)throw new Error(t[5]); return res.json();
 };
 const preparePhotos=async data=>{
   if(!data.order_share_url || !window.OzmanOrderPhotoShare)return;
   const button=el('purchaseWheelWhatsapp');button.disabled=true;
   try{await window.OzmanOrderPhotoShare.prepare(data.order_share_url,data.order_share);}
   catch(_){msg.textContent='تعذّر تجهيز الصور؛ اضغط إرسال الطلب لإعادة المحاولة.';}
   finally{button.disabled=false;}
 };
 async function open(){
   if(!dialog.open)dialog.showModal();
   if(busy)return;
   msg.textContent=''; spin.hidden=true;el('purchaseWheelFrame').hidden=true;el('purchaseWheelRanges').hidden=!!pending;
   el('purchaseWheelWhatsapp').hidden=!pending?.whatsapp_url && !pending?.order_share_url;
   if(!pending)return;
   try{
     const data=await request(pending.url);
     if(Number(data.shop_id)!==@json($shop->id)){pending=null;save();ready();throw new Error(t[5]);}
     pending.whatsapp_url=data.whatsapp_url;pending.order_share_url=data.order_share_url;save();el('purchaseWheelWhatsapp').hidden=!data.whatsapp_url && !data.order_share_url;
     await preparePhotos(data);
     wheel=data.wheel;
     if(data.reward){result(data.reward);return;}
     if(!wheel)throw new Error(t[5]);
     const disc=el('purchaseWheelDisc'), n=wheel.segments.length;disc.replaceChildren();disc.style.transform='rotate(0deg)';
     disc.style.background='conic-gradient('+wheel.segments.map((s,i)=>(/^#[0-9a-f]{6}$/i.test(s.color)?s.color:'#087f93')+' '+i*360/n+'deg '+(i+1)*360/n+'deg').join(',')+')';
     wheel.segments.forEach((s,i)=>{const label=document.createElement('span');label.textContent=s.label;label.style.transform=`rotate(${(i+.5)*360/n-90}deg) translate(18px,-50%)`;disc.append(label);});
     el('purchaseWheelFrame').hidden=false;spin.hidden=false;
   }catch(e){msg.textContent=e.message;}
 }
 trigger.onclick=open;
 el('purchaseWheelClose').onclick=()=>dialog.close();
 el('purchaseWheelWhatsapp').onclick=()=>{
   if(pending?.order_share_url && window.OzmanOrderPhotoShare){window.OzmanOrderPhotoShare.send(pending.order_share_url,msg);}
   else if(pending?.whatsapp_url)window.location.assign(pending.whatsapp_url);
 };
 spin.onclick=async()=>{
   if(busy||!pending)return;busy=true;spin.disabled=true;el('purchaseWheelWhatsapp').disabled=true;
   try{const data=await request(pending.spin_url,'POST');
     pending.whatsapp_url=data.whatsapp_url;pending.order_share_url=data.order_share_url;save();el('purchaseWheelWhatsapp').hidden=!data.whatsapp_url && !data.order_share_url;
     await preparePhotos(data);el('purchaseWheelWhatsapp').disabled=true;
     el('purchaseWheelDisc').style.transform=`rotate(${1800-(data.reward.segment_index+.5)*360/wheel.segments.length}deg)`;
     await new Promise(resolve=>setTimeout(resolve,3100));result(data.reward);
   }catch(e){msg.textContent=e.message;}finally{busy=false;spin.disabled=false;el('purchaseWheelWhatsapp').disabled=false;}
 };
 window.RestaurantPurchaseWheel={ordered(data){
   if(!data.purchase_wheel)return false;
   pending={...data.purchase_wheel,whatsapp_url:data.whatsapp_url,order_share_url:data.order_share_url};reward=null;save();ready();
   // Close the cart dialog before presenting the reward dialog.
   document.querySelectorAll('dialog[open]').forEach(d=>d.close());open();return true;
 }};
 ready();
})();
</script>
@endif
