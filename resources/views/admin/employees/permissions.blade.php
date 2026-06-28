<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'صلاحيات المستخدم' }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#050505;color:#fff;font-family:Cairo,Segoe UI,sans-serif}.main{min-height:100vh;margin-right:245px}.content{padding:30px}.hero,.toolbar,.group{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.055);border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.35)}.hero{padding:24px;margin-bottom:20px;display:flex;justify-content:space-between;gap:16px}.hero h1{margin:0;color:#00e5ff;font-size:28px}.hero p{margin:6px 0 0;color:rgba(255,255,255,.62);font-weight:700}.toolbar{padding:16px;margin-bottom:20px;display:flex;gap:10px;align-items:center}.search{height:48px;flex:1;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.25);border-radius:16px;color:#fff;padding:0 16px;font-family:inherit}.btn{border:0;border-radius:999px;padding:12px 18px;font-family:inherit;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.btn-green{background:#25d366;color:#00140a}.btn-blue{background:#1473ff;color:#fff}.btn-outline{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.group{padding:18px}.group-head{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,.1);padding-bottom:12px;margin-bottom:14px}.group h2{margin:0;color:#00e5ff;font-size:17px}.count{font-size:12px;color:rgba(255,255,255,.55)}.perm{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:16px;margin-bottom:10px;background:rgba(0,0,0,.18);font-weight:900}.perm input{width:20px;height:20px;accent-color:#1473ff}.actions{display:flex;gap:10px;margin-top:22px}.errors{margin-bottom:18px;padding:14px;border-radius:16px;background:rgba(230,55,75,.13);color:#ff8794;font-weight:800}@media(max-width:1200px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:900px){.main{margin-right:0}.grid{grid-template-columns:1fr}.hero,.toolbar{flex-direction:column;align-items:stretch}}
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
                    <button class="btn btn-blue" type="button" id="selectAll"><i class="ti ti-check"></i> تحديد الكل</button>
                    <button class="btn btn-outline" type="button" id="clearAll"><i class="ti ti-eraser"></i> إلغاء الكل</button>
                </div>

                <section class="grid" id="permissionsGrid">
                    @foreach($permissionGroups as $group)
                        <article class="group" data-permission-group>
                            <div class="group-head">
                                <h2>{{ $group['label'] }}</h2>
                                <span class="count">{{ count($group['permissions'] ?? []) }} صلاحية</span>
                            </div>
                            @foreach($group['permissions'] ?? [] as $permission => $meta)
                                <label class="perm" data-permission-item>
                                    <span>{{ $meta['label'] }}</span>
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))>
                                </label>
                            @endforeach
                        </article>
                    @endforeach
                </section>

                <div class="actions">
                    <button class="btn btn-green" type="submit"><i class="ti ti-device-floppy"></i> حفظ الصلاحيات</button>
                    <a class="btn btn-outline" href="{{ $backUrl }}">إلغاء</a>
                </div>
            </form>
        </div>
    </main>
    <script>
        const search = document.getElementById('permissionSearch');
        const groups = [...document.querySelectorAll('[data-permission-group]')];
        const checks = [...document.querySelectorAll('input[name="permissions[]"]')];
        document.getElementById('selectAll')?.addEventListener('click', () => checks.forEach((check) => check.checked = true));
        document.getElementById('clearAll')?.addEventListener('click', () => checks.forEach((check) => check.checked = false));
        search?.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            groups.forEach((group) => {
                const items = [...group.querySelectorAll('[data-permission-item]')];
                let visible = false;
                items.forEach((item) => {
                    const match = item.textContent.toLowerCase().includes(query);
                    item.style.display = match ? '' : 'none';
                    visible = visible || match;
                });
                group.style.display = visible ? '' : 'none';
            });
        });
    </script>
</body>
</html>
