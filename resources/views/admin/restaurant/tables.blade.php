<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>طاولات مطعم {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08dcf4;--cyan-soft:rgba(8,220,244,.12);--green:#25df87;--red:#ff6274;--bg:#05030d;--panel:rgba(13,21,29,.94);--panel-2:rgba(8,14,20,.93);--border:rgba(139,160,179,.2);--muted:#9ba7b4}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 83% 8%,rgba(0,222,244,.12),transparent 31%),radial-gradient(circle at 8% 18%,rgba(118,44,255,.13),transparent 29%),linear-gradient(135deg,#080313,#02080a 65%,#071118);color:#f7f9fb;font-family:Cairo,Arial,sans-serif}.tables-page{max-width:none;margin:0 245px 0 0;padding:26px clamp(18px,2.2vw,36px) 52px}.glass{border:1px solid var(--border);background:linear-gradient(135deg,rgba(25,23,39,.91),rgba(11,25,28,.9));box-shadow:0 20px 70px rgba(0,0,0,.25);backdrop-filter:blur(15px)}.hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:25px 28px;border-radius:24px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:6px 0;font-size:clamp(25px,3vw,36px)}.hero p{max-width:660px;margin:0;color:var(--muted);font-size:13px;line-height:1.8}.actions{display:flex;flex-wrap:wrap;gap:9px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:43px;padding:10px 14px;border:1px solid var(--border);border-radius:13px;background:#0c1820;color:#f8fbfd;font:800 12px Cairo,Arial,sans-serif;text-decoration:none;cursor:pointer}.btn:hover{border-color:rgba(8,220,244,.65)}.btn-primary{border:0;background:linear-gradient(135deg,var(--cyan),#35bcff);color:#001219}.btn-danger{border-color:rgba(255,98,116,.35);background:rgba(255,98,116,.1);color:#ff91a0}.notice{margin:16px 0;padding:12px 15px;border-radius:14px;font-size:13px;font-weight:800}.notice-success{border:1px solid rgba(37,223,135,.38);background:rgba(37,223,135,.1);color:#6df0af}.notice-error{border:1px solid rgba(255,98,116,.38);background:rgba(255,98,116,.1);color:#ffabb5}.layout{display:grid;grid-template-columns:minmax(275px,360px) minmax(0,1fr);gap:18px;margin-top:18px;align-items:start}.panel{padding:20px;border-radius:22px}.panel-head{display:flex;align-items:center;gap:11px;margin-bottom:17px}.panel-head i{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:var(--cyan-soft);color:var(--cyan);font-size:22px}.panel-head h2{margin:0;font-size:19px}.panel-head p{margin:2px 0 0;color:var(--muted);font-size:11px}.table-form{display:grid;gap:11px}.field{width:100%;min-height:48px;padding:10px 13px;border:1px solid var(--border);border-radius:13px;outline:0;background:#070d13;color:#f7f9fb;font:700 13px Cairo,Arial,sans-serif}.field:focus{border-color:var(--cyan);box-shadow:0 0 0 3px var(--cyan-soft)}.help{margin:13px 0 0;color:var(--muted);font-size:11px;line-height:1.8}.table-count{display:inline-grid;place-items:center;min-width:42px;height:32px;margin-right:auto;padding:0 10px;border-radius:999px;background:var(--cyan-soft);color:var(--cyan);font-size:12px;font-weight:900}.table-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:13px}.table-card{position:relative;min-height:275px;padding:15px;border:1px solid var(--border);border-radius:20px;background:linear-gradient(145deg,rgba(10,20,26,.98),rgba(10,9,17,.98));overflow:hidden}.table-card:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 100% 0,rgba(8,220,244,.12),transparent 42%);pointer-events:none}.table-card-top{position:relative;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.table-card h3{margin:0;font-size:17px}.table-card p{margin:3px 0 0;color:var(--muted);font-size:11px}.table-qr{position:relative;display:grid;place-items:center;width:138px;height:138px;margin:14px auto;padding:8px;border:1px solid rgba(8,220,244,.3);border-radius:17px;background:#fff}.table-qr img{display:block;width:100%;height:100%;object-fit:contain}.table-url{position:relative;display:flex;align-items:center;gap:6px;min-height:34px;padding:6px 9px;border:1px solid var(--border);border-radius:10px;background:rgba(0,0,0,.16);color:var(--muted);font-size:9px;direction:ltr;overflow:hidden}.table-url span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.table-actions{position:relative;display:grid;grid-template-columns:1fr auto;gap:8px;margin-top:10px}.table-actions form{margin:0}.empty{display:grid;place-items:center;min-height:350px;padding:30px;border:1px dashed var(--border);border-radius:20px;color:var(--muted);text-align:center}.empty i{display:block;margin-bottom:10px;color:var(--cyan);font-size:42px}.empty p{margin:0;line-height:1.9}.locked{padding:18px;border:1px dashed var(--border);border-radius:16px;color:var(--muted);font-size:12px;line-height:1.8}@media(max-width:900px){.tables-page{margin:0;padding:18px 14px 100px}.hero{align-items:flex-start;flex-direction:column;padding:22px}.layout{grid-template-columns:1fr}.table-grid{grid-template-columns:repeat(auto-fill,minmax(190px,1fr))}}@media(max-width:520px){.table-grid{grid-template-columns:1fr}.actions,.actions .btn{width:100%}.hero .actions{width:100%}}
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="tables-page">
        <section class="hero glass">
            <div>
                <span class="eyebrow">RESTAURANT TABLES</span>
                <h1>طاولات {{ $shop->name }} ورموز QR</h1>
                <p>أنشئ طاولة، حمّل رمزها، وضعه عليها ليصل الزبون إلى منيو المطعم ويطلب مباشرة من مكانه.</p>
            </div>
            <div class="actions">
                <a class="btn" href="{{ route('restaurant.dashboard', $shop) }}"><i class="ti ti-arrow-right"></i> إدارة الطلبات</a>
                <a class="btn btn-primary" href="{{ route('restaurant.menu', $shop) }}" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> فتح المنيو</a>
            </div>
        </section>

        @if(session('status'))
            <div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="notice notice-error"><i class="ti ti-alert-circle"></i> {{ $errors->first() }}</div>
        @endif

        <div class="layout">
            <section class="panel glass">
                <div class="panel-head">
                    <i class="ti ti-table-plus"></i>
                    <div><h2>إضافة طاولة</h2><p>سيتم إنشاء رمز QR تلقائياً بعد الحفظ.</p></div>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.store'))
                    <form class="table-form" method="post" action="{{ route('restaurant.tables.store', $shop) }}">
                        @csrf
                        <label>اسم الطاولة<input class="field" name="name" placeholder="مثال: طاولة 1" required maxlength="100" value="{{ old('name') }}"></label>
                        <label>عدد المقاعد <small style="color:var(--muted)">(اختياري)</small><input class="field" name="capacity" type="number" min="1" max="100" placeholder="مثال: 4" value="{{ old('capacity') }}"></label>
                        <button class="btn btn-primary" type="submit"><i class="ti ti-plus"></i> إضافة الطاولة وإنشاء QR</button>
                    </form>
                    <p class="help">كل طاولة تملك رابطاً ورمزاً مختلفاً؛ لا يمكن تكرار اسم الطاولة داخل نفس المطعم.</p>
                @else
                    <div class="locked"><i class="ti ti-lock"></i> لديك صلاحية عرض الطاولات فقط، وتحتاج صلاحية إدارة الطاولات لإضافتها أو حذفها.</div>
                @endif
            </section>

            <section class="panel glass">
                <div class="panel-head">
                    <i class="ti ti-qrcode"></i>
                    <div><h2>الطاولات الحالية</h2><p>حمّل الرمز أو افتح رابط الطاولة للتأكد منه قبل طباعته.</p></div>
                    <span class="table-count">{{ $tables->count() }}</span>
                </div>
                <div class="table-grid">
                    @forelse($tables as $table)
                        @php($tableUrl = route('restaurant.table', [$shop, $table->code]))
                        <article class="table-card">
                            <div class="table-card-top">
                                <div><h3>{{ $table->name }}</h3><p>{{ $table->capacity ? $table->capacity.' مقاعد' : 'السعة غير محددة' }}</p></div>
                                <i class="ti ti-armchair-2" style="color:var(--cyan);font-size:22px"></i>
                            </div>
                            <a class="table-qr" href="{{ $tableUrl }}" target="_blank" rel="noopener" title="فتح رابط الطاولة"><img src="{{ route('restaurant.tables.qr', ['table' => $table->code]) }}" alt="QR {{ $table->name }}"></a>
                            <div class="table-url"><i class="ti ti-link"></i><span>{{ $tableUrl }}</span></div>
                            <div class="table-actions">
                                <a class="btn" download="{{ $shop->slug.'-'.$table->code.'.svg' }}" href="{{ route('restaurant.tables.qr', ['table' => $table->code]) }}"><i class="ti ti-download"></i> تحميل QR</a>
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.destroy'))
                                    <form method="post" action="{{ route('restaurant.tables.destroy', $table) }}">@csrf @method('delete')<button class="btn btn-danger" type="submit" onclick="return confirm('حذف {{ $table->name }}؟')"><i class="ti ti-trash"></i></button></form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="empty"><div><i class="ti ti-table-off"></i><p>لا توجد طاولات بعد.<br>أضف أول طاولة من النموذج لتوليد رمز QR الخاص بها.</p></div></div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</body>
</html>
