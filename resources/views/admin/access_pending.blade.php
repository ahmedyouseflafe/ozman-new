<!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>صلاحيات الحساب</title>
@viewStyle('admin.access_pending')</head>
<body><main><h1>تم تسجيل الدخول بنجاح</h1><p>لا توجد صفحة متاحة ضمن صلاحيات حسابك الحالية. اطلب من الإدارة تحديد الصلاحيات المطلوبة، مثل «مشاهدة طلباته فقط».</p><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">تسجيل الخروج</button></form></main></body></html>
