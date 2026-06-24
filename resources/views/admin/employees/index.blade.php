<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموظفون - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#050505;color:#fff;font-family:Cairo,Segoe UI,sans-serif}.main{min-height:100vh;margin-right:245px}.content{padding:30px}.panel,.hero{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.055);border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.35)}.hero{padding:24px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:16px}.hero h1{margin:0;color:#00e5ff;font-size:30px}.hero p{margin:6px 0 0;color:rgba(255,255,255,.62);font-weight:700}.btn{border:0;border-radius:999px;padding:12px 18px;font-family:inherit;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.btn-primary{background:#00e5ff;color:#001014}.btn-green{background:#25d366;color:#00140a}.btn-blue{background:#1473ff;color:#fff}.btn-yellow{background:#ffd60a;color:#111}.btn-red{background:#e6374b;color:#fff}.btn-outline{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px}.stat{padding:20px}.stat span{color:rgba(255,255,255,.6);font-weight:800}.stat strong{display:block;margin-top:8px;font-size:32px;color:#00e5ff}.toolbar{padding:18px;margin-bottom:20px;display:flex;gap:12px;align-items:center;justify-content:space-between}.search{height:48px;min-width:320px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.25);border-radius:16px;color:#fff;padding:0 16px;font-family:inherit;font-size:14px}.table-panel{padding:0;overflow:hidden}table{width:100%;border-collapse:collapse}th,td{padding:16px;border-bottom:1px solid rgba(255,255,255,.08);text-align:right}th{color:#00e5ff;font-size:13px}td{color:rgba(255,255,255,.78);font-weight:800}.employee-name{display:flex;align-items:center;gap:12px;color:#fff}.avatar{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#000;border:1px solid #00e5ff;color:#00e5ff}.muted{color:rgba(255,255,255,.5);font-size:12px}.tag{border-radius:999px;padding:6px 11px;font-size:12px;font-weight:900}.tag-on{background:rgba(37,211,102,.14);color:#25d366}.tag-off{background:rgba(230,55,75,.14);color:#ff6070}.actions{display:flex;gap:8px;flex-wrap:wrap}.pagination{padding:18px}.alert{margin-bottom:18px;padding:14px 18px;border-radius:16px;background:rgba(37,211,102,.14);color:#25d366;font-weight:900}@media(max-width:900px){.main{margin-right:0}.stats{grid-template-columns:1fr}.hero,.toolbar{flex-direction:column;align-items:stretch}.search{min-width:0;width:100%}table{min-width:860px}.table-panel{overflow-x:auto}}
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'الموظفون'])
        <div class="content">
            @if(session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif

            <section class="hero">
                <div>
                    <h1>إدارة الموظفين</h1>
                    <p>أنشئ موظفين للوحة التحكم وحدد لكل موظف الصلاحيات المناسبة.</p>
                </div>
                <a class="btn btn-primary" href="{{ route('employees.create') }}">
                    <i class="ti ti-plus"></i>
                    إضافة موظف
                </a>
            </section>

            <section class="stats">
                <div class="panel stat"><span>إجمالي الموظفين</span><strong>{{ $employeesCount }}</strong></div>
                <div class="panel stat"><span>الموظفون النشطون</span><strong>{{ $activeEmployeesCount }}</strong></div>
                <div class="panel stat"><span>غير النشطين</span><strong>{{ max($employeesCount - $activeEmployeesCount, 0) }}</strong></div>
            </section>

            <form class="panel toolbar" method="GET" action="{{ route('employees') }}">
                <input class="search" type="search" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو الإيميل أو الهاتف...">
                <div>
                    <button class="btn btn-blue" type="submit"><i class="ti ti-search"></i> بحث</button>
                    <a class="btn btn-outline" href="{{ route('employees') }}">مسح</a>
                </div>
            </form>

            <section class="panel table-panel">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>الإيميل</th>
                            <th>الحالة</th>
                            <th>الصلاحيات</th>
                            <th>تاريخ الإنشاء</th>
                            <th>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>{{ $employee->id }}</td>
                                <td>
                                    <div class="employee-name">
                                        <span class="avatar">{{ mb_substr($employee->name, 0, 1) }}</span>
                                        <span>{{ $employee->name }}<br><span class="muted">{{ $employee->phone ?: 'بدون هاتف' }}</span></span>
                                    </div>
                                </td>
                                <td dir="ltr">{{ $employee->email }}</td>
                                <td><span class="tag {{ $employee->is_active ? 'tag-on' : 'tag-off' }}">{{ $employee->is_active ? 'نشط' : 'متوقف' }}</span></td>
                                <td>{{ $employee->employee_permissions_count }} صلاحية</td>
                                <td>{{ $employee->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-yellow" href="{{ route('employees.permissions.edit', $employee) }}"><i class="ti ti-lock"></i> صلاحيات</a>
                                        <a class="btn btn-blue" href="{{ route('employees.edit', $employee) }}"><i class="ti ti-edit"></i> تعديل</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('حذف الموظف؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-red" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">لا يوجد موظفون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="pagination">{{ $employees->links() }}</div>
            </section>
        </div>
    </main>
</body>
</html>
