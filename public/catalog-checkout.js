(() => {
    const config=window.OzmanCatalogCheckout;if(!config)return;
    let busy=false,signature='',token='';
    window.OzmanSaveCatalogOrder=async(cart,name,phone,button)=>{
        if(busy||!cart.length)return;busy=true;const label=button.textContent;button.disabled=true;button.textContent='جارٍ تجهيز الطلب…';
        const details={customer_name:name,customer_phone:phone,items:cart.map(item=>({product_id:Number(item.id),qty:Number(item.qty??item.quantity),size:item.size||null,color:item.color||null}))};
        const next=JSON.stringify(details);if(next!==signature){signature=next;token=crypto.randomUUID();}
        const body=JSON.stringify({...details,request_token:token});
        const send=()=>fetch(config.url,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':config.csrf},credentials:'same-origin',body,signal:AbortSignal.timeout(20000)});
        try {
            let response=await send();
            if(response.status===419){const fresh=await fetch(config.csrfUrl,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});if(!fresh.ok)throw new Error('حدّث الصفحة وأعد المحاولة.');config.csrf=(await fresh.json()).token;response=await send();}
            const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat()[0]||data.message||'تعذّر حفظ الطلب. حاول مرة ثانية.');
            const url=new URL(data.whatsapp_url);if(url.protocol!=='https:'||url.hostname!=='wa.me')throw new Error('تعذّر فتح رابط واتساب.');
            window.location.assign(url.href);
        } catch(error){alert(error.message||'تعذّر تجهيز الطلب. حاول مرة ثانية.');}
        finally{busy=false;button.disabled=false;button.textContent=label;}
    };
})();
