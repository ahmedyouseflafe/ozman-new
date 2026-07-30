<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>{{ $distributor->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.distributors.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الموزع'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $distributor->name }}</h1>
                        <p>تفاصيل الموزع ومعلومات التواصل والموقع.</p>
                    </div>
                    <a href="{{ route('distributors') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للموزعين</a>
                </header>

                <section class="panel" style="padding:25px">
                    @if($distributor->image)
                        <img src="{{ asset($distributor->image) }}" class="avatar" alt="{{ $distributor->name }}">
                    @else
                        <div class="avatar"><i class="ti ti-truck-delivery"></i></div>
                    @endif

                    <span class="tag {{ $distributor->is_active ? 'tag-g' : 'tag-r' }}">{{ $distributor->is_active ? 'نشط' : 'غير نشط' }}</span>

                    <div class="detail-grid" style="margin-top:18px">
                        <div class="detail-box"><span class="label">المتجر</span><span class="value">{{ $distributor->shop?->name ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الهاتف</span><span class="value" dir="ltr">{{ $distributor->phone ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">واتساب</span><span class="value" dir="ltr">{{ $distributor->whatsapp ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">البريد</span><span class="value">{{ $distributor->email ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">العنوان</span><span class="value">{{ $distributor->address ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الإحداثيات</span><span class="value">{{ $distributor->latitude ?? '-' }}, {{ $distributor->longitude ?? '-' }}</span></div>
                    </div>

                    <div class="actions">
                        <a href="{{ $publicDistributorUrl }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="ti ti-external-link"></i>فتح رابط محل الموزع</a>
                        <a href="{{ route('distributors.edit', $distributor) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل الموزع</a>
                        <a href="{{ route('distributors') }}" class="btn">رجوع</a>
                    </div>
                </section>

                <section class="panel" style="padding:25px; margin-top:20px">
                    <div class="detail-grid">
                        <div class="detail-box" style="grid-column:1 / -1">
                            <span class="label">رابط محل الموزع</span>
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                                <input type="text" id="publicDistributorUrl" value="{{ $publicDistributorUrl }}" readonly style="flex:1; min-width:260px; background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                                <button type="button" class="btn" id="copyPublicDistributorUrl"><i class="ti ti-copy"></i>نسخ الرابط</button>
                            </div>
                        </div>
                        <div class="detail-box" style="grid-column:1 / -1; display:flex; gap:22px; align-items:center; justify-content:space-between; flex-wrap:wrap">
                            <div>
                                <span class="label">QR Code</span>
                                <span class="value">امسح الكود لتسجيل دخول المتجر وربطه بهذا الموزع تلقائيًا</span>
                            </div>
                            <a href="{{ $publicDistributorUrl }}" target="_blank" rel="noopener">
                                <img src="{{ $distributorQrCodeDataUri }}" alt="QR Code لمحل {{ $distributor->name }}" style="width:180px; height:180px; background:#fff; border-radius:18px; padding:12px">
                            </a>
                            <a href="{{ $distributorQrCodeDataUri }}" download="distributor-{{ $distributor->id }}-qr.svg" class="btn btn-primary"><i class="ti ti-download"></i>تنزيل QR</a>
                        </div>
                    </div>
                </section>

                <section class="panel" style="padding:25px; margin-top:20px">
                    <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; margin-bottom:18px">
                        <div>
                            <h2 style="color:#00e5ff; font-size:24px; margin-bottom:6px">مسوقو الموزع</h2>
                            <p style="color:rgba(255,255,255,.66); font-weight:700">أضف مسوقين للموزع، وكل مسوق يحصل على رابط تتبع خاص يظهر في طلبات الداشبورد.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('distributors.marketers.store', $distributor) }}" style="display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; margin-bottom:20px">
                        @csrf
                        <input name="name" placeholder="اسم المسوق" required style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <input name="phone" placeholder="الهاتف" dir="ltr" style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <input name="whatsapp" placeholder="واتساب" dir="ltr" style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <input name="email" placeholder="البريد الإلكتروني" dir="ltr" style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <input type="number" name="commission_rate" min="0" max="100" step="0.01" value="0" placeholder="نسبة الربح %" dir="ltr" style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <input type="password" name="login_password" placeholder="كلمة مرور الدخول" style="background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                        <label style="display:flex; align-items:center; gap:8px; color:#fff; font-weight:800">
                            <input type="checkbox" name="is_active" value="1" checked>
                            نشط
                        </label>
                        <button class="btn btn-primary" type="submit" style="grid-column:span 4"><i class="ti ti-user-plus"></i>إضافة مسوق</button>
                    </form>

                    <div style="display:grid; gap:14px">
                        @forelse($marketerShareLinks as $share)
                            @php($marketer = $share['marketer'])
                            <article class="detail-box" style="display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center">
                                <div>
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px">
                                        <strong style="font-size:18px">{{ $marketer->name }}</strong>
                                        <span class="tag {{ $marketer->is_active ? 'tag-g' : 'tag-r' }}">{{ $marketer->is_active ? 'نشط' : 'غير نشط' }}</span>
                                        <span class="tag {{ $marketer->user_id ? 'tag-g' : 'tag-r' }}">{{ $marketer->user_id ? 'حساب دخول مربوط' : 'بدون حساب دخول' }}</span>
                                        <span class="value" dir="ltr">{{ $marketer->tracking_code }}</span>
                                    </div>
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                                        <input type="text" id="marketerUrl{{ $marketer->id }}" value="{{ $share['url'] }}" readonly style="flex:1; min-width:260px; background:#08080c; border:1px solid rgba(255,255,255,.12); border-radius:14px; color:#fff; padding:14px">
                                        <button type="button" class="btn copy-marketer-url" data-copy-target="marketerUrl{{ $marketer->id }}"><i class="ti ti-copy"></i>نسخ الرابط</button>
                                        <a href="{{ $share['url'] }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="ti ti-external-link"></i>فتح الرابط</a>
                                        <input type="text" id="marketerWheelUrl{{ $marketer->id }}" value="{{ $share['wheel_url'] }}" readonly style="flex:1; min-width:260px; background:#08080c; border:1px solid rgba(0,229,255,.22); border-radius:14px; color:#fff; padding:14px">
                                        <button type="button" class="btn copy-marketer-url" data-copy-target="marketerWheelUrl{{ $marketer->id }}"><i class="ti ti-copy"></i>نسخ رابط العجلة</button>
                                        <a href="{{ $share['wheel_url'] }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="ti ti-rotate-clockwise"></i>فتح العجلة</a>
                                        <form method="POST" action="{{ route('distributors.marketers.destroy', $marketer) }}" onsubmit="return confirm('هل تريد حذف هذا المسوق؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn" type="submit"><i class="ti ti-trash"></i>حذف</button>
                                        </form>
                                    </div>
                                     <div class="value" style="margin-top:8px">
                                         {{ $marketer->phone ?: '-' }} | {{ $marketer->whatsapp ?: '-' }} | {{ $marketer->email ?: '-' }}
                                     </div>
                                     <div class="value" style="margin-top:8px; color:#00e5ff">
                                         نسبة الربح: {{ number_format((float) $marketer->commission_rate, 2) }}%
                                     </div>
                                 </div>
                                <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end">
                                    <a href="{{ $share['qr_url'] }}" target="_blank" rel="noopener" title="QR تسجيل المتجر وربطه بالمروّج">
                                        <img src="{{ $share['qr'] }}" alt="QR Code للمسوق {{ $marketer->name }}" style="width:130px; height:130px; background:#fff; border-radius:16px; padding:10px">
                                    </a>
                                    <a href="{{ $share['wheel_url'] }}" target="_blank" rel="noopener" title="QR العجلة المباشرة">
                                        <img src="{{ $share['wheel_qr'] }}" alt="QR Code للعجلة المباشرة {{ $marketer->name }}" style="width:130px; height:130px; background:#fff; border-radius:16px; padding:10px; border:2px solid #00e5ff">
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="detail-box">
                                <span class="value">لا يوجد مسوقون لهذا الموزع بعد.</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('copyPublicDistributorUrl')?.addEventListener('click', async () => {
            const input = document.getElementById('publicDistributorUrl');
            if (!input) return;
            input.select();
            await navigator.clipboard?.writeText(input.value).catch(() => document.execCommand('copy'));
        });
        document.querySelectorAll('.copy-marketer-url').forEach((button) => {
            button.addEventListener('click', async () => {
                const input = document.getElementById(button.dataset.copyTarget);
                if (!input) return;
                input.select();
                await navigator.clipboard?.writeText(input.value).catch(() => document.execCommand('copy'));
            });
        });
    </script>
</body>
</html>
