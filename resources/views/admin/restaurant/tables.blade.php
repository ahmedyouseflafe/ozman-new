<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>طاولات مطعم {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.restaurant.tables')
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="tables-page">
        <section class="hero glass">
            <div>
                <span class="eyebrow">الطاولات</span>
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
