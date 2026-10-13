@if($shop->slug === 'elegance-perfumes')
<div id="beautyWelcomeFields" hidden>
    <input id="beautyProfileAddress"><input id="beautyProfileLatitude"><input id="beautyProfileLongitude">
</div>
<button type="button" id="bankaiProfileEdit" class="beauty-social" style="position:relative;width:auto;padding:8px 16px;margin:12px" aria-label="بياناتي">بياناتي</button>
@include('front.partials.restaurant_customer_welcome', ['isRtl'=>$rtl])
@include('front.partials.shop_signup_wheel')
<div class="cosmetics-purchase-control">
    @include('front.partials.restaurant_purchase_wheel')
</div>
<style>
.cosmetics-checkout-bar{position:fixed;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));display:flex;align-items:center;gap:12px;z-index:45;max-width:calc(100% - 24px)}
.cosmetics-checkout-bar .beauty-cart{position:static;min-width:0;flex:1}
.cosmetics-purchase-control{flex:none}
.beauty-reward-tools{bottom:100px!important}
#beautyPurchaseWheelOpen{display:none}
#bankaiWelcome{border-color:#b47da1;background:#1b101e}
#bankaiWelcome .bankai-welcome-brand{background:radial-gradient(circle at top,#623653,#180f1e)}
#bankaiWelcome .bankai-welcome-form{background:#160f1b}
#bankaiWelcome .bankai-welcome-submit{background:linear-gradient(110deg,#ff80b8,#bd78ff)}
#shopSignupWheel,#purchaseWheelDialog{border-color:#e8c276;background:radial-gradient(circle at top,#493049,#130d1b)}
@media(max-width:480px){.cosmetics-checkout-bar{left:12px}.cosmetics-checkout-bar .beauty-cart strong{font-size:12px}}
</style>
<script>
(() => {
 const $=id=>document.getElementById(id), orderButton=$('beautyWhatsappOrder');
 const bar=document.createElement('div');bar.className='cosmetics-checkout-bar';document.body.append(bar);
 bar.append(document.querySelector('.beauty-cart'),document.querySelector('.cosmetics-purchase-control'));
 document.querySelector('.beauty-brand')?.append($('bankaiProfileEdit'));
 document.querySelector('.cosmetics-hero')?.after($('signupWheelReopen'));
 const summary=document.createElement('p');summary.setAttribute('role','status');summary.style.color='#f7ce82';
 document.querySelector('.cart-actions')?.before(summary);
 const update=()=>{
   const subtotal=(window.OzmanBeautyCart||[]).reduce((sum,i)=>sum+i.price*i.qty,0);
   const discount=window.OzmanSignupReward?.discount(subtotal)||0,gift=window.OzmanSignupReward?.gift();
   summary.textContent=gift?'هدية مع أول طلب: '+gift.label:discount?'خصم التسجيل: '+discount.toFixed(2)+' ₪':'';
   $('beautyCartSheetTotal').textContent=(subtotal-discount).toFixed(2)+' ₪';
   $('beautyCartTotal').textContent=(subtotal-discount).toFixed(2)+' ₪';
 };
 document.addEventListener('restaurant:reward-changed',update);
 document.addEventListener('beauty:cart-changed',update);
 let sending=false;
 orderButton.addEventListener('click',async event=>{
   event.preventDefault();event.stopImmediatePropagation();
   if(sending||!window.OzmanBeautyCart?.length)return;
   if(!window.OzmanRestaurantCustomer?.token()){$('bankaiProfileEdit').click();return;}
   sending=true;orderButton.setAttribute('aria-disabled','true');
   try{
     if(!await window.OzmanSignupReward.beforeOrder())return;
     const response=await fetch(@json(route('cosmetics.orders.store',$shop)),{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({registration_token:window.OzmanRestaurantCustomer.token(),items:window.OzmanBeautyCart.map(i=>({product_id:i.id,qty:i.qty}))})});
     const data=await response.json();if(!response.ok)throw new Error(data.message||'تعذّر إرسال الطلب');
     window.OzmanSignupReward.consumed();window.OzmanBeautyCart.splice(0);window.OzmanBeautyCartSync();
     summary.textContent='تم حفظ طلبك '+data.order_number+' — '+Number(data.total).toFixed(2)+' ₪';
     $('closeBeautyCart').click();
     if(!window.RestaurantPurchaseWheel?.ordered(data)&&data.whatsapp_url)window.location.assign(data.whatsapp_url);
   }catch(error){summary.textContent=error.message;}finally{sending=false;orderButton.removeAttribute('aria-disabled');}
 },true);
})();
</script>
@endif
