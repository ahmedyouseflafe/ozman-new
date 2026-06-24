<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إدارة المستخدمين - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
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

        .content { padding: 28px 34px 46px; }

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

        .btn-primary {
            border: 0;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 34px rgba(0, 229, 255, .58);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card,
        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
        }

        .stat-card {
            min-height: 142px;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--card-color, var(--primary)));
            box-shadow: 0 0 18px var(--card-color, var(--primary));
        }

        .stat-label {
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 900;
        }

        .stat-val {
            margin-top: 18px;
            color: var(--card-color, var(--primary));
            font-size: 38px;
            line-height: 1;
            font-weight: 900;
            text-shadow: 0 0 18px rgba(0, 229, 255, .45);
        }

        .stat-icon {
            position: absolute;
            left: 20px;
            bottom: 18px;
            font-size: 48px;
            color: var(--card-color, var(--primary));
            opacity: .18;
        }

        .panel { padding: 24px; }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .panel-title {
            color: #fff;
            font-size: 19px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--primary);
            filter: drop-shadow(0 0 10px rgba(0, 229, 255, .55));
        }

        .filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 16px;
        }

        .search-inp,
        .filter-select {
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 999px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: all .3s ease;
        }

        .search-inp {
            width: 250px;
            padding: 0 16px 0 42px;
        }

        .filter-select {
            min-width: 178px;
            padding: 0 16px;
            cursor: pointer;
        }

        .search-inp:focus,
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .search-inp::placeholder { color: var(--dim); }

        .filter-select option {
            color: #111;
            background: #fff;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
        }

        table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 16px 18px;
            text-align: right;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        th {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            text-shadow: 0 0 9px rgba(0, 229, 255, .35);
            background: rgba(255, 255, 255, .025);
        }

        td {
            color: var(--muted);
            font-weight: 700;
        }

        tbody tr { transition: background .25s ease; }
        tbody tr:hover { background: rgba(0, 229, 255, .055); }
        tr:last-child td { border-bottom: 0; }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 900;
            min-width: 220px;
        }

        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #000;
            border: 1px solid var(--primary);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 900;
            box-shadow: 0 0 16px rgba(0, 229, 255, .35);
            flex-shrink: 0;
        }

        .user-sub {
            display: block;
            color: var(--dim);
            font-size: 11px;
            margin-top: 3px;
            direction: ltr;
            text-align: right;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 76px;
            min-height: 30px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid currentColor;
        }

        .tag-g { color: var(--green); background: rgba(37, 211, 102, .1); }
        .tag-r { color: var(--danger); background: rgba(255, 59, 48, .1); }
        .tag-y { color: var(--yellow); background: rgba(255, 214, 10, .1); }
        .tag-c { color: var(--primary); background: rgba(0, 229, 255, .09); }
        .tag-p { color: #b35cff; background: rgba(112, 0, 255, .12); }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .05);
            color: rgba(255, 255, 255, .72);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .3s ease;
        }

        .icon-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px) scale(1.06);
            box-shadow: 0 0 16px rgba(0, 229, 255, .32);
        }

        .empty-state {
            padding: 46px 18px;
            text-align: center;
            color: var(--dim);
        }

        .empty-state i {
            display: block;
            color: var(--primary);
            font-size: 42px;
            margin-bottom: 12px;
            filter: drop-shadow(0 0 14px rgba(0, 229, 255, .45));
        }

        @media(max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
        }

        @media(max-width: 680px) {
            .content { padding: 20px 16px 34px; }
            .page-head,
            .panel-head {
                align-items: stretch;
                flex-direction: column;
            }
            .filter-row { width: 100%; }
            .stats-grid { grid-template-columns: 1fr; }
            .search-inp,
            .filter-select { width: 100%; }
            h1 { font-size: 28px; }
        }
    </style>
</head>

<body>
    @php
        $userItems = collect($users ?? [
            [
                'name' => 'Admin',
                'email' => 'admin@ozman.com',
                'phone' => '059-000-0001',
                'role' => 'super_admin',
                'role_label' => 'Super Admin',
                'status_label' => 'نشط',
                'status_class' => 'tag-g',
            ],
            [
                'name' => 'محمد الإداري',
                'email' => 'm.admin@ozman.com',
                'phone' => '059-010-0100',
                'role' => 'company_admin',
                'role_label' => 'مشرف شركة',
                'status_label' => 'نشط',
                'status_class' => 'tag-g',
            ],
            [
                'name' => 'أحمد سالم',
                'email' => 'ahmed@ozman.com',
                'phone' => '059-111-2222',
                'role' => 'shop_owner',
                'role_label' => 'صاحب متجر',
                'status_label' => 'نشط',
                'status_class' => 'tag-g',
            ],
            [
                'name' => 'خالد وكيل',
                'email' => 'khalid@example.com',
                'phone' => '059-222-3333',
                'role' => 'agent',
                'role_label' => 'وكيل',
                'status_label' => 'نشط',
                'status_class' => 'tag-g',
            ],
            [
                'name' => 'منى حسن',
                'email' => 'mona@gmail.com',
                'phone' => '059-333-4444',
                'role' => 'customer',
                'role_label' => 'عميل',
                'status_label' => 'نشط',
                'status_class' => 'tag-g',
            ],
        ]);

        $roleLabels = [
            'super_admin' => 'Super Admin',
            'company_admin' => 'مشرف شركة',
            'shop_owner' => 'صاحب متجر',
            'agent' => 'وكيل',
            'distributor' => 'موزع',
            'marketer' => 'مسوقة',
            'customer' => 'عميل',
        ];

        $roleClasses = [
            'super_admin' => 'tag-r',
            'company_admin' => 'tag-p',
            'shop_owner' => 'tag-c',
            'agent' => 'tag-y',
            'distributor' => 'tag-p',
            'marketer' => 'tag-c',
            'customer' => 'tag-g',
        ];

        $usersTotal = $usersCount ?? $userItems->count();
        $superAdminsCount = $superAdminsCount ?? $userItems->where('role', 'super_admin')->count();
        $companyAdminsCount = $companyAdminsCount ?? $userItems->where('role', 'company_admin')->count();
        $shopOwnersCount = $shopOwnersCount ?? $userItems->where('role', 'shop_owner')->count();
        $customersCount = $customersCount ?? $userItems->where('role', 'customer')->count();
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'إدارة المستخدمين'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط المستخدمين">
                    <div class="ticker">
                        <span>إدارة مستخدمي Ozman من مكان واحد</span>
                        <span>أدوار واضحة: إدارة، أصحاب متاجر، وكلاء، موزعون، وعملاء</span>
                        <span>بحث وفلترة مباشرة داخل جدول المستخدمين</span>
                        <span>إدارة مستخدمي Ozman من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">الإدارة</div>
                        <h1>إدارة المستخدمين</h1>
                        <p>{{ $usersTotal }} مستخدم مسجل داخل النظام مع متابعة الأدوار والحالة.</p>
                    </div>
                    <a href="#" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        مستخدم جديد
                    </a>
                </header>

                <section class="stats-grid" aria-label="إحصائيات المستخدمين">
                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">Super Admin</div>
                        <div class="stat-val">{{ $superAdminsCount }}</div>
                        <i class="ti ti-shield-star stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">مشرفو الشركة</div>
                        <div class="stat-val">{{ $companyAdminsCount }}</div>
                        <i class="ti ti-user-cog stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">أصحاب المتاجر</div>
                        <div class="stat-val">{{ $shopOwnersCount }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">العملاء</div>
                        <div class="stat-val">{{ $customersCount }}</div>
                        <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة المستخدمين
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="roleFilter" aria-label="فلترة الدور">
                                <option value="">كل الأدوار</option>
                                @foreach($roleLabels as $role => $label)
                                    <option value="{{ $role }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="userSearch" type="search" placeholder="بحث بالاسم، البريد، الهاتف...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الهاتف</th>
                                    <th>الدور</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userItems as $user)
                                    @php
                                        $role = data_get($user, 'role', 'customer');
                                        $roleLabel = data_get($user, 'role_label', $roleLabels[$role] ?? $role);
                                        $roleClass = data_get($user, 'role_class', $roleClasses[$role] ?? 'tag-c');
                                        $statusClass = data_get($user, 'status_class', data_get($user, 'is_active', true) ? 'tag-g' : 'tag-r');
                                        $statusLabel = data_get($user, 'status_label', data_get($user, 'is_active', true) ? 'نشط' : 'غير نشط');
                                        $name = data_get($user, 'name', '-');
                                    @endphp
                                    <tr data-role="{{ $role }}">
                                        <td>
                                            <div class="user-cell">
                                                <span class="avatar-circle">{{ mb_substr($name, 0, 1) }}</span>
                                                <span>
                                                    {{ $name }}
                                                    <span class="user-sub">{{ data_get($user, 'created_at') ? 'انضم: ' . data_get($user, 'created_at') : 'Ozman user' }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td dir="ltr">{{ data_get($user, 'email', '-') }}</td>
                                        <td dir="ltr">{{ data_get($user, 'phone', '-') }}</td>
                                        <td><span class="tag {{ $roleClass }}">{{ $roleLabel }}</span></td>
                                        <td><span class="tag {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>
                                            <div class="actions">
                                                <button type="button" class="icon-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </button>
                                                @if($role !== 'super_admin')
                                                    <button type="button" class="icon-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="ti ti-user-off" aria-hidden="true"></i>
                                                لا يوجد مستخدمون لعرضهم حاليا
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        const userSearch = document.getElementById('userSearch');
        const roleFilter = document.getElementById('roleFilter');
        const userRows = document.querySelectorAll('#usersTable tbody tr');

        function filterUsers() {
            const query = (userSearch?.value || '').trim().toLowerCase();
            const role = roleFilter?.value || '';

            userRows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesRole = !role || row.dataset.role === role;
                row.style.display = matchesQuery && matchesRole ? '' : 'none';
            });
        }

        userSearch?.addEventListener('input', filterUsers);
        roleFilter?.addEventListener('change', filterUsers);
    </script>
</body>

</html>
