<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إدارة عقارات {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.real_estate.dashboard')
</head>
<body>
@include('admin.includes.sidebar')
<main class="page">
    <section class="hero glass"><div class="hero-top"><div><span class="eyebrow">العقارات</span><h1>إدارة عقارات {{ $shop->name }}</h1><p>العقارات، الصور، النشر، العملاء ومواعيد المعاينة في لوحة واحدة.</p></div><div class="actions"><a class="btn" href="{{ route('shops.show',$shop) }}"><i class="ti ti-arrow-right"></i> المتجر</a><a class="btn" target="_blank" href="{{ route('real-estate.company',$shop) }}"><i class="ti ti-external-link"></i> صفحة الشركة</a><a class="btn btn-primary" href="{{ route('real-estate.dashboard.properties.create',$shop) }}"><i class="ti ti-plus"></i> إضافة عقار</a></div></div></section>
    <nav class="dashboard-nav" aria-label="أقسام لوحة العقارات"><a href="#properties"><i class="ti ti-building-estate"></i> العقارات</a><a href="#leads"><i class="ti ti-users"></i> العملاء والمعاينات @if($stats['new_leads'])<span class="tag new">{{ $stats['new_leads'] }}</span>@endif</a><a href="#company-url"><i class="ti ti-qrcode"></i> رابط الشركة وQR</a></nav>
    @if(session('status'))<div class="notice success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>@endif
    <section class="stats">
        @foreach([['all','ti-building-estate','كل العقارات','var(--cyan)'],['published','ti-world','منشور','var(--green)'],['closed','ti-rosette-discount-check','مباع أو مؤجر','var(--purple)'],['new_leads','ti-users','عملاء جدد','var(--yellow)'],['viewings','ti-calendar-event','معاينات قادمة','var(--red)']] as [$key,$icon,$label,$color])
            <article class="stat glass" style="--accent:{{ $color }}"><i class="ti {{ $icon }}"></i><strong>{{ $stats[$key] }}</strong><span>{{ $label }}</span></article>
        @endforeach
    </section>
    <span id="properties"></span>
    <section class="section glass">
        <div class="section-head"><div><h2><i class="ti ti-home-search" style="color:var(--cyan)"></i> قائمة العقارات</h2><span class="muted">إدارة حالة كل إعلان وصوره وبياناته.</span></div><a class="btn btn-primary" href="{{ route('real-estate.dashboard.properties.create',$shop) }}"><i class="ti ti-plus"></i> عقار جديد</a></div>
        <form class="filters" method="get"><input class="field" name="q" value="{{ request('q') }}" placeholder="العنوان، المرجع أو المدينة"><select class="field" name="status"><option value="">كل الحالات</option>@foreach(['draft'=>'مسودة','published'=>'منشور','reserved'=>'محجوز','rented'=>'مؤجر','sold'=>'مباع','archived'=>'مؤرشف'] as $key=>$label)<option value="{{ $key }}" @selected($status===$key)>{{ $label }}</option>@endforeach</select><button class="btn"><i class="ti ti-filter"></i> تصفية</button></form>
        <div class="table-wrap"><table><thead><tr><th>العقار</th><th>المرجع</th><th>النوع</th><th>الموقع</th><th>السعر</th><th>الحالة</th><th>العملاء</th><th>الإجراءات</th></tr></thead><tbody>
        @forelse($properties as $property)
            @php($cover=$property->images->firstWhere('is_cover',true) ?: $property->images->first())
            <tr><td><div class="inline">@if($cover)<img class="thumb" src="{{ Storage::url($cover->path) }}" alt="">@else<div class="thumb"></div>@endif<div><b>{{ $property->title }}</b><br><small class="muted">{{ $property->area ? $property->area.' م²' : 'المساحة غير محددة' }}</small></div></div></td><td>{{ $property->reference ?: '—' }}</td><td>{{ $property->purpose==='sale'?'بيع':'إيجار' }} / {{ $property->property_type }}</td><td>{{ $property->city }}<br><small class="muted">{{ $property->neighborhood }}</small></td><td><b>{{ number_format($property->price) }} {{ $property->currency }}</b></td><td><span class="tag">{{ ['draft'=>'مسودة','published'=>'منشور','reserved'=>'محجوز','rented'=>'مؤجر','sold'=>'مباع','archived'=>'مؤرشف'][$property->status] }}</span></td><td>{{ $property->leads_count }}</td><td><div class="actions"><a class="btn" href="{{ route('real-estate.dashboard.properties.edit',[$shop,$property]) }}"><i class="ti ti-edit"></i></a>@if($property->status==='published')<a class="btn" target="_blank" href="{{ $property->publicUrl() }}"><i class="ti ti-eye"></i></a>@endif<form method="post" action="{{ route('real-estate.dashboard.properties.destroy',[$shop,$property]) }}">@csrf @method('delete')<button class="btn btn-danger" onclick="return confirm('حذف العقار وجميع صوره وطلباته؟')"><i class="ti ti-trash"></i></button></form></div></td></tr>
        @empty<tr><td colspan="8" class="empty">لا توجد عقارات بعد. ابدأ بإضافة أول عقار.</td></tr>@endforelse
        </tbody></table></div>{{ $properties->links() }}
    </section>
    <span id="leads"></span><section class="section glass">
        <div class="section-head"><div><h2><i class="ti ti-user-dollar" style="color:var(--yellow)"></i> العملاء وطلبات المعاينة</h2><span class="muted">طلبات التواصل القادمة من صفحات العقارات.</span></div><span class="tag new">{{ $stats['new_leads'] }} جديد</span></div>
        <form class="filters" method="get"><select class="field" name="lead_status"><option value="">كل حالات العملاء</option>@foreach(['new'=>'جديد','contacted'=>'تم التواصل','viewing'=>'موعد معاينة','won'=>'تمت الصفقة','lost'=>'غير مهتم'] as $key=>$label)<option value="{{ $key }}" @selected($leadStatus===$key)>{{ $label }}</option>@endforeach</select><button class="btn">تصفية</button></form>
        <div class="table-wrap"><table><thead><tr><th>العميل</th><th>العقار</th><th>التواصل</th><th>الرسالة</th><th>المعاينة</th><th>الحالة</th></tr></thead><tbody>
        @forelse($leads as $lead)<tr><td><b>{{ $lead->name }}</b><br><small class="muted">{{ $lead->created_at->diffForHumans() }}</small></td><td>{{ $lead->property?->title ?: 'استفسار عام' }}</td><td><a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a><br><small>{{ $lead->email }}</small></td><td>{{ Str::limit($lead->message,90) ?: '—' }}</td><td>{{ $lead->viewing_at?->format('Y-m-d H:i') ?: 'غير محدد' }}</td><td><form class="inline" method="post" action="{{ route('real-estate.dashboard.leads.update',[$shop,$lead]) }}">@csrf @method('patch')<select class="field" name="status">@foreach(['new'=>'جديد','contacted'=>'تم التواصل','viewing'=>'معاينة','won'=>'تمت الصفقة','lost'=>'غير مهتم'] as $key=>$label)<option value="{{ $key }}" @selected($lead->status===$key)>{{ $label }}</option>@endforeach</select><input class="field" type="datetime-local" name="viewing_at" value="{{ $lead->viewing_at?->format('Y-m-d\TH:i') }}"><button class="btn"><i class="ti ti-device-floppy"></i></button></form></td></tr>@empty<tr><td colspan="6" class="empty">لا توجد طلبات عملاء حتى الآن.</td></tr>@endforelse
        </tbody></table></div>{{ $leads->links() }}
    </section>
    <section class="section glass"><div class="section-head"><div><h2><i class="ti ti-qrcode" style="color:var(--cyan)"></i> رابط الشركة وQR</h2><span class="muted">استخدمه في إعلانات فيسبوك والمطبوعات؛ يعرض عقارات هذه الشركة فقط.</span></div><span class="tag">{{ $stats['alerts'] }} تنبيه بحث نشط</span></div><div class="qr-card"><div class="qr"><img src="{{ route('real-estate.company.qr',$shop) }}" alt="QR {{ $shop->name }}"></div><div><div class="public-link"><input class="field" id="company-url" readonly value="{{ route('real-estate.company',$shop) }}"><button class="btn" type="button" onclick="navigator.clipboard.writeText(document.getElementById('company-url').value)"><i class="ti ti-copy"></i> نسخ</button></div><div class="actions" style="margin-top:12px"><a class="btn" download="{{ $shop->slug }}-real-estate-qr.svg" href="{{ route('real-estate.company.qr',$shop) }}"><i class="ti ti-download"></i> تنزيل QR</a><a class="btn btn-primary" target="_blank" href="{{ route('real-estate.company',$shop) }}"><i class="ti ti-world"></i> فتح الصفحة</a></div></div></div></section>
</main>
</body></html>
