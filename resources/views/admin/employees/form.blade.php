<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $employee->exists ? 'تعديل موظف' : 'إضافة موظف' }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.employees.form')
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
