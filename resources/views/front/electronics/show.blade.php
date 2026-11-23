<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @php
        $productName = $product->localized('name');
        $productDescription = $product->localized('description') ?: "اشترِ {$productName} من {$shop->name} عبر Ozman.";
        $productCanonical = route('electronics.product', [$shop, $product]);
        $productImage = $product->main_image
            ? asset(\Illuminate\Support\Str::startsWith($product->main_image, 'storage/') ? $product->main_image : 'storage/'.$product->main_image)
            : asset('images/logo.svg');
        $productPrice = $product->discount_price ?: $product->price;
    @endphp
    @include('front.partials.seo', [
        'title' => $productName.' | '.$shop->name,
        'description' => $productDescription,
        'canonical' => $productCanonical,
        'image' => $productImage,
        'type' => 'product',
        'schema' => [
            '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $productName,
            'description' => $productDescription, 'image' => [$productImage], 'sku' => $product->sku,
            'offers' => ['@type' => 'Offer', 'url' => $productCanonical, 'priceCurrency' => 'ILS',
                'price' => $productPrice, 'availability' => $product->quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
        ],
    ])
    @viewStyle('front.electronics.show')
</head>
<body>
@php
    $attrs = $product->catalog_attributes ?? [];
    $resolveImage = fn ($path) => $path
        ? (\Illuminate\Support\Str::startsWith($path, ['http://','https://','storage/']) ? asset($path) : asset('storage/'.$path))
        : asset('images/1.jpg');
    $gallery = collect([$product->main_image])->merge($product->images->pluck('image'))->filter()->unique()->map($resolveImage)->values();
    if ($gallery->isEmpty()) $gallery = collect([asset('images/1.jpg')]);
@endphp
<div class="wrap">
    <header class="top"><a class="back" href="{{ route('electronics.store',$shop) }}">→ العودة للمتجر</a><span class="store-name">{{ $shop->name }}</span></header>
    <main class="product">
        <section class="gallery" aria-label="صور المنتج">@include('front.partials.product_like', ['product' => $product])
            <button class="media-stage" id="openGallery" type="button" aria-label="تكبير صور المنتج"><img class="main-img" id="mainImage" src="{{ $gallery->first() }}" alt="{{ $product->localized('name') }}"></button>
            <div class="thumbs">@foreach($gallery as $index=>$src)<button class="thumb {{ $index===0?'active':'' }}" type="button" data-index="{{ $index }}"><img src="{{ $src }}" alt="صورة {{ $index+1 }}"></button>@endforeach</div>
        </section>
        <section class="info">
            <div class="eyebrow">{{ data_get($attrs,'brand') }} · {{ data_get($attrs,'condition') }}</div>
            <h1>{{ $product->localized('name') }}</h1>
            <div class="tags"><span class="tag">{{ data_get($attrs,'network') }}</span><span class="tag">{{ data_get($attrs,'screen_size') }}</span><span class="tag">{{ data_get($attrs,'sim_type') }}</span></div>
            <div class="price" id="price">{{ number_format((float)($product->variants->where('quantity','>',0)->min('price') ?? $product->discount_price ?? $product->price),2) }} ₪</div>
            @include('front.partials.loyalty_hint', ['total' => '#price'])
            <h2 class="options-title">اختر التخزين واللون</h2>
            <div class="options">@foreach($product->variants as $variant)<label class="option {{ $variant->quantity<1?'sold':'' }}"><input type="radio" name="variant" value="{{ $variant->id }}" data-price="{{ (float)($variant->price??$product->discount_price??$product->price) }}" data-color-name="{{ $variant->color_name ?: $variant->color }}" @disabled($variant->quantity<1)><i class="swatch" style="--swatch:{{ $variant->color ?: '#777777' }}"></i><span><b>{{ $variant->storage ?: 'قياسي' }}</b><small class="ram">{{ $variant->ram ? ' · RAM '.$variant->ram : '' }}</small></span><span class="variant-color">{{ $variant->color_name ?: 'لون مخصص' }}</span><small class="stock">{{ $variant->quantity>0 ? $variant->quantity.' متوفر' : 'نفد' }}</small></label>@endforeach</div>
            <form class="buy" id="buyForm"><input name="customer_name" placeholder="الاسم الكامل" required><input name="customer_phone" placeholder="رقم الجوال" required><textarea name="customer_address" placeholder="عنوان التوصيل" required></textarea><textarea name="customer_notes" placeholder="ملاحظات اختيارية"></textarea><input type="number" name="qty" min="1" max="20" value="1"><button>تأكيد الطلب أونلاين</button><div id="message"></div></form>
        </section>
    </main>
    <section class="spec-grid">@foreach(['processor'=>'المعالج','battery'=>'البطارية','cameras'=>'الكاميرات','battery_health'=>'صحة البطارية','warranty_months'=>'الضمان بالأشهر','included_accessories'=>'الملحقات','condition_notes'=>'حالة الجهاز'] as $key=>$label)@if(filled(data_get($attrs,$key)))<div class="spec"><small>{{ $label }}</small>{{ is_array(data_get($attrs,$key))?implode('، ',data_get($attrs,$key)):data_get($attrs,$key) }}</div>@endif @endforeach</section>
    <h2>أجهزة مشابهة</h2><section class="related">@foreach($related as $item)<a href="{{ route('electronics.product',[$shop,$item]) }}"><img src="{{ $resolveImage($item->main_image) }}"><b>{{ $item->localized('name') }}</b></a>@endforeach</section>
</div>
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="معرض صور المنتج"><div class="lightbox-main"><button class="lightbox-close" type="button" aria-label="إغلاق">×</button><button class="lightbox-arrow lightbox-prev" type="button" aria-label="الصورة السابقة">›</button><img class="lightbox-image" alt="{{ $product->localized('name') }}"><button class="lightbox-arrow lightbox-next" type="button" aria-label="الصورة التالية">‹</button></div><div class="lightbox-thumbs"></div></div>
@include('front.partials.coupon_field')
<script>
const gallery=@json($gallery);let current=0;const transparentGallery=new Map(),mainImage=document.getElementById('mainImage'),lightbox=document.getElementById('lightbox'),lightboxImage=lightbox.querySelector('.lightbox-image'),lightboxThumbs=lightbox.querySelector('.lightbox-thumbs');
function removeEdgeBackground(src){if(transparentGallery.has(src))return transparentGallery.get(src);const job=new Promise(resolve=>{const image=new Image();image.crossOrigin='anonymous';image.onload=()=>{try{const canvas=document.createElement('canvas'),limit=1400,scale=Math.min(1,limit/Math.max(image.naturalWidth,image.naturalHeight));canvas.width=Math.max(1,Math.round(image.naturalWidth*scale));canvas.height=Math.max(1,Math.round(image.naturalHeight*scale));const ctx=canvas.getContext('2d',{willReadFrequently:true});ctx.drawImage(image,0,0,canvas.width,canvas.height);const frame=ctx.getImageData(0,0,canvas.width,canvas.height),pixels=frame.data,w=canvas.width,h=canvas.height,total=w*h,seen=new Uint8Array(total),queue=new Int32Array(total);let head=0,tail=0;const pale=index=>{const p=index*4,r=pixels[p],g=pixels[p+1],b=pixels[p+2];return r>226&&g>226&&b>226&&Math.max(r,g,b)-Math.min(r,g,b)<24};const add=index=>{if(index>=0&&index<total&&!seen[index]&&pale(index)){seen[index]=1;queue[tail++]=index}};for(let x=0;x<w;x++){add(x);add((h-1)*w+x)}for(let y=0;y<h;y++){add(y*w);add(y*w+w-1)}while(head<tail){const index=queue[head++],x=index%w,p=index*4;pixels[p+3]=0;if(x>0)add(index-1);if(x<w-1)add(index+1);if(index>=w)add(index-w);if(index<total-w)add(index+w)}ctx.putImageData(frame,0,0);resolve(canvas.toDataURL('image/png'))}catch(error){resolve(src)}};image.onerror=()=>resolve(src);image.src=src});transparentGallery.set(src,job);return job}
async function selectImage(i){current=(i+gallery.length)%gallery.length;mainImage.src=gallery[current];document.querySelectorAll('.thumb').forEach((x,n)=>x.classList.toggle('active',n===current));lightboxImage.src=await removeEdgeBackground(gallery[current]);lightboxThumbs.querySelectorAll('.lightbox-thumb').forEach((x,n)=>x.classList.toggle('active',n===current))}
lightboxThumbs.innerHTML=gallery.map((src,i)=>`<button class="lightbox-thumb ${i===0?'active':''}" type="button" data-lightbox-index="${i}"><img src="${src}" alt="صورة ${i+1}"></button>`).join('');document.querySelectorAll('.thumb').forEach(x=>x.onclick=()=>selectImage(Number(x.dataset.index)));lightboxThumbs.onclick=e=>{const b=e.target.closest('[data-lightbox-index]');if(b)selectImage(Number(b.dataset.lightboxIndex))};document.getElementById('openGallery').onclick=()=>{selectImage(current);lightbox.classList.add('open');document.body.style.overflow='hidden'};function closeGallery(){lightbox.classList.remove('open');document.body.style.overflow=''}lightbox.querySelector('.lightbox-close').onclick=closeGallery;lightbox.querySelector('.lightbox-next').onclick=()=>selectImage(current+1);lightbox.querySelector('.lightbox-prev').onclick=()=>selectImage(current-1);lightbox.onclick=e=>{if(e.target===lightbox)closeGallery()};addEventListener('keydown',e=>{if(!lightbox.classList.contains('open'))return;if(e.key==='Escape')closeGallery();if(e.key==='ArrowLeft')selectImage(current+1);if(e.key==='ArrowRight')selectImage(current-1)});
document.querySelectorAll('[name=variant]').forEach(v=>v.onchange=()=>document.getElementById('price').textContent=Number(v.dataset.price).toFixed(2)+' ₪');OzmanCoupon.mount(document.querySelector('#buyForm button'));document.getElementById('buyForm').onsubmit=async e=>{e.preventDefault();const form=e.currentTarget;const variant=document.querySelector('[name=variant]:checked'),message=document.getElementById('message');if(!variant){message.textContent='اختر التخزين واللون أولاً.';return}const fd=new FormData(form),payload={customer_name:fd.get('customer_name'),customer_phone:fd.get('customer_phone'),customer_address:fd.get('customer_address'),customer_notes:fd.get('customer_notes'),coupon_code:OzmanCoupon.code(),items:[{variant_id:Number(variant.value),qty:Number(fd.get('qty'))}]};message.textContent='جارٍ تسجيل الطلب...';const res=await fetch(@json(route('electronics.orders.store',$shop)),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify(payload)});const data=await res.json().catch(()=>({}));message.textContent=res.ok?'تم تسجيل طلبك: '+data.order_number+' — اللون: '+variant.dataset.colorName+(data.coupon_discount>0?' — وفّرت '+Number(data.coupon_discount).toFixed(2)+' ₪ بالكود':'')+(data.coupon_error?' — '+data.coupon_error:''):(Object.values(data.errors||{}).flat()[0]||data.message||'تعذر تسجيل الطلب');if(res.ok){form.reset();OzmanCoupon.reset()}};
</script>
@include('front.partials.page_share', ['title' => $product->localized('name').' — '.$shop->name, 'url' => route('electronics.product', [$shop, $product])])
</body></html>
