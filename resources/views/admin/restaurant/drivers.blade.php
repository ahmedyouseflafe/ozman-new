<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>مندوبي توصيل {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08dcf4;--green:#25df87;--red:#ff6274;--panel:#0d171f;--border:rgba(139,160,179,.2);--muted:#9ba7b4}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 83% 8%,rgba(0,222,244,.12),transparent 31%),radial-gradient(circle at 8% 18%,rgba(118,44,255,.13),transparent 29%),linear-gradient(135deg,#080313,#02080a 65%,#071118);color:#f7f9fb;font-family:Cairo,Arial,sans-serif}.drivers-page{margin:0 245px 0 0;padding:26px clamp(18px,2.2vw,36px) 52px}.glass{border:1px solid var(--border);background:linear-gradient(135deg,rgba(25,23,39,.91),rgba(11,25,28,.9));box-shadow:0 20px 70px rgba(0,0,0,.25);backdrop-filter:blur(15px)}.hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:25px 28px;border-radius:24px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:6px 0;font-size:clamp(25px,3vw,36px)}.hero p{max-width:650px;margin:0;color:var(--muted);font-size:13px;line-height:1.8}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:44px;padding:10px 14px;border:1px solid var(--border);border-radius:13px;background:#0b1720;color:#fff;font:800 12px Cairo,Arial,sans-serif;text-decoration:none;cursor:pointer}.btn-primary{border:0;background:linear-gradient(135deg,var(--cyan),#35bcff);color:#001219}.btn-danger{border-color:rgba(255,98,116,.4);background:rgba(255,98,116,.1);color:#ff9baa}.notice{margin:16px 0;padding:12px 15px;border-radius:14px;font-size:13px;font-weight:800}.notice-success{border:1px solid rgba(37,223,135,.38);background:rgba(37,223,135,.1);color:#6df0af}.notice-error{border:1px solid rgba(255,98,116,.38);background:rgba(255,98,116,.1);color:#ffabb5}.layout{display:grid;grid-template-columns:minmax(300px,390px) minmax(0,1fr);gap:18px;margin-top:18px;align-items:start}.panel{padding:20px;border-radius:22px}.panel-head{display:flex;align-items:center;gap:11px;margin-bottom:17px}.panel-head i{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:rgba(8,220,244,.12);color:var(--cyan);font-size:22px}.panel-head h2{margin:0;font-size:19px}.panel-head p{margin:2px 0 0;color:var(--muted);font-size:11px}.form{display:grid;grid-template-columns:1fr 1fr;gap:10px}.form .full{grid-column:1/-1}.field{width:100%;min-height:48px;padding:10px 13px;border:1px solid var(--border);border-radius:13px;outline:0;background:#070d13;color:#f7f9fb;font:700 13px Cairo,Arial,sans-serif}.field:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(8,220,244,.12)}.login-link{display:flex;align-items:center;gap:8px;margin:0 0 15px;padding:10px 12px;border:1px solid var(--border);border-radius:13px;background:#070d13}.login-link span{color:var(--muted);font-size:10px;font-weight:800}.login-link input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#fff;font:700 11px Cairo,Arial,sans-serif;direction:ltr}.drivers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(225px,1fr));gap:13px}.driver-card{padding:17px;border:1px solid var(--border);border-radius:19px;background:linear-gradient(145deg,rgba(10,20,26,.98),rgba(10,9,17,.98))}.driver-card-top{display:flex;align-items:center;justify-content:space-between;gap:8px}.driver-card strong{font-size:16px}.tag{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border:1px solid rgba(8,220,244,.35);border-radius:999px;color:var(--cyan);background:rgba(8,220,244,.08);font-size:10px;font-weight:900}.tag.off{border-color:rgba(255,98,116,.4);color:#ff9baa;background:rgba(255,98,116,.08)}.driver-card small{display:block;min-height:42px;margin:11px 0;color:var(--muted);font-size:11px;line-height:1.8;overflow-wrap:anywhere}.driver-card form{margin:0}.empty{display:grid;place-items:center;min-height:280px;border:1px dashed var(--border);border-radius:18px;color:var(--muted);text-align:center}.empty i{display:block;margin-bottom:10px;color:var(--cyan);font-size:40px}@media(max-width:900px){.drivers-page{margin:0;padding:18px 14px 100px}.hero{align-items:flex-start;flex-direction:column;padding:22px}.layout{grid-template-columns:1fr}}@media(max-width:520px){.form{grid-template-columns:1fr}.drivers-grid{grid-template-columns:1fr}.hero h1{font-size:25px}}
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="drivers-page">
        <section class="hero glass">
            <div><span class="eyebrow">DELIVERY TEAM</span><h1>مندوبي توصيل {{ $shop->name }}</h1><p>أضف حساب المندوب، فعّله أو أوقفه، ثم عيّنه لطلبات التوصيل من تفاصيل كل طلب.</p></div>
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
