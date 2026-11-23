<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إدارة المستخدمين - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.users')
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
