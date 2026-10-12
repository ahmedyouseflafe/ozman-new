@php
    $purchaseWheels = \App\Models\RewardWheel::where('shop_id', $shop->id)->where('wheel_type', 'purchase_amount')->where('is_active', true)
        ->whereHas('segments', fn($q) => $q->where('is_active', true), '>=', 2)->orderBy('min_order_total')->get();
    $pwText = match($locale) {
        'en' => ['Purchase wheel','Send an eligible order to unlock the wheel.','Spin','Continue to WhatsApp','Close','Try again','Your prize: ','Order amount'],
        'he' => ['גלגל קנייה','שלחו הזמנה בסכום מתאים לפתיחת הגלגל.','סובבו','המשך לוואטסאפ','סגירה','נסו שוב','הפרס שלך: ','סכום ההזמנה'],
        default => ['عجلة الشراء','أرسل طلباً بالمبلغ المطلوب لفتح العجلة.','لف العجلة','متابعة إلى واتساب','إغلاق','تعذّر التحميل، حاول مجدداً','جائزتك: ','مبلغ الطلب'],
    };
@endphp
<style>
.restaurant-bottom-actions{position:fixed;bottom:calc(12px + env(safe-area-inset-bottom));left:50%;transform:translateX(-50%);width:min(640px,calc(100% - 24px));display:flex;gap:8px;z-index:15;align-items:stretch}
.restaurant-bottom-actions .mobile-cart{position:static;transform:none;width:auto;flex:1;min-width:0;padding:10px;font-size:clamp(12px,3vw,16px)}
.purchase-wheel-trigger{border:2px solid #07d8ec;border-radius:17px;background:#122d36;color:#fff;padding:8px 12px;font-weight:800;cursor:pointer;font-size:12px;max-width:42%}
.purchase-wheel-trigger.is-ready{background:#07533e;border-color:#50e9a7}
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
<button type="button" id="purchaseWheelTrigger" class="purchase-wheel-trigger" aria-label="{{ $pwText[0] }}">🔒 {{ $pwText[0] }}</button>
<dialog id="purchaseWheelDialog" aria-labelledby="purchaseWheelTitle">
    <h3 id="purchaseWheelTitle">{{ $pwText[0] }}</h3>
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
 const ready=()=>{trigger.classList.toggle('is-ready',!!pending);trigger.textContent=(pending?'🎡 ':'🔒 ')+t[0];};
 const result=r=>{reward=r;msg.textContent=t[6]+r.label;spin.hidden=true;};
 const request=async(url,method='GET')=>{
   const res=await fetch(url,{method,headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||@json(csrf_token())}});
   if(!res.ok)throw new Error(t[5]); return res.json();
 };
 async function open(){
   if(!dialog.open)dialog.showModal();
   if(busy)return;
   msg.textContent=''; spin.hidden=true;el('purchaseWheelFrame').hidden=true;el('purchaseWheelRanges').hidden=!!pending;
   el('purchaseWheelWhatsapp').hidden=!pending?.whatsapp_url;
   if(!pending)return;
   try{
     const data=await request(pending.url);wheel=data.wheel;
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
 el('purchaseWheelWhatsapp').onclick=()=>{if(pending?.whatsapp_url)window.location.assign(pending.whatsapp_url);};
 spin.onclick=async()=>{
   if(busy||!pending)return;busy=true;spin.disabled=true;
   try{const data=await request(pending.spin_url,'POST');
     el('purchaseWheelDisc').style.transform=`rotate(${1800-(data.reward.segment_index+.5)*360/wheel.segments.length}deg)`;
     await new Promise(resolve=>setTimeout(resolve,3100));result(data.reward);
   }catch(e){msg.textContent=e.message;}finally{busy=false;spin.disabled=false;}
 };
 window.RestaurantPurchaseWheel={ordered(data){
   if(!data.purchase_wheel)return false;
   pending={...data.purchase_wheel,whatsapp_url:data.whatsapp_url};reward=null;save();ready();
   // Close the cart dialog before presenting the reward dialog.
   document.querySelectorAll('dialog[open]').forEach(d=>d.close());open();return true;
 }};
 ready();
})();
</script>
@endif
