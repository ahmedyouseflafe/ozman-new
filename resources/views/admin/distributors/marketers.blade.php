<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>مسوقو الموزعين - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at 12% 16%, rgba(112, 0, 255, .14), transparent 30%), radial-gradient(circle at 80% 8%, rgba(0, 229, 255, .16), transparent 34%), #050505;
            color: #fff;
            font-family: Cairo, Segoe UI, sans-serif;
        }
        .main { min-height: 100vh; margin-right: 245px; }
        .content { padding: 28px 34px 46px; }
        .hero, .panel, .stat-card {
            border: 1px solid rgba(255,255,255,.1);
            background: linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025));
            border-radius: 24px;
            box-shadow: 0 18px 48px rgba(0,0,0,.34);
        }
        .hero { padding: 24px; margin-bottom: 20px; display: flex; justify-content: space-between; gap: 16px; align-items: flex-end; }
        .hero h1 { color: #00e5ff; font-size: 32px; margin-bottom: 8px; }
        .hero p, .muted { color: rgba(255,255,255,.64); font-weight: 700; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
        .stat-card { padding: 18px; }
        .stat-card span { color: rgba(255,255,255,.64); font-size: 13px; font-weight: 900; }
        .stat-card strong { display: block; margin-top: 10px; color: #00e5ff; font-size: 30px; }
        .panel { padding: 22px; }
        .filters { display: grid; grid-template-columns: 1.5fr 1fr 1fr auto; gap: 10px; margin-bottom: 18px; }
        .create-form {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
            gap: 10px;
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid rgba(0,229,255,.16);
            border-radius: 20px;
            background: rgba(0,229,255,.05);
        }
        .create-form-title {
            grid-column: 1 / -1;
            color: #00e5ff;
            font-size: 16px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .input, .select {
            height: 46px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.32);
            color: #fff;
            border-radius: 16px;
            padding: 0 14px;
            font: inherit;
            font-weight: 800;
        }
        .select option { background: #111; color: #fff; }
        .btn {
            border: 0;
            border-radius: 999px;
            min-height: 42px;
            padding: 10px 16px;
            display: inline-flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.14);
            font: inherit;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary { background: rgba(0,229,255,.14); color: #00e5ff; border-color: rgba(0,229,255,.34); }
        .btn-danger { color: #ff6878; border-color: rgba(255,104,120,.32); background: rgba(255,104,120,.08); }
        .table-wrap { overflow-x: auto; border: 1px solid rgba(255,255,255,.08); border-radius: 20px; }
        table { width: 100%; min-width: 1160px; border-collapse: collapse; }
        th, td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.07); text-align: right; vertical-align: top; }
        th { color: #00e5ff; font-size: 12px; }
        th:last-child, td:last-child { width: 154px; text-align: center; }
        tr:last-child td { border-bottom: 0; }
        .tag {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            border: 1px solid rgba(0,229,255,.26);
            color: #00e5ff;
            background: rgba(0,229,255,.08);
        }
        .tag.green { color: #25d366; border-color: rgba(37,211,102,.3); background: rgba(37,211,102,.08); }
        .tag.red { color: #ff6878; border-color: rgba(255,104,120,.3); background: rgba(255,104,120,.08); }
        .actions {
            display: inline-grid;
            grid-template-columns: repeat(4, 42px);
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        .actions > form { display: contents; }
        .commission-form {
            display: grid;
            grid-template-columns: 92px 42px;
            gap: 8px;
            align-items: center;
        }
        .commission-form .input {
            height: 40px;
            min-width: 0;
            padding: 0 8px;
            text-align: center;
        }
        .action-btn {
            width: 42px;
            height: 42px;
            min-height: 42px;
            padding: 0;
            border-radius: 14px;
            font-size: 20px;
        }
        .action-btn:disabled,
        .action-disabled {
            opacity: .45;
            cursor: not-allowed;
            filter: grayscale(.25);
        }
        .modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0,0,0,.72);
            backdrop-filter: blur(12px);
        }
        .modal.show { display: flex; }
        .modal-card {
            width: min(720px, 100%);
            max-height: 92vh;
            overflow: auto;
            border: 1px solid rgba(0,229,255,.22);
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(20,20,26,.98), rgba(5,5,8,.98));
            box-shadow: 0 24px 70px rgba(0,0,0,.5), 0 0 34px rgba(0,229,255,.12);
            padding: 22px;
        }
        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .modal-title {
            color: #00e5ff;
            font-size: 22px;
            font-weight: 900;
        }
        .modal-close {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.07);
            color: #fff;
            cursor: pointer;
            font-size: 20px;
        }
        .edit-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .edit-form .full { grid-column: 1 / -1; }
        .check-row {
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px;
            padding: 0 14px;
            color: #fff;
            font-weight: 900;
            background: rgba(255,255,255,.055);
        }
        .modal-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 6px;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .empty, .errors, .status { padding: 16px; border-radius: 16px; font-weight: 800; margin-bottom: 16px; }
        .empty { text-align: center; color: rgba(255,255,255,.62); }
        .errors { color: #ff8794; background: rgba(230,55,75,.13); }
        .status { color: #25d366; background: rgba(37,211,102,.1); }
        .pagination { margin-top: 16px; }
        @media(max-width: 1000px) {
            .main { margin-right: 0; }
            .content { padding: 22px 16px; }
            .hero, .filters, .create-form { display: flex; flex-direction: column; align-items: stretch; }
            .stats { grid-template-columns: 1fr; }
            .edit-form { grid-template-columns: 1fr; }
            .modal-card { padding: 16px; border-radius: 18px; }
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'مسوقو الموزعين'])
        <div class="content">
            <section class="hero">
                <div>
                    <h1>مسوقو الموزعين</h1>
                    <p>عرض كل المسوقين المرتبطين بالموزعين، فلترة النتائج، وتحديد صلاحيات حساباتهم.</p>
                </div>
                <a href="{{ route('distributors') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للموزعين</a>
            </section>

            @if(session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="stats">
                <div class="stat-card"><span>إجمالي النتائج</span><strong>{{ number_format($marketers->total()) }}</strong></div>
                <div class="stat-card"><span>نشط في هذه الصفحة</span><strong>{{ number_format($marketers->getCollection()->where('is_active', true)->count()) }}</strong></div>
                <div class="stat-card"><span>طلبات من روابطهم في هذه الصفحة</span><strong>{{ number_format($marketers->getCollection()->sum('front_orders_count')) }}</strong></div>
            </div>

            <section class="panel">
                @if($distributors->isNotEmpty())
                    <form
                        class="create-form"
                        method="POST"
                        action="{{ route('distributors.marketers.store', $distributors->first()) }}"
                        data-action-template="{{ url('/distributors/__DISTRIBUTOR__/marketers') }}"
                    >
                        @csrf
                        <input type="hidden" name="return_to" value="marketers_index">
                        <div class="create-form-title">
                            <i class="ti ti-user-plus"></i>
                            إضافة مسوق للموزع
                        </div>
                        <select class="select" name="distributor_id" id="marketerDistributorSelect" required>
                            @foreach($distributors as $distributor)
                                <option value="{{ $distributor->id }}">{{ $distributor->name }} - {{ $distributor->shop?->name ?? 'بدون متجر' }}</option>
                            @endforeach
                        </select>
                        <input class="input" type="text" name="name" value="{{ old('name') }}" placeholder="اسم المسوق" required>
                        <input class="input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="الهاتف" dir="ltr">
                        <input class="input" type="tel" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="واتساب" dir="ltr">
                        <input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="البريد وحساب الدخول">
                        <input class="input" type="number" name="commission_rate" value="{{ old('commission_rate', 0) }}" min="0" max="100" step="0.01" placeholder="نسبة الربح %" dir="ltr">
                        <input class="input" type="password" name="login_password" placeholder="كلمة مرور اختيارية">
                        <button class="btn btn-primary" type="submit"><i class="ti ti-plus"></i>إضافة</button>
                    </form>
                @endif

                <form class="filters" method="GET" action="{{ route('distributors.marketers.index') }}">
                    <input class="input" type="search" name="search" value="{{ $search }}" placeholder="بحث بالاسم، البريد، الهاتف، الكود أو الموزع">
                    <select class="select" name="distributor_id">
                        <option value="">كل الموزعين</option>
                        @foreach($distributors as $distributor)
                            <option value="{{ $distributor->id }}" @selected((int) $selectedDistributorId === (int) $distributor->id)>{{ $distributor->name }}</option>
                        @endforeach
                    </select>
                    <select class="select" name="status">
                        <option value="">كل الحالات</option>
                        <option value="active" @selected($status === 'active')>نشط</option>
                        <option value="inactive" @selected($status === 'inactive')>غير نشط</option>
                    </select>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-filter"></i>فلترة</button>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>المسوق</th>
                                <th>الموزع</th>
                                <th>المتجر</th>
                                <th>التواصل</th>
                                <th>كود الرابط</th>
                                <th>حساب الدخول</th>
                                <th>نسبة الربح</th>
                                <th>الطلبات</th>
                                <th>الأرباح</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marketers as $marketer)
                                <tr>
                                    <td>
                                        <strong>{{ $marketer->name }}</strong>
                                        <div class="muted">#{{ $marketer->id }}</div>
                                    </td>
                                    <td>{{ $marketer->distributor?->name ?? '-' }}</td>
                                    <td>{{ $marketer->distributor?->shop?->name ?? '-' }}</td>
                                    <td>
                                        <div dir="ltr">{{ $marketer->phone ?: '-' }}</div>
                                        <div class="muted" dir="ltr">{{ $marketer->whatsapp ?: '-' }}</div>
                                        <div class="muted">{{ $marketer->email ?: '-' }}</div>
                                    </td>
                                    <td dir="ltr">{{ $marketer->tracking_code }}</td>
                                    <td>
                                        @if($marketer->user)
                                            <span class="tag green">مربوط</span>
                                            <div class="muted">{{ $marketer->user->email }}</div>
                                        @else
                                            <span class="tag red">غير مربوط</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form class="commission-form" method="POST" action="{{ route('distributors.marketers.commission.update', $marketer) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input class="input" type="number" name="commission_rate" value="{{ number_format((float) $marketer->commission_rate, 2, '.', '') }}" min="0" max="100" step="0.01" dir="ltr" aria-label="نسبة الربح">
                                            <button class="btn btn-primary action-btn" type="submit" title="حفظ النسبة" aria-label="حفظ النسبة"><i class="ti ti-device-floppy"></i></button>
                                        </form>
                                    </td>
                                    <td>{{ number_format($marketer->front_orders_count) }}</td>
                                    <td>
                                        <strong>{{ number_format((float) $marketer->commission_total, 2) }} شيكل</strong>
                                    </td>
                                    <td>
                                        <span class="tag {{ $marketer->is_active ? 'green' : 'red' }}">{{ $marketer->is_active ? 'نشط' : 'غير نشط' }}</span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn action-btn" type="button" data-edit-open="marketer-edit-{{ $marketer->id }}" title="تعديل" aria-label="تعديل">
                                                <i class="ti ti-pencil"></i><span class="sr-only">تعديل</span>
                                            </button>
                                            @if($marketer->user)
                                                <a class="btn btn-primary action-btn" href="{{ route('distributors.marketers.permissions.edit', $marketer) }}" title="الصلاحيات" aria-label="الصلاحيات">
                                                    <i class="ti ti-shield-lock"></i><span class="sr-only">الصلاحيات</span>
                                                </a>
                                            @else
                                                <span class="btn action-btn action-disabled" title="لا يوجد حساب دخول" aria-label="لا يوجد حساب دخول">
                                                    <i class="ti ti-user-off"></i><span class="sr-only">لا يوجد حساب دخول</span>
                                                </span>
                                            @endif
                                            <a class="btn action-btn" href="{{ route('front.marketer', ['marketer' => $marketer->tracking_code]) }}" target="_blank" rel="noopener" title="فتح الرابط" aria-label="فتح الرابط">
                                                <i class="ti ti-external-link"></i><span class="sr-only">فتح الرابط</span>
                                            </a>
                                            <a class="btn btn-primary action-btn" href="{{ route('front.marketer.direct-wheel', ['marketer' => $marketer->tracking_code]) }}" target="_blank" rel="noopener" title="رابط العجلة المباشرة" aria-label="رابط العجلة المباشرة">
                                                <i class="ti ti-rotate-clockwise"></i><span class="sr-only">رابط العجلة المباشرة</span>
                                            </a>
                                            <form method="POST" action="{{ route('distributors.marketers.destroy', $marketer) }}" onsubmit="return confirm('هل تريد حذف هذا المسوق؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger action-btn" type="submit" title="حذف" aria-label="حذف"><i class="ti ti-trash"></i><span class="sr-only">حذف</span></button>
                                            </form>
                                        </div>
                                        <div class="modal" id="marketer-edit-{{ $marketer->id }}" aria-hidden="true">
                                            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="marketer-edit-title-{{ $marketer->id }}">
                                                <div class="modal-head">
                                                    <div class="modal-title" id="marketer-edit-title-{{ $marketer->id }}">تعديل بيانات المسوق</div>
                                                    <button class="modal-close" type="button" data-edit-close aria-label="إغلاق">×</button>
                                                </div>
                                                <form class="edit-form" method="POST" action="{{ route('distributors.marketers.update', $marketer) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input class="input" type="text" name="name" value="{{ old('name', $marketer->name) }}" placeholder="اسم المسوق" required>
                                                    <input class="input" type="email" name="email" value="{{ old('email', $marketer->email) }}" placeholder="البريد وحساب الدخول">
                                                    <input class="input" type="tel" name="phone" value="{{ old('phone', $marketer->phone) }}" placeholder="الهاتف" dir="ltr">
                                                    <input class="input" type="tel" name="whatsapp" value="{{ old('whatsapp', $marketer->whatsapp) }}" placeholder="واتساب" dir="ltr">
                                                    <input class="input" type="number" name="commission_rate" value="{{ old('commission_rate', number_format((float) $marketer->commission_rate, 2, '.', '')) }}" min="0" max="100" step="0.01" placeholder="نسبة الربح %" dir="ltr">
                                                    <input class="input" type="password" name="login_password" placeholder="كلمة مرور جديدة - اتركها فارغة بدون تغيير">
                                                    <label class="check-row full">
                                                        <span>تفعيل المسوق</span>
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $marketer->is_active))>
                                                    </label>
                                                    <div class="modal-actions">
                                                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i>حفظ التعديلات</button>
                                                        <button class="btn" type="button" data-edit-close>إلغاء</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11"><div class="empty">لا يوجد مسوقون مطابقون للفلاتر الحالية.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    {{ $marketers->links() }}
                </div>
            </section>
        </div>
    </main>
    <script>
        (() => {
            const form = document.querySelector('.create-form[data-action-template]');
            const select = document.getElementById('marketerDistributorSelect');

            if (!form || !select) return;

            const syncAction = () => {
                form.action = form.dataset.actionTemplate.replace('__DISTRIBUTOR__', encodeURIComponent(select.value));
            };

            select.addEventListener('change', syncAction);
            form.addEventListener('submit', syncAction);
            syncAction();
        })();

        (() => {
            const openModal = (modal) => {
                if (!modal) return;
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                modal.querySelector('input, select, button')?.focus();
            };

            const closeModal = (modal) => {
                if (!modal) return;
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            };

            document.querySelectorAll('[data-edit-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    openModal(document.getElementById(button.dataset.editOpen));
                });
            });

            document.querySelectorAll('[data-edit-close]').forEach((button) => {
                button.addEventListener('click', () => closeModal(button.closest('.modal')));
            });

            document.querySelectorAll('.modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('.modal.show').forEach(closeModal);
            });
        })();
    </script>
</body>

</html>
