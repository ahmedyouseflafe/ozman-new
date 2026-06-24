<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إضافة متجر جديد - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .64);
            --dim: rgba(255, 255, 255, .4);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 15% 14%, rgba(112, 0, 255, .14), transparent 29%),
                radial-gradient(circle at 78% 8%, rgba(0, 229, 255, .14), transparent 34%),
                linear-gradient(180deg, #030303 0%, #050505 52%, #08020f 100%);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .shell { min-height: 100vh; }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 28px 34px 46px;
            max-width: 1220px;
            margin: 0 auto;
        }

        .hero-strip {
            min-height: 136px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: linear-gradient(90deg, rgba(0, 229, 255, .08), rgba(255, 255, 255, .035), rgba(112, 0, 255, .08));
            backdrop-filter: blur(18px);
            overflow: hidden;
            display: flex;
            align-items: center;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .42), inset 0 0 45px rgba(0, 229, 255, .035);
            margin-bottom: 28px;
        }

        .ticker {
            display: flex;
            gap: 54px;
            width: max-content;
            white-space: nowrap;
            animation: slideRtl 24s linear infinite;
            color: var(--primary);
            font-size: 18px;
            font-weight: 900;
            text-shadow: 0 0 14px rgba(0, 229, 255, .55);
        }

        @keyframes slideRtl {
            from { transform: translateX(0); }
            to { transform: translateX(50%); }
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px;
        }

        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42);
        }

        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .form-shell {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 30px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
            overflow: hidden;
        }

        .form-section {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .form-section:last-of-type { border-bottom: 0; }

        .section-head {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 18px;
        }

        .section-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #000;
            border: 1px solid var(--primary);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 21px;
            box-shadow: 0 0 18px rgba(0, 229, 255, .28);
            flex-shrink: 0;
        }

        .section-head h2 {
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.2;
        }

        .section-head p {
            color: var(--dim);
            font-size: 12px;
            font-weight: 700;
            margin-top: 4px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .form-group.full { grid-column: 1 / -1; }

        .form-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .form-label i {
            color: var(--primary);
            font-size: 16px;
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .45));
        }

        .field { position: relative; }

        .field-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 17px;
            pointer-events: none;
            opacity: .85;
        }

        textarea + .field-icon {
            top: 17px;
            transform: none;
        }

        input,
        textarea,
        select {
            width: 100%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 16px;
            color: #fff;
            padding: 12px 42px 12px 14px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: all .3s ease;
        }

        textarea {
            min-height: 112px;
            resize: vertical;
            line-height: 1.7;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        input::placeholder,
        textarea::placeholder { color: var(--dim); }

        select option {
            background: #080808;
            color: #fff;
        }

        input[type="time"],
        input[type="email"],
        input[type="number"],
        input[dir="ltr"] {
            direction: ltr;
            text-align: left;
            padding-right: 14px;
            padding-left: 42px;
        }

        input[type="time"] + .field-icon,
        input[type="email"] + .field-icon,
        input[dir="ltr"] + .field-icon {
            right: auto;
            left: 14px;
        }

        .upload-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .upload-box {
            position: relative;
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 94px;
            padding: 16px;
            border: 1px dashed rgba(0, 229, 255, .35);
            border-radius: 20px;
            background: rgba(0, 0, 0, .22);
            cursor: pointer;
            transition: all .3s ease;
        }

        .upload-box:hover {
            border-color: var(--primary);
            background: rgba(0, 229, 255, .06);
            box-shadow: 0 0 22px rgba(0, 229, 255, .16);
        }

        .upload-box input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            padding: 0;
        }

        .upload-icon,
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #000;
            border: 1px solid var(--primary);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 22px;
            box-shadow: 0 0 16px rgba(0, 229, 255, .25);
            flex-shrink: 0;
        }

        .upload-title,
        .card-title {
            display: block;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
        }

        .upload-sub,
        .card-sub {
            display: block;
            color: var(--dim);
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
        }

        .location-card,
        .switch-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
            padding: 16px;
        }

        .card-copy {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .map-status {
            color: var(--primary);
            font-weight: 900;
            text-shadow: 0 0 10px rgba(0, 229, 255, .35);
        }

        .btn {
            border: 1px solid var(--border);
            min-height: 44px;
            padding: 0 18px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            background: rgba(255, 255, 255, .055);
            font-family: inherit;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            transition: all .3s ease;
        }

        .btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .btn-primary {
            border: 0;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
        }

        .btn-primary:hover {
            color: #001014;
            box-shadow: 0 0 34px rgba(0, 229, 255, .58);
        }

        .switch {
            position: relative;
            width: 58px;
            height: 32px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--border);
            border-radius: 999px;
            transition: all .3s ease;
        }

        .slider::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            right: 4px;
            top: 3px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .72);
            transition: all .3s ease;
        }

        .switch input:checked + .slider {
            background: rgba(37, 211, 102, .22);
            border-color: var(--green);
            box-shadow: 0 0 18px rgba(37, 211, 102, .25);
        }

        .switch input:checked + .slider::before {
            transform: translateX(-26px);
            background: var(--green);
            box-shadow: 0 0 12px rgba(37, 211, 102, .7);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, .08);
            background: rgba(0, 0, 0, .22);
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .78);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 100;
        }

        .modal-backdrop.open { display: flex; }

        .location-modal {
            width: min(980px, 100%);
            border: 1px solid rgba(0, 229, 255, .28);
            background: rgba(5, 5, 5, .96);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .55), 0 0 35px rgba(0, 229, 255, .16);
        }

        .modal-head,
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .modal-footer {
            border-bottom: 0;
            border-top: 1px solid var(--border);
            background: rgba(255, 255, 255, .035);
        }

        .modal-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title strong {
            display: block;
            font-size: 16px;
            font-weight: 900;
        }

        .modal-title span,
        .picked-location {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            margin-top: 3px;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            color: #fff;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: all .3s ease;
        }

        .modal-close:hover {
            color: var(--danger);
            border-color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-body { padding: 18px; }

        .coordinate-search {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 12px;
            align-items: end;
        }

        .coordinate-search input {
            direction: ltr;
            text-align: left;
            padding-right: 14px;
        }

        .coordinate-error {
            display: none;
            color: var(--danger);
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 12px;
        }

        .coordinate-error.show { display: block; }

        #shopLocationMap {
            width: 100%;
            height: 440px;
            border: 1px solid var(--border);
            border-radius: 22px;
            overflow: hidden;
            background: #111;
        }

        .leaflet-container,
        .leaflet-control {
            font-family: 'Cairo', sans-serif;
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
            .content { padding: 20px 16px 34px; }
            .form-grid,
            .upload-grid,
            .coordinate-search { grid-template-columns: 1fr; }
            .location-card,
            .switch-card,
            .page-head,
            .modal-footer {
                align-items: stretch;
                flex-direction: column;
            }
            .btn { width: 100%; }
            h1 { font-size: 28px; }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'إضافة متجر جديد'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط إضافة متجر">
                    <div class="ticker">
                        <span>إضافة متجر جديد داخل Ozman</span>
                        <span>بيانات المتجر، الموقع، الصور، وحالة الظهور في نموذج واحد</span>
                        <span>اختيار الموقع من الخريطة مع حفظ الإحداثيات تلقائيا</span>
                        <span>إضافة متجر جديد داخل Ozman</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">المتجر</div>
                        <h1>إضافة متجر جديد</h1>
                        <p>أدخل بيانات المتجر، معلومات التواصل، الموقع، الصور، وحالة التفعيل.</p>
                    </div>
                    <a href="{{ route('shops') }}" class="btn">
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        رجوع للمتاجر
                    </a>
                </header>

                @if($errors->any())
                    <div class="form-shell" style="margin-bottom:18px;padding:18px;border-color:rgba(255,59,48,.35);background:rgba(255,59,48,.08)">
                        <strong>راجع الحقول التالية:</strong>
                        <ul style="margin:10px 20px 0;color:rgba(255,255,255,.78);font-size:13px">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-shell" action="{{ route('shops.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                            <div>
                                <h2>معلومات المتجر الأساسية</h2>
                                <p>الاسم والوصف والرابط المختصر الخاص بالمتجر.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="name"><i class="ti ti-signature" aria-hidden="true"></i>اسم المتجر</label>
                                <div class="field">
                                    <input type="text" id="name" name="name" placeholder="مثال: Ozman Shop" required>
                                    <i class="ti ti-building-store field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="slug"><i class="ti ti-link" aria-hidden="true"></i>الرابط المختصر</label>
                                <div class="field">
                                    <input type="text" id="slug" name="slug" placeholder="ozman-shop" dir="ltr">
                                    <i class="ti ti-link field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="description"><i class="ti ti-align-right" aria-hidden="true"></i>وصف المتجر</label>
                                <div class="field">
                                    <textarea id="description" name="description" placeholder="نبذة قصيرة عن المتجر والخدمات التي يقدمها"></textarea>
                                    <i class="ti ti-notes field-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-user-lock" aria-hidden="true"></i></div>
                            <div>
                                <h2>حساب صاحب المتجر</h2>
                                <p>هذا البريد وكلمة المرور يستخدمان لتسجيل دخول صاحب المتجر إلى متجره فقط.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group full">
                                <label class="form-label" for="owner_email"><i class="ti ti-mail" aria-hidden="true"></i>بريد الدخول</label>
                                <div class="field">
                                    <input type="email" id="owner_email" name="owner_email" value="{{ old('owner_email') }}" placeholder="owner@shop.com" dir="ltr" required>
                                    <i class="ti ti-mail field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="owner_password"><i class="ti ti-lock" aria-hidden="true"></i>كلمة المرور</label>
                                <div class="field">
                                    <input type="password" id="owner_password" name="owner_password" dir="ltr" required>
                                    <i class="ti ti-lock field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="owner_password_confirmation"><i class="ti ti-lock-check" aria-hidden="true"></i>تأكيد كلمة المرور</label>
                                <div class="field">
                                    <input type="password" id="owner_password_confirmation" name="owner_password_confirmation" dir="ltr" required>
                                    <i class="ti ti-lock-check field-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-phone-call" aria-hidden="true"></i></div>
                            <div>
                                <h2>معلومات التواصل</h2>
                                <p>أرقام التواصل والبريد الإلكتروني الخاص بالمتجر.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="phone"><i class="ti ti-phone" aria-hidden="true"></i>رقم الهاتف</label>
                                <div class="field">
                                    <input type="text" id="phone" name="phone" placeholder="+970-59-123-4567" dir="ltr">
                                    <i class="ti ti-phone field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="whatsapp"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>واتساب</label>
                                <div class="field">
                                    <input type="text" id="whatsapp" name="whatsapp" placeholder="+970-59-123-4567" dir="ltr">
                                    <i class="ti ti-brand-whatsapp field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="email"><i class="ti ti-mail" aria-hidden="true"></i>البريد الإلكتروني</label>
                                <div class="field">
                                    <input type="email" id="email" name="email" placeholder="example@shop.com" dir="ltr">
                                    <i class="ti ti-mail field-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-map-2" aria-hidden="true"></i></div>
                            <div>
                                <h2>الموقع وأوقات العمل</h2>
                                <p>العنوان، المدينة، الدولة، ساعات الدوام، وموقع المتجر على الخريطة.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="city"><i class="ti ti-building" aria-hidden="true"></i>المدينة</label>
                                <div class="field">
                                    <input type="text" id="city" name="city" placeholder="غزة">
                                    <i class="ti ti-building field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="country"><i class="ti ti-flag" aria-hidden="true"></i>الدولة</label>
                                <div class="field">
                                    <input type="text" id="country" name="country" placeholder="فلسطين">
                                    <i class="ti ti-flag field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="address"><i class="ti ti-map-pin" aria-hidden="true"></i>العنوان الكامل</label>
                                <div class="field">
                                    <input type="text" id="address" name="address" placeholder="العنوان الكامل للمتجر">
                                    <i class="ti ti-map-pin field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="open_time"><i class="ti ti-clock-hour-8" aria-hidden="true"></i>وقت الفتح</label>
                                <div class="field">
                                    <input type="time" id="open_time" name="open_time">
                                    <i class="ti ti-clock field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="close_time"><i class="ti ti-clock-hour-5" aria-hidden="true"></i>وقت الإغلاق</label>
                                <div class="field">
                                    <input type="time" id="close_time" name="close_time">
                                    <i class="ti ti-clock-off field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group full">
                                <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                                <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">

                                <div class="location-card">
                                    <div class="card-copy">
                                        <span class="card-icon"><i class="ti ti-map-search" aria-hidden="true"></i></span>
                                        <span>
                                            <span class="card-title">موقع المتجر على الخريطة</span>
                                            <span class="card-sub" id="locationSummary">لم يتم اختيار موقع بعد.</span>
                                        </span>
                                    </div>
                                    <button type="button" id="openMapModal" class="btn">
                                        <i class="ti ti-map-pin-plus" aria-hidden="true"></i>
                                        اختيار الموقع
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
<section class="form-section">
    <div class="section-head">
        <div class="section-icon">
            <i class="ti ti-brand-instagram"></i>
        </div>

        <div>
            <h2>مواقع التواصل الاجتماعي</h2>
            <p>روابط صفحات المتجر على السوشال ميديا.</p>
        </div>
    </div>

    <div class="form-grid">

        <div class="form-group">
            <label class="form-label">Facebook</label>

            <div class="field">
                <input type="text" name="facebook"
                    placeholder="https://facebook.com/shop">

                <i class="ti ti-brand-facebook field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Instagram</label>

            <div class="field">
                <input type="text" name="instagram"
                    placeholder="https://instagram.com/shop">

                <i class="ti ti-brand-instagram field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">TikTok</label>

            <div class="field">
                <input type="text" name="tiktok"
                    placeholder="https://tiktok.com/@shop">

                <i class="ti ti-brand-tiktok field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Telegram</label>

            <div class="field">
                <input type="text" name="telegram"
                    placeholder="https://t.me/shop">

                <i class="ti ti-brand-telegram field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Snapchat</label>

            <div class="field">
                <input type="text" name="snapchat"
                    placeholder="snapchat username">

                <i class="ti ti-brand-snapchat field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Twitter / X</label>

            <div class="field">
                <input type="text" name="twitter"
                    placeholder="https://x.com/shop">

                <i class="ti ti-brand-x field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">YouTube</label>

            <div class="field">
                <input type="text" name="youtube"
                    placeholder="https://youtube.com/shop">

                <i class="ti ti-brand-youtube field-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">WhatsApp</label>

            <div class="field">
                <input type="text" name="social_whatsapp"
                    placeholder="+970599999999">

                <i class="ti ti-brand-whatsapp field-icon"></i>
            </div>
        </div>

    </div>
</section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-credit-card-pay" aria-hidden="true"></i></div>
                            <div>
                                <h2>معلومات الدفع</h2>
                                <p>بيانات الحساب الذي يستقبل دفعات العملاء الخاصة بهذا المتجر.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="payment_method"><i class="ti ti-wallet" aria-hidden="true"></i>طريقة الدفع</label>
                                <div class="field">
                                    <select id="payment_method" name="payment_method">
                                        <option value="">اختر طريقة الدفع</option>
                                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>تحويل بنكي</option>
                                        <option value="wallet" @selected(old('payment_method') === 'wallet')>محفظة إلكترونية</option>
                                        <option value="cash" @selected(old('payment_method') === 'cash')>كاش / عند الاستلام</option>
                                        <option value="other" @selected(old('payment_method') === 'other')>أخرى</option>
                                    </select>
                                    <i class="ti ti-wallet field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_provider"><i class="ti ti-building-bank" aria-hidden="true"></i>البنك أو مزود الدفع</label>
                                <div class="field">
                                    <input type="text" id="payment_provider" name="payment_provider" value="{{ old('payment_provider') }}" placeholder="اسم البنك أو المحفظة">
                                    <i class="ti ti-building-bank field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_account_holder"><i class="ti ti-user-dollar" aria-hidden="true"></i>اسم صاحب الحساب</label>
                                <div class="field">
                                    <input type="text" id="payment_account_holder" name="payment_account_holder" value="{{ old('payment_account_holder') }}" placeholder="الاسم كما يظهر في الحساب">
                                    <i class="ti ti-user-dollar field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_account_number"><i class="ti ti-number" aria-hidden="true"></i>رقم الحساب</label>
                                <div class="field">
                                    <input type="text" id="payment_account_number" name="payment_account_number" value="{{ old('payment_account_number') }}" dir="ltr" placeholder="Account number">
                                    <i class="ti ti-number field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_iban"><i class="ti ti-receipt" aria-hidden="true"></i>IBAN</label>
                                <div class="field">
                                    <input type="text" id="payment_iban" name="payment_iban" value="{{ old('payment_iban') }}" dir="ltr" placeholder="IBAN">
                                    <i class="ti ti-receipt field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment_wallet_number"><i class="ti ti-device-mobile-dollar" aria-hidden="true"></i>رقم المحفظة</label>
                                <div class="field">
                                    <input type="text" id="payment_wallet_number" name="payment_wallet_number" value="{{ old('payment_wallet_number') }}" dir="ltr" placeholder="+970599999999">
                                    <i class="ti ti-device-mobile-dollar field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="payment_notes"><i class="ti ti-notes" aria-hidden="true"></i>ملاحظات الدفع</label>
                                <div class="field">
                                    <textarea id="payment_notes" name="payment_notes" placeholder="أي تعليمات إضافية للدفع أو التحويل">{{ old('payment_notes') }}</textarea>
                                    <i class="ti ti-notes field-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-photo-up" aria-hidden="true"></i></div>
                            <div>
                                <h2>الصور والحالة</h2>
                                <p>ارفع شعار المتجر والبانر وحدد حالة ظهوره للمستخدمين.</p>
                            </div>
                        </div>

                        <div class="upload-grid">
                            <label class="upload-box">
                                <input type="file" name="logo" accept="image/*">
                                <span class="upload-icon"><i class="ti ti-badge" aria-hidden="true"></i></span>
                                <span>
                                    <span class="upload-title">شعار المتجر</span>
                                    <span class="upload-sub">PNG أو JPG، يفضل صورة مربعة</span>
                                </span>
                            </label>

                            <label class="upload-box">
                                <input type="file" name="banner" accept="image/*">
                                <span class="upload-icon"><i class="ti ti-photo" aria-hidden="true"></i></span>
                                <span>
                                    <span class="upload-title">بانر المتجر</span>
                                    <span class="upload-sub">صورة أفقية واضحة لواجهة المتجر</span>
                                </span>
                            </label>
                        </div>

                        <div style="height:15px"></div>

                        <div class="switch-card">
                            <div class="card-copy">
                                <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                                <span>
                                    <span class="card-title">تفعيل المتجر</span>
                                    <span class="card-sub">عند التفعيل سيظهر المتجر كمحل نشط داخل المنصة.</span>
                                </span>
                            </div>
                            <label class="switch" for="is_active">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>
                            حفظ المتجر
                        </button>
                        <a href="{{ route('shops') }}" class="btn">
                            <i class="ti ti-arrow-right" aria-hidden="true"></i>
                            رجوع
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="modal-backdrop" id="locationModal" aria-hidden="true">
        <div class="location-modal" role="dialog" aria-modal="true" aria-labelledby="locationModalTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="card-icon"><i class="ti ti-map-2" aria-hidden="true"></i></span>
                    <span>
                        <strong id="locationModalTitle">اختيار موقع المتجر</strong>
                        <span>اضغط على المكان المطلوب داخل الخريطة أو أدخل الإحداثيات ثم احفظ الموقع.</span>
                    </span>
                </div>
                <button type="button" class="modal-close" id="closeMapModal" aria-label="إغلاق">
                    <i class="ti ti-x" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="coordinate-search">
                    <div>
                        <label class="form-label" for="modalLatitude"><i class="ti ti-current-location" aria-hidden="true"></i>Latitude</label>
                        <input type="number" id="modalLatitude" placeholder="31.501000" step="0.000001" min="-90" max="90">
                    </div>
                    <div>
                        <label class="form-label" for="modalLongitude"><i class="ti ti-world-longitude" aria-hidden="true"></i>Longitude</label>
                        <input type="number" id="modalLongitude" placeholder="34.466000" step="0.000001" min="-180" max="180">
                    </div>
                    <button type="button" class="btn" id="searchCoordinates">
                        <i class="ti ti-search" aria-hidden="true"></i>
                        بحث
                    </button>
                </div>
                <div class="coordinate-error" id="coordinateError">أدخل Latitude بين -90 و 90 و Longitude بين -180 و 180.</div>
                <div id="shopLocationMap"></div>
            </div>

            <div class="modal-footer">
                <div class="picked-location">
                    <i class="ti ti-current-location" aria-hidden="true"></i>
                    <span id="pickedLocationText">لم يتم تحديد موقع بعد</span>
                </div>
                <button type="button" class="btn btn-primary" id="saveLocation" disabled>
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    حفظ الموقع
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const openMapModal = document.getElementById('openMapModal');
        const closeMapModal = document.getElementById('closeMapModal');
        const locationModal = document.getElementById('locationModal');
        const saveLocation = document.getElementById('saveLocation');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const pickedLocationText = document.getElementById('pickedLocationText');
        const locationSummary = document.getElementById('locationSummary');
        const modalLatitude = document.getElementById('modalLatitude');
        const modalLongitude = document.getElementById('modalLongitude');
        const searchCoordinates = document.getElementById('searchCoordinates');
        const coordinateError = document.getElementById('coordinateError');

        let shopMap;
        let shopMarker;
        let pickedLocation = null;

        function formatLocation(lat, lng) {
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        function isValidCoordinate(lat, lng) {
            return Number.isFinite(lat) &&
                Number.isFinite(lng) &&
                lat >= -90 &&
                lat <= 90 &&
                lng >= -180 &&
                lng <= 180;
        }

        function setPickedLocation(lat, lng) {
            pickedLocation = { lat, lng };
            latitudeInput.value = lat.toFixed(6);
            longitudeInput.value = lng.toFixed(6);
            pickedLocationText.textContent = formatLocation(lat, lng);
            modalLatitude.value = lat.toFixed(6);
            modalLongitude.value = lng.toFixed(6);
            coordinateError.classList.remove('show');
            saveLocation.disabled = false;

            if (!shopMarker) {
                shopMarker = L.marker([lat, lng], { draggable: true }).addTo(shopMap);
                shopMarker.on('dragend', function(event) {
                    const position = event.target.getLatLng();
                    setPickedLocation(position.lat, position.lng);
                });
            } else {
                shopMarker.setLatLng([lat, lng]);
            }
        }

        function searchByCoordinates() {
            const lat = parseFloat(modalLatitude.value);
            const lng = parseFloat(modalLongitude.value);

            if (!isValidCoordinate(lat, lng)) {
                coordinateError.classList.add('show');
                return;
            }

            setPickedLocation(lat, lng);
            shopMap.setView([lat, lng], 16);
        }

        function openLocationModal() {
            locationModal.classList.add('open');
            locationModal.setAttribute('aria-hidden', 'false');

            setTimeout(() => {
                if (!shopMap) {
                    const initialLat = parseFloat(latitudeInput.value) || 31.501;
                    const initialLng = parseFloat(longitudeInput.value) || 34.466;

                    shopMap = L.map('shopLocationMap').setView([initialLat, initialLng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(shopMap);

                    shopMap.on('click', function(event) {
                        setPickedLocation(event.latlng.lat, event.latlng.lng);
                    });

                    if (latitudeInput.value && longitudeInput.value) {
                        setPickedLocation(initialLat, initialLng);
                    } else {
                        modalLatitude.value = initialLat.toFixed(6);
                        modalLongitude.value = initialLng.toFixed(6);
                    }
                }

                shopMap.invalidateSize();
            }, 120);
        }

        function closeLocationModal() {
            locationModal.classList.remove('open');
            locationModal.setAttribute('aria-hidden', 'true');
        }

        openMapModal.addEventListener('click', openLocationModal);
        closeMapModal.addEventListener('click', closeLocationModal);
        searchCoordinates.addEventListener('click', searchByCoordinates);

        [modalLatitude, modalLongitude].forEach((input) => {
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchByCoordinates();
                }
            });
        });

        locationModal.addEventListener('click', function(event) {
            if (event.target === locationModal) {
                closeLocationModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && locationModal.classList.contains('open')) {
                closeLocationModal();
            }
        });

        saveLocation.addEventListener('click', function() {
            if (!pickedLocation) {
                return;
            }

            latitudeInput.value = pickedLocation.lat.toFixed(6);
            longitudeInput.value = pickedLocation.lng.toFixed(6);
            locationSummary.innerHTML = `تم اختيار الموقع: <span class="map-status">${formatLocation(pickedLocation.lat, pickedLocation.lng)}</span>`;
            closeLocationModal();
        });
    </script>
</body>

</html>
