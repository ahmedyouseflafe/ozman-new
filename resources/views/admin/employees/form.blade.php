<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $employee->exists ? 'تعديل موظف' : 'إضافة موظف' }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#050505;color:#fff;font-family:Cairo,Segoe UI,sans-serif}.main{min-height:100vh;margin-right:245px}.content{padding:30px}.card{max-width:980px;margin:auto;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.055);border-radius:24px;padding:26px;box-shadow:0 20px 60px rgba(0,0,0,.35)}.head{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid rgba(255,255,255,.1);padding-bottom:18px;margin-bottom:22px}.head h1{margin:0;color:#00e5ff;font-size:28px}.head p{margin:6px 0 0;color:rgba(255,255,255,.62);font-weight:700}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}label{font-weight:900}.required{color:#ff6070}input{height:54px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.25);border-radius:16px;color:#fff;padding:0 16px;font-family:inherit;font-size:15px}input:focus{outline:none;border-color:#00e5ff;box-shadow:0 0 0 3px rgba(0,229,255,.12)}.check{flex-direction:row;align-items:center}.check input{width:20px;height:20px}.actions{display:flex;gap:10px;margin-top:24px}.btn{border:0;border-radius:999px;padding:12px 20px;font-family:inherit;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.btn-green{background:#25d366;color:#00140a}.btn-outline{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}.errors{margin-bottom:18px;padding:14px;border-radius:16px;background:rgba(230,55,75,.13);color:#ff8794;font-weight:800}@media(max-width:900px){.main{margin-right:0}.grid{grid-template-columns:1fr}.head{flex-direction:column}}
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => $employee->exists ? 'تعديل موظف' : 'إضافة موظف'])
        <div class="content">
            <form class="card" method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
                @csrf
                @if($employee->exists)
                    @method('PUT')
                @endif

                <div class="head">
                    <div>
                        <h1>{{ $employee->exists ? 'تعديل موظف' : 'إضافة موظف' }}</h1>
                        <p>الموظف يدخل لوحة التحكم حسب الصلاحيات التي تحددها له.</p>
                    </div>
                    <a class="btn btn-outline" href="{{ route('employees') }}"><i class="ti ti-arrow-right"></i> رجوع</a>
                </div>

                @if($errors->any())
                    <div class="errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="grid">
                    <div class="field">
                        <label>اسم الموظف <span class="required">*</span></label>
                        <input name="name" value="{{ old('name', $employee->name) }}" required>
                    </div>
                    <div class="field">
                        <label>الإيميل <span class="required">*</span></label>
                        <input type="email" name="email" dir="ltr" value="{{ old('email', $employee->email) }}" required>
                    </div>
                    <div class="field">
                        <label>الهاتف</label>
                        <input name="phone" dir="ltr" value="{{ old('phone', $employee->phone) }}">
                    </div>
                    <div class="field">
                        <label>كلمة المرور {{ $employee->exists ? '' : '*' }}</label>
                        <input type="password" name="password" {{ $employee->exists ? '' : 'required' }} autocomplete="new-password">
                    </div>
                    <label class="field check full">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active))>
                        حساب الموظف فعال
                    </label>
                </div>

                <div class="actions">
                    <button class="btn btn-green" type="submit"><i class="ti ti-device-floppy"></i> حفظ الموظف</button>
                    <a class="btn btn-outline" href="{{ route('employees') }}">إلغاء</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
