@php
    // $purchaseWheels comes from PageDataComposers::purchaseWheels.
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
@viewStyle('front.partials.restaurant_purchase_wheel')
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
     wheel=data.wheel;
     if(data.reward){result(data.reward);return;}
     if(!wheel)throw new Error(t[5]);
     const disc=el('purchaseWheelDisc'), n=wheel.segments.length;disc.replaceChildren();disc.style.transform='rotate(0deg)';
     disc.style.background='conic-gradient('+wheel.segments.map((s,i)=>(/^#[0-9a-f]{6}$/i.test(s.color)?s.color:'#087f93')+' '+i*360/n+'deg '+(i+1)*360/n+'deg').join(',')+')';
     wheel.segments.forEach((s,i)=>{const label=document.createElement('span'),text=document.createElement('b'),angle=(i+.5)*360/n-90;text.textContent=s.label;if(angle>90&&angle<270)text.style.transform='rotate(180deg)';label.append(text);label.style.transform=`rotate(${angle}deg) translate(18px,-50%)`;disc.append(label);});
     el('purchaseWheelFrame').hidden=false;spin.hidden=false;
   }catch(e){msg.textContent=e.message;}
 }
 trigger.onclick=open;
 el('purchaseWheelClose').onclick=()=>dialog.close();
 el('purchaseWheelWhatsapp').onclick=()=>{
   if(pending?.order_share_url && window.OzmanOrderWhatsapp){window.OzmanOrderWhatsapp.remember(pending.order_share_url,pending.whatsapp_url);window.OzmanOrderWhatsapp.send(msg);}
   else if(pending?.whatsapp_url)window.location.assign(pending.whatsapp_url);
 };
 spin.onclick=async()=>{
   if(busy||!pending)return;busy=true;spin.disabled=true;el('purchaseWheelWhatsapp').disabled=true;
   try{const data=await request(pending.spin_url,'POST');
     pending.whatsapp_url=data.whatsapp_url;pending.order_share_url=data.order_share_url;save();el('purchaseWheelWhatsapp').hidden=!data.whatsapp_url && !data.order_share_url;
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
