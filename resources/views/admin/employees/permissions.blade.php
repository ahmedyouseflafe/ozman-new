<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'صلاحيات المستخدم' }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#050505;color:#fff;font-family:Cairo,Segoe UI,sans-serif}.main{min-height:100vh;margin-right:245px}.content{padding:30px;max-width:1500px;margin:auto}.hero,.toolbar,.group,.save-bar{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.055);border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.35)}.hero{padding:24px;margin-bottom:20px;display:flex;justify-content:space-between;gap:16px}.hero h1{margin:0;color:#00e5ff;font-size:28px}.hero p{margin:6px 0 0;color:rgba(255,255,255,.62);font-weight:700}.toolbar{position:sticky;top:12px;z-index:8;padding:14px 16px;margin-bottom:22px;display:flex;gap:10px;align-items:center;backdrop-filter:blur(16px)}.search{height:48px;flex:1;min-width:220px;border:1px solid rgba(255,255,255,.14);background:#080808;border-radius:16px;color:#fff;padding:0 16px;font-family:inherit}.btn{border:0;border-radius:999px;padding:12px 18px;font-family:inherit;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer}.btn-green{background:#25d366;color:#00140a}.btn-blue{background:#1473ff;color:#fff}.btn-outline{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}.selected-summary{padding:10px 14px;border-radius:999px;background:rgba(0,229,255,.1);color:#8ff5ff;font-size:13px;font-weight:900;white-space:nowrap}.grid{display:grid;gap:20px}.group{overflow:hidden}.group-head{display:flex;align-items:center;gap:14px;padding:20px 22px;background:linear-gradient(90deg,rgba(0,229,255,.09),transparent);border-bottom:1px solid rgba(255,255,255,.1)}.group-icon{width:46px;height:46px;flex:0 0 46px;display:grid;place-items:center;border-radius:14px;background:rgba(0,229,255,.12);color:#00e5ff;font-size:23px}.group-heading{min-width:0;flex:1}.group h2{margin:0;color:#fff;font-size:19px}.group-description{margin:4px 0 0;color:rgba(255,255,255,.58);font-size:12px;font-weight:700;line-height:1.7}.group-tools{display:flex;align-items:center;gap:8px}.group-tool{border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.25);color:#fff;border-radius:999px;padding:8px 11px;font:800 11px Cairo;cursor:pointer}.count{font-size:12px;color:#8ff5ff;white-space:nowrap}.permission-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px}.perm{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;min-height:92px;padding:15px;border:1px solid rgba(255,255,255,.09);border-radius:17px;background:rgba(0,0,0,.2);cursor:pointer;transition:.18s ease}.perm:hover{border-color:rgba(0,229,255,.34);transform:translateY(-1px)}.perm:has(input:checked){border-color:rgba(37,211,102,.52);background:rgba(37,211,102,.075)}.perm-text{display:flex;flex-direction:column;gap:5px}.perm-title{font-weight:900}.perm-description{color:rgba(255,255,255,.5);font-size:11px;font-weight:700;line-height:1.65}.perm input{width:21px;height:21px;flex:0 0 auto;margin-top:2px;accent-color:#25d366}.save-bar{position:sticky;bottom:12px;z-index:7;display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:24px;padding:14px 16px;backdrop-filter:blur(16px)}.actions{display:flex;gap:10px}.errors{margin-bottom:18px;padding:14px;border-radius:16px;background:rgba(230,55,75,.13);color:#ff8794;font-weight:800}.empty-search{display:none;padding:35px;text-align:center;color:rgba(255,255,255,.55);font-weight:800}@media(max-width:1000px){.permission-list{grid-template-columns:1fr}.toolbar{position:static;flex-wrap:wrap}.search{flex-basis:100%}}@media(max-width:900px){.main{margin-right:0}.content{padding:16px}.hero,.toolbar,.save-bar{flex-direction:column;align-items:stretch}.group-head{align-items:flex-start;flex-wrap:wrap}.group-tools{width:100%;flex-wrap:wrap}.selected-summary{text-align:center}.actions{display:grid;grid-template-columns:1fr 1fr}.permission-list{padding:12px}.perm{min-height:auto}}
    </style>
</head>
<body>
    @php
        $headerTitle = $headerTitle ?? 'صلاحيات الموظف';
        $description = $description ?? 'حدد الصفحات والعمليات التي يستطيع المستخدم الوصول إليها.';
        $formAction = $formAction ?? route('employees.permissions.update', $employee);
        $backUrl = $backUrl ?? route('employees');
    @endphp
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => $headerTitle])
        <div class="content">
            <form method="POST" action="{{ $formAction }}">
                @csrf
                @method('PUT')

                <section class="hero">
                    <div>
                        <h1>{{ $headerTitle }}: {{ $employee->name }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                    <a class="btn btn-outline" href="{{ $backUrl }}"><i class="ti ti-arrow-right"></i> رجوع</a>
                </section>

                @if($errors->any())
                    <div class="errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="toolbar">
                    <input class="search" id="permissionSearch" type="search" placeholder="ابحث داخل الصلاحيات...">
                    <span class="selected-summary"><span id="selectedCount">0</span> صلاحية محددة</span>
                    <button class="btn btn-blue" type="button" id="selectAll"><i class="ti ti-check"></i> تحديد الكل</button>
                    <button class="btn btn-outline" type="button" id="clearAll"><i class="ti ti-eraser"></i> إلغاء الكل</button>
                </div>

                <section class="grid" id="permissionsGrid">
                    @php
                        $groupIcons = [
                            'dashboard' => 'ti-layout-dashboard', 'shops' => 'ti-building-store',
                            'catalog' => 'ti-package', 'people' => 'ti-users-group',
                            'orders' => 'ti-receipt', 'reward_wheels' => 'ti-chart-donut',
                            'employees' => 'ti-user-shield', 'users' => 'ti-users',
                            'settings' => 'ti-settings',
                        ];
                    @endphp
                    @foreach($permissionGroups as $groupKey => $group)
                        <article class="group" data-permission-group>
                            <div class="group-head">
                                <span class="group-icon"><i class="ti {{ $groupIcons[$groupKey] ?? 'ti-lock' }}"></i></span>
                                <div class="group-heading">
                                    <h2>{{ $group['label'] }}</h2>
                                    <p class="group-description">{{ $group['description'] ?? '' }}</p>
                                </div>
                                <div class="group-tools">
                                    <span class="count"><span data-group-selected>0</span> / {{ count($group['permissions'] ?? []) }}</span>
                                    <button class="group-tool" type="button" data-group-select>تحديد القسم</button>
                                    <button class="group-tool" type="button" data-group-clear>إلغاء القسم</button>
                                </div>
                            </div>
                            <div class="permission-list">
                                @foreach($group['permissions'] ?? [] as $permission => $meta)
                                    <label class="perm" data-permission-item>
                                        <span class="perm-text">
                                            <span class="perm-title">{{ $meta['label'] }}</span>
                                            @if(filled($meta['description'] ?? null))
                                                <span class="perm-description">{{ $meta['description'] }}</span>
                                            @endif
                                        </span>
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))>
                                    </label>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </section>
                <div class="empty-search" id="emptySearch"><i class="ti ti-search-off"></i> لا توجد صلاحيات مطابقة للبحث.</div>

                <div class="save-bar">
                    <span class="selected-summary">سيتم حفظ <span id="saveSelectedCount">0</span> صلاحية لهذا الحساب</span>
                    <div class="actions">
                        <button class="btn btn-green" type="submit"><i class="ti ti-device-floppy"></i> حفظ الصلاحيات</button>
                        <a class="btn btn-outline" href="{{ $backUrl }}">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
    <script>
        const search = document.getElementById('permissionSearch');
        const groups = [...document.querySelectorAll('[data-permission-group]')];
        const checks = [...document.querySelectorAll('input[name="permissions[]"]')];
        const selectedCount = document.getElementById('selectedCount');
        const saveSelectedCount = document.getElementById('saveSelectedCount');
        const emptySearch = document.getElementById('emptySearch');
        const updateCounts = () => {
            const total = checks.filter((check) => check.checked).length;
            if (selectedCount) selectedCount.textContent = total;
            if (saveSelectedCount) saveSelectedCount.textContent = total;
            groups.forEach((group) => {
                const count = [...group.querySelectorAll('input[name="permissions[]"]')].filter((check) => check.checked).length;
                const output = group.querySelector('[data-group-selected]');
                if (output) output.textContent = count;
            });
        };
        const setChecks = (items, checked) => {
            items.filter((check) => check.closest('[data-permission-item]')?.style.display !== 'none')
                .forEach((check) => check.checked = checked);
            updateCounts();
        };
        document.getElementById('selectAll')?.addEventListener('click', () => setChecks(checks, true));
        document.getElementById('clearAll')?.addEventListener('click', () => setChecks(checks, false));
        groups.forEach((group) => {
            const groupChecks = [...group.querySelectorAll('input[name="permissions[]"]')];
            group.querySelector('[data-group-select]')?.addEventListener('click', () => setChecks(groupChecks, true));
            group.querySelector('[data-group-clear]')?.addEventListener('click', () => setChecks(groupChecks, false));
        });
        checks.forEach((check) => check.addEventListener('change', updateCounts));
        search?.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visibleGroups = 0;
            groups.forEach((group) => {
                const items = [...group.querySelectorAll('[data-permission-item]')];
                let visible = false;
                items.forEach((item) => {
                    const match = item.textContent.toLowerCase().includes(query);
                    item.style.display = match ? '' : 'none';
                    visible = visible || match;
                });
                group.style.display = visible ? '' : 'none';
                if (visible) visibleGroups += 1;
            });
            if (emptySearch) emptySearch.style.display = visibleGroups ? 'none' : 'block';
        });
        updateCounts();
    </script>
</body>
</html>
