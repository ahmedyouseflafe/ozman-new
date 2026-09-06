<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إدارة مطعم {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08dcf4;--cyan-soft:rgba(8,220,244,.12);--purple:#762cff;--green:#25df87;--yellow:#ffd43b;--red:#ff6274;--bg:#05030d;--panel:rgba(17,20,29,.92);--panel-2:rgba(10,13,19,.9);--border:rgba(139,160,179,.2);--muted:#9ba7b4}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:
            radial-gradient(circle at 83% 8%,rgba(0,222,244,.12),transparent 31%),
            radial-gradient(circle at 8% 18%,rgba(118,44,255,.13),transparent 29%),
            linear-gradient(135deg,#080313,#02080a 65%,#071118);color:#f7f9fb;font-family:"Cairo",Arial,sans-serif}
        button,input,select{font:inherit}
        .restaurant-page{width:auto;max-width:none;margin:0 245px 0 0;padding:26px clamp(18px,2.2vw,36px) 52px;overflow:hidden}
        .glass{background:linear-gradient(135deg,rgba(25,23,39,.91),rgba(11,25,28,.9));border:1px solid var(--border);box-shadow:0 20px 70px rgba(0,0,0,.25);backdrop-filter:blur(15px)}
        .hero{position:relative;overflow:hidden;border-radius:24px;padding:24px 28px;margin-bottom:18px}
        .hero:before{content:"";position:absolute;inset:0;background:linear-gradient(100deg,rgba(118,44,255,.12),transparent 43%,rgba(8,220,244,.1));pointer-events:none}
        .hero-top,.hero-actions,.section-head,.live-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;position:relative}
        .eyebrow{color:var(--cyan);font-weight:800;font-size:12px}.hero h1{font-size:clamp(24px,2.35vw,36px);line-height:1.35;margin:6px 0}.hero p{color:var(--muted);margin:0;max-width:750px;font-size:14px;font-weight:600}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:45px;padding:9px 18px;border-radius:14px;border:1px solid rgba(8,220,244,.32);background:rgba(8,220,244,.08);color:var(--cyan);text-decoration:none;font-weight:800;cursor:pointer;transition:.2s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(8,220,244,.18)}.btn-primary{background:linear-gradient(135deg,#0cd9ec,#22bff1);color:#021014;border:0}.btn-danger{color:#ff9ba6;border-color:rgba(255,98,116,.35);background:rgba(255,98,116,.09)}
        .stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}
        .stat{position:relative;overflow:hidden;border-radius:19px;padding:17px 19px;min-height:112px}
        .stat:after{content:"";position:absolute;width:85px;height:85px;border-radius:50%;background:var(--accent);filter:blur(50px);opacity:.27;left:-8px;bottom:-20px}
        .stat-icon{width:40px;height:40px;border-radius:13px;display:grid;place-items:center;background:color-mix(in srgb,var(--accent) 14%,transparent);color:var(--accent);font-size:21px}.stat-label{color:#c3cad2;font-size:14px;font-weight:700}.stat strong{display:block;color:var(--accent);font-size:30px;margin-top:5px;line-height:1}
        .section{border-radius:23px;padding:21px;margin-bottom:18px}.section-head{margin-bottom:16px}.section-title{display:flex;align-items:center;gap:11px}.section-icon{width:43px;height:43px;border-radius:14px;display:grid;place-items:center;border:1px solid rgba(8,220,244,.32);background:var(--cyan-soft);color:var(--cyan);font-size:22px}.section h2{font-size:21px;margin:0}.section-subtitle{color:var(--muted);font-size:12px;margin-top:2px}
        .form-row{display:grid;grid-template-columns:minmax(180px,1.4fr) minmax(150px,1fr) auto;gap:12px}
        .field{width:100%;min-height:49px;border:1px solid var(--border);background:rgba(5,8,13,.75);color:#fff;border-radius:14px;padding:10px 15px;outline:0}.field:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(8,220,244,.1)}.field::placeholder{color:#687583}
        .tables-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:13px;margin-top:16px}.table-card{border:1px solid var(--border);background:rgba(5,8,13,.72);border-radius:18px;padding:15px;text-align:center}.table-card h3{margin:3px 0}.table-card p{color:var(--muted);margin:2px}.qr-box{width:125px;height:125px;padding:7px;background:#fff;border-radius:14px;margin:10px auto}.qr-box img{width:100%;height:100%}.table-actions{display:flex;justify-content:center;gap:7px;flex-wrap:wrap}
        .filters{display:grid;grid-template-columns:minmax(190px,1fr) minmax(190px,1fr) auto;gap:10px;width:min(620px,100%)}.live{display:inline-flex;align-items:center;gap:7px;color:var(--green);font-size:12px;font-weight:800}.live-dot{width:8px;height:8px;border-radius:50%;background:currentColor;box-shadow:0 0 13px currentColor;animation:pulse 1.6s infinite}@keyframes pulse{50%{opacity:.4}}
        .orders-wrap{overflow:auto;border:1px solid var(--border);border-radius:19px;background:rgba(3,6,10,.55)}table{width:100%;min-width:990px;border-collapse:collapse}th{color:var(--cyan);font-size:13px;background:rgba(8,220,244,.05)}th,td{text-align:right;padding:16px;border-bottom:1px solid rgba(139,160,179,.13);vertical-align:top}tbody tr{transition:.2s}tbody tr:hover{background:rgba(8,220,244,.035)}tbody tr:last-child td{border-bottom:0}small{color:var(--muted)}.tag{display:inline-flex;align-items:center;padding:5px 11px;border-radius:30px;background:var(--cyan-soft);color:var(--cyan);border:1px solid rgba(8,220,244,.24);font-size:12px;font-weight:800}.status-form{display:flex;align-items:center;gap:7px}.status-form .field{min-height:39px;padding:5px 9px}.status-form .btn{min-height:39px;padding:5px 12px}
        .notice{border-radius:16px;padding:14px 18px;margin-bottom:18px}.notice-success{border:1px solid rgba(37,223,135,.35);background:rgba(37,223,135,.09);color:#73f2ae}.notice-error{border:1px solid rgba(255,98,116,.35);background:rgba(255,98,116,.09);color:#ffabb5}.empty{grid-column:1/-1;color:var(--muted);text-align:center;padding:18px}
        nav[role="navigation"]{margin-top:18px}
        @media(max-width:1250px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:900px){.restaurant-page{width:100%;margin:0;padding:18px 14px 100px}.hero{padding:23px}.hero-top,.hero-actions,.section-head,.live-heading{align-items:flex-start;flex-direction:column}.stats-grid{grid-template-columns:repeat(2,1fr)}.section{padding:19px}.form-row,.filters{grid-template-columns:1fr}.form-row .btn,.filters .btn{width:100%}}
        @media(max-width:520px){.restaurant-page{padding-inline:10px}.hero{border-radius:21px;padding:19px}.hero h1{font-size:25px}.stats-grid{gap:10px}.stat{min-height:112px;padding:15px}.stat strong{font-size:28px}.stat-icon{width:39px;height:39px}.section{border-radius:21px;padding:15px}.section h2{font-size:20px}.tables-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
@include('admin.includes.sidebar')
<main class="restaurant-page">
    <section class="hero glass">
        <div class="hero-top">
            <div>
                <span class="eyebrow">OZMAN RESTAURANT CONTROL</span>
                <h1>إدارة مطعم {{ $shop->name }}</h1>
                <p>لوحة موحّدة لإدارة الطاولات، طلبات الصالة، الطلبات الأونلاين، المطبخ والكاشير.</p>
            </div>
            <div class="hero-actions">
                <a class="btn" href="{{ route('shops.show',$shop) }}"><i class="ti ti-arrow-right"></i> لوحة المتجر</a>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('products'))
                    <a class="btn" href="{{ route('products',['shop_id'=>$shop->id]) }}"><i class="ti ti-tools-kitchen-2"></i> إدارة الوجبات</a>
                @endif
                <a class="btn btn-primary" href="{{ route('restaurant.menu',$shop) }}" target="_blank"><i class="ti ti-external-link"></i> فتح منيو المطعم</a>
            </div>
        </div>
    </section>

    @if(session('status'))<div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice notice-error"><b>تعذّر تنفيذ العملية:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="stats-grid">
        <article class="stat glass" style="--accent:var(--cyan)"><div class="stat-icon"><i class="ti ti-receipt"></i></div><span class="stat-label">طلبات اليوم</span><strong id="stat-today">{{ $stats['today'] }}</strong></article>
        <article class="stat glass" style="--accent:var(--green)"><div class="stat-icon"><i class="ti ti-sparkles"></i></div><span class="stat-label">طلبات جديدة</span><strong id="stat-new">{{ $stats['new'] }}</strong></article>
        <article class="stat glass" style="--accent:var(--yellow)"><div class="stat-icon"><i class="ti ti-chef-hat"></i></div><span class="stat-label">قيد التحضير</span><strong id="stat-preparing">{{ $stats['preparing'] }}</strong></article>
        <article class="stat glass" style="--accent:var(--purple)"><div class="stat-icon"><i class="ti ti-bell-check"></i></div><span class="stat-label">جاهزة للتسليم</span><strong id="stat-ready">{{ $stats['ready'] }}</strong></article>
    </section>

    <section class="section glass">
        <div class="section-head">
            <div class="section-title"><span class="section-icon"><i class="ti ti-tools-kitchen-2"></i></span><div><h2>الطاولات ورموز QR</h2><div class="section-subtitle">أنشئ رمزاً مستقلاً لكل طاولة ليستطيع الزبون فتح المنيو والطلب.</div></div></div>
        </div>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.store'))
            <form class="form-row" method="post" action="{{ route('restaurant.tables.store',$shop) }}">
                @csrf
                <input class="field" name="name" placeholder="مثال: طاولة 1" required maxlength="100">
                <input class="field" name="capacity" type="number" min="1" max="100" placeholder="عدد المقاعد">
                <button class="btn btn-primary"><i class="ti ti-plus"></i> إضافة طاولة</button>
            </form>
        @endif
        <div class="tables-grid">
            @forelse($tables as $table)
                <article class="table-card">
                    <h3>{{ $table->name }}</h3><p>{{ $table->capacity ? $table->capacity.' مقاعد' : 'السعة غير محددة' }}</p>
                    <div class="qr-box"><img src="{{ route('restaurant.tables.qr',['table'=>$table->code]) }}" alt="QR {{ $table->name }}"></div>
                    <div class="table-actions">
                        <a class="btn" download href="{{ route('restaurant.tables.qr',['table'=>$table->code]) }}"><i class="ti ti-download"></i> تحميل QR</a>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.destroy'))
                            <form method="post" action="{{ route('restaurant.tables.destroy',$table) }}">@csrf @method('delete')<button class="btn btn-danger" onclick="return confirm('حذف الطاولة؟')"><i class="ti ti-trash"></i></button></form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty"><i class="ti ti-table-off" style="font-size:35px"></i><p>لا توجد طاولات بعد. أضف أول طاولة لإنشاء رمز QR الخاص بها.</p></div>
            @endforelse
        </div>
    </section>

    <section class="section glass">
        <div class="live-heading">
            <div class="section-title"><span class="section-icon"><i class="ti ti-chef-hat"></i></span><div><h2>شاشة المطبخ والكاشير</h2><div class="section-subtitle">طلبات الصالة والتوصيل والاستلام في مكان واحد.</div></div></div>
            <span class="live" id="live-status"><i class="live-dot"></i> متصل وتحديث مباشر</span>
        </div>
        <form class="filters" method="get" style="margin:22px 0 16px">
            <select class="field" name="type"><option value="">كل أنواع الطلب</option>@foreach(['dine_in'=>'طلبات الطاولات','delivery'=>'توصيل','pickup'=>'استلام'] as $key=>$label)<option value="{{ $key }}" @selected($selectedType===$key)>{{ $label }}</option>@endforeach</select>
            <select class="field" name="status"><option value="">كل الحالات</option>@foreach(['new'=>'جديد','preparing'=>'قيد التحضير','ready'=>'جاهز','completed'=>'مكتمل','cancelled'=>'ملغي'] as $key=>$label)<option value="{{ $key }}" @selected($selectedStatus===$key)>{{ $label }}</option>@endforeach</select>
            <button class="btn btn-primary"><i class="ti ti-filter"></i> فلترة</button>
        </form>
        <div class="orders-wrap"><table><thead><tr><th>الطلب</th><th>المصدر</th><th>الزبون / الطاولة</th><th>تفاصيل الوجبات</th><th>المجموع</th><th>الحالة</th></tr></thead><tbody id="restaurant-orders-body">
            @include('admin.restaurant.partials.orders_rows', [
                'orders' => $orders,
                'canManageOrders' => auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.orders.status'),
            ])
        </tbody></table></div>
        <div>{{ $orders->links() }}</div>
    </section>
</main>
<script>
(() => {
    const body = document.getElementById('restaurant-orders-body');
    const liveStatus = document.getElementById('live-status');
    if (!body || !liveStatus) return;
    const feedUrl = {{ Illuminate\Support\Js::from(route('restaurant.orders.feed', ['shop'=>$shop,'status'=>$selectedStatus,'type'=>$selectedType])) }};
    let latestId = Number(body.querySelector('tr[data-order-id]')?.dataset.orderId || 0);
    let polling = false;
    async function refreshOrders() {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const response = await fetch(feedUrl,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',cache:'no-store'});
            if (response.status===401 || response.status===403){liveStatus.innerHTML='<i class="live-dot"></i> لا توجد صلاحية أو انتهت الجلسة';liveStatus.style.color='var(--red)';return}
            if (!response.ok) throw new Error('feed');
            const data=await response.json(); body.innerHTML=data.html;
            for(const key of ['today','new','preparing','ready']){const element=document.getElementById(`stat-${key}`);if(element)element.textContent=data.stats[key]??0}
            liveStatus.innerHTML=Number(data.latest_id)>latestId&&latestId>0?'<i class="live-dot"></i> وصل طلب جديد الآن':'<i class="live-dot"></i> متصل وتحديث مباشر';
            latestId=Math.max(latestId,Number(data.latest_id)||0);liveStatus.style.color='var(--green)';
        } catch(_){liveStatus.innerHTML='<i class="live-dot"></i> جاري إعادة الاتصال';liveStatus.style.color='var(--yellow)'} finally{polling=false}
    }
    window.setInterval(refreshOrders,3000);
    document.addEventListener('visibilitychange',()=>{if(!document.hidden)refreshOrders()});
})();
</script>
</body>
</html>
