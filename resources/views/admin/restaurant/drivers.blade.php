<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>مندوبي توصيل {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.restaurant.drivers')
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="drivers-page">
        <section class="hero glass">
            <div><span class="eyebrow">فريق التوصيل</span><h1>مندوبي توصيل {{ $shop->name }}</h1><p>أضف حساب المندوب، فعّله أو أوقفه، ثم عيّنه لطلبات التوصيل من تفاصيل كل طلب.</p></div>
            <a class="btn btn-primary" href="{{ route('restaurant.dashboard', $shop) }}"><i class="ti ti-arrow-right"></i> رجوع للطلبات</a>
        </section>
        @if(session('status'))<div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice notice-error"><i class="ti ti-alert-circle"></i> {{ $errors->first() }}</div>@endif
        <div class="layout">
            <section class="panel glass">
                <div class="panel-head"><i class="ti ti-user-plus"></i><div><h2>إضافة مندوب</h2><p>بيانات الدخول تكون خاصة بهذا المندوب.</p></div></div>
                <div class="login-link"><span>رابط دخول المندوب</span><input readonly value="{{ route('driver.login') }}" onclick="this.select()" aria-label="رابط دخول المندوب"></div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.store'))
                    <form class="form" method="post" action="{{ route('restaurant.drivers.store', $shop) }}">
                        @csrf
                        <input class="field full" name="name" value="{{ old('name') }}" placeholder="اسم المندوب" autocomplete="name" required maxlength="255">
                        <input class="field" name="phone" value="{{ old('phone') }}" placeholder="رقم الجوال" inputmode="tel" autocomplete="tel" required maxlength="60">
                        <input class="field" name="email" value="{{ old('email') }}" placeholder="البريد الإلكتروني للدخول" type="email" autocomplete="off" required maxlength="255">
                        <input class="field" name="password" placeholder="كلمة المرور (8 أحرف على الأقل)" type="password" autocomplete="new-password" required minlength="8">
                        <input class="field" name="password_confirmation" placeholder="تأكيد كلمة المرور" type="password" autocomplete="new-password" required minlength="8">
                        <button class="btn btn-primary full" type="submit"><i class="ti ti-user-plus"></i> إضافة مندوب وربطه بالمطعم</button>
                    </form>
                @endif
            </section>
            <section class="panel glass">
                <div class="panel-head"><i class="ti ti-motorbike"></i><div><h2>المندوبون الحاليون</h2><p>يظهر للمندوب الفعّال فقط طلبات التوصيل المعينة له.</p></div></div>
                <div class="drivers-grid">
                    @forelse($drivers as $driver)
                        <article class="driver-card">
                            <div class="driver-card-top"><strong>{{ $driver->user?->name }}</strong><span class="tag {{ $driver->is_active ? '' : 'off' }}">{{ $driver->is_active ? 'فعال' : 'متوقف' }}</span></div>
                            <small>{{ $driver->user?->phone }}<br>{{ $driver->user?->email }}</small>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.toggle'))
                                <form method="post" action="{{ route('restaurant.drivers.toggle', $driver) }}">@csrf @method('patch')<input type="hidden" name="is_active" value="{{ $driver->is_active ? 0 : 1 }}"><button class="btn {{ $driver->is_active ? 'btn-danger' : '' }}" type="submit"><i class="ti {{ $driver->is_active ? 'ti-player-pause' : 'ti-player-play' }}"></i> {{ $driver->is_active ? 'إيقاف المندوب' : 'تفعيل المندوب' }}</button></form>
                            @endif
                        </article>
                    @empty
                        <div class="empty"><div><i class="ti ti-motorbike-off"></i><p>لا يوجد مندوبون بعد.</p></div></div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</body>
</html>
