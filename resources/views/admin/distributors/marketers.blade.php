<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>مسوقو الموزعين - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.distributors.marketers')
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
    @viewScript('admin.distributors.marketers')
</body>

</html>
