<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>أوزمان للمتاجر — افتح محلك أونلاين واستقبل الطلبات على جوالك</title>
<meta name="description" content="متجر أونلاين لمحلك بدون خبرة: منيو ومنتجات، طلبات مباشرة مع إشعار، عجلة حظ ونقاط للزباين، وتطبيق على الجوال.">
<meta property="og:title" content="أوزمان للمتاجر">
<meta property="og:description" content="افتح محلك أونلاين واستقبل الطلبات على جوالك.">
<link rel="icon" href="{{ asset('images/logo.svg') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&amp;display=swap" rel="stylesheet">
<style>
body{margin:0;background:#05070A}
a{color:#19C7E6}a:hover{color:#7FE3F5}
.wrap{width:100%;max-width:1200px;margin:0 auto;padding:0 24px;box-sizing:border-box}
.hero-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:48px;align-items:center}
.types{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:12px}
.steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.feat-main{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.feat-small{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.faq{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.nav-links{display:flex;gap:28px}
details summary{cursor:pointer;list-style:none}
details summary::-webkit-details-marker{display:none}
@media (max-width:900px){
.hero-grid,.feat-main,.faq{grid-template-columns:minmax(0,1fr)}
.types{grid-template-columns:repeat(4,minmax(0,1fr))}
.steps,.feat-small{grid-template-columns:minmax(0,1fr)}
.nav-links{display:none}
.hero-title{font-size:38px !important}
.section-title{font-size:28px !important}
}
</style>
</head>
<body id="top">
<div style="font-family: Cairo, system-ui, sans-serif; color: #EEF4F8; background: #0A1018; direction: rtl">

<header style="position: sticky; top: 0; z-index: 5; background: rgba(10,16,24,.92); border-bottom: 1px solid #1B2734; backdrop-filter: blur(10px)">
<div class="wrap" style="display: flex; align-items: center; justify-content: space-between; height: 72px; gap: 16px">
<a href="#top" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #EEF4F8">
<img src="{{ asset('images/logo.svg') }}" alt="" width="38" height="38" style="border-radius: 11px">
<span style="display: grid; line-height: 1.15"><b style="font-size: 19px; font-weight: 900">أوزمان</b><small style="font-size: 11.5px; color: #9FB0BF; font-weight: 700">للمتاجر</small></span>
</a>
<nav class="nav-links" aria-label="أقسام الصفحة">
<a href="#features" style="color: #C9D6E0; text-decoration: none; font-weight: 700; font-size: 15px">المزايا</a>
<a href="#how" style="color: #C9D6E0; text-decoration: none; font-weight: 700; font-size: 15px">كيف بتبلّش</a>
<a href="#faq" style="color: #C9D6E0; text-decoration: none; font-weight: 700; font-size: 15px">أسئلة</a>
</nav>
<a href="#join" style="display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 18px; border-radius: 12px; background: #08DEF4; color: #04161B; font-weight: 900; font-size: 15px; text-decoration: none">سجّل محلك</a>
</div>
</header>

<section id="top" style="padding: 72px 0 56px; background: radial-gradient(800px 400px at 85% 0%, rgba(25,199,230,.10), transparent 70%)">
<div class="wrap hero-grid">
<div style="display: flex; flex-direction: column; gap: 22px">
<span style="align-self: flex-start; display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; border: 1px solid #23394A; color: #9FE8F5; font-size: 13.5px; font-weight: 800">منصة بيع أونلاين لكل أنواع المحلات</span>
<h1 class="hero-title" style="margin: 0; font-size: 56px; line-height: 1.25; font-weight: 900">محلك أونلاين،<br><span style="color: #08DEF4">والشغل علينا.</span></h1>
<p style="margin: 0; font-size: 19px; line-height: 1.9; color: #B7C6D2; max-width: 560px">متجر بإسم محلك، طلبات بتوصلك لايف، بوت ذكي بيدير صفحات السوشال، وعجلة هدايا بترجّعلك الزباين. كله من لوحة وحدة على جوالك.</p>
<div style="display: flex; flex-wrap: wrap; gap: 12px">
<a href="{{ $whatsapp ?: '#join' }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 10px; min-height: 54px; padding: 0 26px; border-radius: 14px; background: #08DEF4; color: #04161B; font-weight: 900; font-size: 17px; text-decoration: none">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"></path><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"></path></svg>
سجّل محلك على واتساب</a>
<a href="{{ $demoUrl }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; min-height: 54px; padding: 0 24px; border-radius: 14px; border: 1px solid #2A3D4E; color: #EEF4F8; font-weight: 800; font-size: 16px; text-decoration: none">جرّب كزبون</a>
</div>
<div style="display: flex; flex-wrap: wrap; gap: 18px; color: #9FB0BF; font-size: 14px; font-weight: 700">
<span style="display: inline-flex; align-items: center; gap: 6px"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3EE0A0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"></path></svg>ما بدها خبرة تقنية</span>
<span style="display: inline-flex; align-items: center; gap: 6px"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3EE0A0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"></path></svg>بنجهّزلك المتجر</span>
<span style="display: inline-flex; align-items: center; gap: 6px"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3EE0A0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"></path></svg>عربي وعبري وإنجليزي</span>
</div>
</div>

<div style="display: flex; justify-content: center">
<div style="width: 320px; border-radius: 40px; padding: 12px; background: #05090E; border: 1px solid #22303E; box-shadow: 0 40px 90px rgba(0,0,0,.5)">
<div style="border-radius: 30px; background: #0E1620; padding: 18px 14px 20px; display: flex; flex-direction: column; gap: 12px">
<div style="display: flex; align-items: center; justify-content: space-between">
<b style="font-size: 15px">الطلبات</b>
<span style="display: inline-flex; align-items: center; gap: 6px; color: #3EE0A0; font-size: 11.5px; font-weight: 800"><span style="width: 7px; height: 7px; border-radius: 50%; background: #3EE0A0"></span>لايف</span>
</div>
<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px">
<div style="background: #121E2A; border-radius: 10px; padding: 8px; display: grid; gap: 2px"><small style="color: #9FB0BF; font-size: 10.5px">جديدة</small><b style="color: #3EE0A0; font-size: 20px">3</b></div>
<div style="background: #121E2A; border-radius: 10px; padding: 8px; display: grid; gap: 2px"><small style="color: #9FB0BF; font-size: 10.5px">بالتحضير</small><b style="color: #FFC94D; font-size: 20px">2</b></div>
<div style="background: #121E2A; border-radius: 10px; padding: 8px; display: grid; gap: 2px"><small style="color: #9FB0BF; font-size: 10.5px">جاهزة</small><b style="color: #C9A8FF; font-size: 20px">1</b></div>
</div>
<div style="border: 1px solid rgba(62,224,160,.45); background: #102019; border-radius: 14px; padding: 11px; display: grid; gap: 8px">
<div style="display: flex; align-items: center; gap: 8px"><b style="font-size: 14px">#A2F9</b><span style="padding: 1px 8px; border-radius: 999px; background: rgba(255,178,107,.14); color: #FFB26B; font-size: 11px; font-weight: 800">توصيل</span><span style="margin-inline-start: auto; padding: 2px 8px; border-radius: 8px; background: rgba(62,224,160,.12); color: #3EE0A0; font-size: 11px; font-weight: 800">جديد</span></div>
<div style="color: #9FB0BF; font-size: 11.5px; display: flex; gap: 6px"><span>3 وجبات</span><span>·</span><span>8:42 م</span><b style="margin-inline-start: auto; color: #FFC94D; font-size: 13px">96.00 ₪</b></div>
<small style="color: #9FB0BF; font-size: 10.5px">اختار وقت التحضير وابدأ:</small>
<div style="display: flex; gap: 5px">
<span style="flex: 1; text-align: center; padding: 6px 0; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">10</span>
<span style="flex: 1; text-align: center; padding: 6px 0; border-radius: 9px; border: 1px solid #FFC94D; color: #FFC94D; font-size: 12px; font-weight: 800">20</span>
<span style="flex: 1; text-align: center; padding: 6px 0; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">30</span>
<span style="flex: 1; text-align: center; padding: 6px 0; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">45</span>
</div>
</div>
<div style="border: 1px solid #22303E; background: #111A24; border-radius: 14px; padding: 11px; display: grid; gap: 8px">
<div style="display: flex; align-items: center; gap: 8px"><b style="font-size: 14px">#K7Q1</b><span style="padding: 1px 8px; border-radius: 999px; background: rgba(25,199,230,.12); color: #7FE3F5; font-size: 11px; font-weight: 800">استلام</span><span style="margin-inline-start: auto; padding: 2px 8px; border-radius: 8px; background: rgba(255,201,77,.12); color: #FFC94D; font-size: 11px; font-weight: 800">قيد التحضير</span></div>
<div style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 10px; border: 1px solid rgba(255,201,77,.35); background: rgba(255,201,77,.07); color: #FFC94D"><b style="font-size: 18px; direction: ltr">12:48</b><small style="font-size: 11px">باقي للتجهيز</small></div>
<span style="text-align: center; padding: 9px 0; border-radius: 10px; background: linear-gradient(90deg,#3EE0A0,#19C7E6); color: #04161B; font-weight: 900; font-size: 13px">جاهز</span>
</div>
</div>
</div>
</div>
</div>
</section>

<section style="padding: 40px 0 64px">
<div class="wrap" style="display: flex; flex-direction: column; gap: 22px">
<p style="margin: 0; text-align: center; color: #9FB0BF; font-size: 15px; font-weight: 800">أوزمان شغّال لكل أنواع المحلات</p>
<div class="types">
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3v18M4 3v6a3 3 0 0 0 6 0V3M17 3c-2 2-2 6 0 8v10"></path></svg><b style="font-size: 13.5px">مطاعم</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 10h6v11H9zM10 10V6l4-3v7"></path></svg><b style="font-size: 13.5px">مكياج</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3l4 3 4-3 5 4-3 4-2-1v11H8V10l-2 1-3-4z"></path></svg><b style="font-size: 13.5px">ملابس</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path></svg><b style="font-size: 13.5px">إلكترونيات</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13h16v8H4zM6 13a6 6 0 0 1 12 0M12 3v2"></path></svg><b style="font-size: 13.5px">حلويات</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 18v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6M4 15h16M6 18v3M18 18v3M7 10V6h10v4"></path></svg><b style="font-size: 13.5px">أثاث وأجهزة</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5c-3-3-8-1-8 4 0 6 3 12 5 12s2-5 3-5 1 5 3 5 5-6 5-12c0-5-5-7-8-4z"></path></svg><b style="font-size: 13.5px">عيادات</b></div>
<div style="display: grid; justify-items: center; gap: 8px; padding: 16px 6px; border-radius: 16px; background: #0F1822; border: 1px solid #1B2734"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l18-7-7 18-2-8z"></path></svg><b style="font-size: 13.5px">خدمات</b></div>
</div>
</div>
</section>

<section id="how" style="padding: 64px 0; background: #0D151E; border-block: 1px solid #172230">
<div class="wrap" style="display: flex; flex-direction: column; gap: 36px">
<div style="display: grid; gap: 10px; text-align: center">
<h2 class="section-title" style="margin: 0; font-size: 36px; font-weight: 900">بتبلّش بثلاث خطوات</h2>
<p style="margin: 0; color: #9FB0BF; font-size: 17px">إحنا بنعمل الشغل التقني، وإنت بتركّز على محلك.</p>
</div>
<div class="steps">
<div style="padding: 26px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px">
<span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(25,199,230,.12); color: #08DEF4; display: grid; place-items: center; font-weight: 900; font-size: 20px">1</span>
<h3 style="margin: 0; font-size: 21px">بنجهّزلك متجرك</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15.5px; line-height: 1.8">بإسم محلك ولوجوك وألوانك، مع رابط خاص فيك بتنشره وين ما بدك.</p>
</div>
<div style="padding: 26px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px">
<span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(25,199,230,.12); color: #08DEF4; display: grid; place-items: center; font-weight: 900; font-size: 20px">2</span>
<h3 style="margin: 0; font-size: 21px">بتضيف منتجاتك</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15.5px; line-height: 1.8">صورة وسعر ووصف من الجوال، وبتعدّل وقت ما بدك.</p>
</div>
<div style="padding: 26px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px">
<span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(25,199,230,.12); color: #08DEF4; display: grid; place-items: center; font-weight: 900; font-size: 20px">3</span>
<h3 style="margin: 0; font-size: 21px">بتستقبل طلبات</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15.5px; line-height: 1.8">الطلب بيوصلك لايف مع صوت وإشعار، حتى والشاشة مسكّرة.</p>
</div>
</div>
</div>
</section>

<section id="features" style="padding: 72px 0">
<div class="wrap" style="display: flex; flex-direction: column; gap: 28px">
<div style="display: grid; gap: 10px; text-align: center">
<h2 class="section-title" style="margin: 0; font-size: 36px; font-weight: 900">كل اللي بيحتاجه محلك ليبيع أونلاين</h2>
<p style="margin: 0; color: #9FB0BF; font-size: 17px">مش بس متجر. أدوات بتجيبلك زباين وبترجّعهم.</p>
</div>

<div class="feat-main">
<div style="padding: 28px; border-radius: 22px; background: #111A24; border: 1px solid #1F2C3A; display: flex; flex-direction: column; gap: 16px">
<span style="align-self: flex-start; padding: 4px 12px; border-radius: 999px; background: rgba(255,201,77,.12); color: #FFC94D; font-size: 12.5px; font-weight: 900">الأقوى عنا</span>
<h3 style="margin: 0; font-size: 26px; font-weight: 900">بوت ذكي بيدير صفحات محلك</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 16px; line-height: 1.85">بتختار المنتج، والبوت بيكتب البوست بالذكاء الاصطناعي، وبيختار أحسن وقت لمتابعينك، وبينشر على إنستغرام وفيسبوك وتيك توك. ومع كل بوست رابط الطلب.</p>
<div style="border-radius: 16px; background: #0B121A; border: 1px solid #1F2C3A; padding: 14px; display: grid; gap: 10px">
<div style="display: flex; align-items: center; gap: 8px; color: #9FB0BF; font-size: 12.5px; font-weight: 800"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.8 4.7L18.5 9l-4.7 1.8L12 15.5l-1.8-4.7L5.5 9l4.7-1.3z"></path></svg>البوت كتب:</div>
<p style="margin: 0; font-size: 14.5px; line-height: 1.8; color: #EEF4F8">وصلت التشكيلة الجديدة! ألوان الشتا صارت عنا، والكمية محدودة. اطلب هلقيت والتوصيل لباب البيت.</p>
<div style="display: flex; flex-wrap: wrap; gap: 6px">
<span style="padding: 5px 10px; border-radius: 9px; border: 1px solid #FFC94D; color: #FFC94D; font-size: 12px; font-weight: 800">أفضل وقت: الخميس 8 م</span>
<span style="padding: 5px 10px; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">إنستغرام</span>
<span style="padding: 5px 10px; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">فيسبوك</span>
<span style="padding: 5px 10px; border-radius: 9px; border: 1px solid #263646; font-size: 12px; font-weight: 800">تيك توك</span>
</div>
</div>
</div>

<div style="padding: 28px; border-radius: 22px; background: #111A24; border: 1px solid #1F2C3A; display: flex; flex-direction: column; gap: 16px">
<span style="align-self: flex-start; padding: 4px 12px; border-radius: 999px; background: rgba(62,224,160,.12); color: #3EE0A0; font-size: 12.5px; font-weight: 900">ولا طلب بيضيع</span>
<h3 style="margin: 0; font-size: 26px; font-weight: 900">لوحة طلبات لايف</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 16px; line-height: 1.85">الطلب بيوصلك مع صوت تنبيه. بتختار وقت التحضير بكبسة، والزبون بيشوف العداد عنده، وبيوصله إشعار لما يجهز. وبتغيّر الحالة وقت ما بدك.</p>
<div style="border-radius: 16px; background: #0B121A; border: 1px solid #1F2C3A; padding: 14px; display: grid; gap: 10px">
<div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px">
<div style="display: grid; gap: 6px; text-align: center"><span style="height: 5px; border-radius: 5px; background: #3EE0A0"></span><small style="color: #3EE0A0; font-size: 12px; font-weight: 800">جديد</small></div>
<div style="display: grid; gap: 6px; text-align: center"><span style="height: 5px; border-radius: 5px; background: #3EE0A0"></span><small style="color: #3EE0A0; font-size: 12px; font-weight: 800">تحضير</small></div>
<div style="display: grid; gap: 6px; text-align: center"><span style="height: 5px; border-radius: 5px; background: #FFC94D"></span><small style="color: #EEF4F8; font-size: 12px; font-weight: 800">جاهز</small></div>
<div style="display: grid; gap: 6px; text-align: center"><span style="height: 5px; border-radius: 5px; background: #23313F"></span><small style="color: #9FB0BF; font-size: 12px; font-weight: 800">تسليم</small></div>
</div>
<div style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: rgba(25,199,230,.08); color: #BDF3FC; font-size: 13.5px; font-weight: 700"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"></path></svg>للزبون: «طلبك جاهز» وصل على جواله</div>
</div>
</div>
</div>

<div class="feat-small">
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6L5.6 18.4"></path></svg>
<h3 style="margin: 0; font-size: 19px">عجلة هدايا ونقاط</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">الزبون بيلف ويربح خصم أو هدية، وبيجمع نقاط مع كل طلب، فبيرجع يطلب.</p>
</div>
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"></rect><path d="M10 18h4"></path></svg>
<h3 style="margin: 0; font-size: 19px">تطبيق خاص لمحلك</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">بتثبّته على الجوال بكبسة، والطلبات والإشعارات بتوصلك حتى والشاشة مسكّرة.</p>
</div>
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="17" r="3"></circle><circle cx="18" cy="17" r="3"></circle><path d="M9 17h6l-3-8h4M6 17l3-6h5"></path></svg>
<h3 style="margin: 0; font-size: 19px">مندوبين توصيل</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">كل مندوب إله صفحة، والطلب بيوصله أول ما يجهز، والزبون بيعرف وقت الوصول.</p>
</div>
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M14 14h3v3M21 14v7h-7"></path></svg>
<h3 style="margin: 0; font-size: 19px">طاولات بـQR</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">الزبون بيمسح الكود على الطاولة وبيطلب، والطلب بيوصل المطبخ برقم الطاولة.</p>
</div>
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2 .7 3 2.8 3 6"></path></svg>
<h3 style="margin: 0; font-size: 19px">مروّجين لمحلك</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">ناس بيروّجوا لمنتجاتك على صفحاتهم، وبيجيبولك زيارات وزباين جداد.</p>
</div>
<div style="padding: 24px; border-radius: 20px; background: #111A24; border: 1px solid #1F2C3A; display: grid; gap: 10px; align-content: start">
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#08DEF4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7M16 6l-4-4-4 4M12 2v13"></path></svg>
<h3 style="margin: 0; font-size: 19px">لايك ومشاركة</h3>
<p style="margin: 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">الزبون بيشارك أي منتج على الواتساب وكل المنصات، ورفقاته بيفوتوا على متجرك.</p>
</div>
</div>
</div>
</section>

<section id="demo" style="padding: 0 0 72px">
<div class="wrap">
<div style="border-radius: 26px; padding: 40px; background: linear-gradient(135deg, #10202B, #0E1620); border: 1px solid #23394A; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px">
<div style="display: grid; gap: 8px; max-width: 620px">
<h2 class="section-title" style="margin: 0; font-size: 30px; font-weight: 900">جرّبه بنفسك قبل ما تقرر</h2>
<p style="margin: 0; color: #B7C6D2; font-size: 16.5px; line-height: 1.8">افتح متجر تجريبي واطلب كأنك زبون، وشوف كيف الطلب بيوصل للوحة المحل.</p>
</div>
<a href="{{ $demoUrl }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; min-height: 54px; padding: 0 26px; border-radius: 14px; border: 1px solid #08DEF4; color: #08DEF4; font-weight: 900; font-size: 16px; text-decoration: none">افتح المتجر التجريبي</a>
</div>
</div>
</section>

<section id="faq" style="padding: 64px 0; background: #0D151E; border-block: 1px solid #172230">
<div class="wrap" style="display: flex; flex-direction: column; gap: 28px">
<h2 class="section-title" style="margin: 0; text-align: center; font-size: 36px; font-weight: 900">أسئلة بتتكرر</h2>
<div class="faq">
<details style="padding: 18px 20px; border-radius: 16px; background: #111A24; border: 1px solid #1F2C3A"><summary style="font-size: 17px; font-weight: 800">قديش بيكلّف؟</summary><p style="margin: 10px 0 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">السعر بيعتمد على نوع محلك وحجم شغلك. احكي معنا على واتساب وبنعطيك العرض المناسب، وبنجهّزلك المتجر قبل ما تدفع.</p></details>
<details style="padding: 18px 20px; border-radius: 16px; background: #111A24; border: 1px solid #1F2C3A"><summary style="font-size: 17px; font-weight: 800">بدي خبرة بالكمبيوتر؟</summary><p style="margin: 10px 0 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">لا. إحنا بنجهّزلك المتجر، وكل إشي بعدها بيشتغل من الجوال بكبسات بسيطة.</p></details>
<details style="padding: 18px 20px; border-radius: 16px; background: #111A24; border: 1px solid #1F2C3A"><summary style="font-size: 17px; font-weight: 800">وين بتوصلني الطلبات؟</summary><p style="margin: 10px 0 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">على لوحة محلك وتطبيقه على الجوال، مع صوت وإشعار، حتى لو الشاشة مسكّرة.</p></details>
<details style="padding: 18px 20px; border-radius: 16px; background: #111A24; border: 1px solid #1F2C3A"><summary style="font-size: 17px; font-weight: 800">البوت بينشر لحاله بدون ما أشوف؟</summary><p style="margin: 10px 0 0; color: #B7C6D2; font-size: 15px; line-height: 1.8">لا، إنت بتشوف البوست وبتعدّله وبتختار تنشر هلقيت أو بوقت محدد. البوت بيساعد، والقرار إلك.</p></details>
</div>
</div>
</section>

<section id="join" style="padding: 80px 0">
<div class="wrap" style="display: grid; justify-items: center; gap: 18px; text-align: center">
<h2 class="section-title" style="margin: 0; font-size: 40px; font-weight: 900">جاهز تفتح محلك أونلاين؟</h2>
<p style="margin: 0; color: #B7C6D2; font-size: 18px">احكي معنا على واتساب، وبنجهّزلك كل إشي.</p>
<a href="{{ $whatsapp ?: '#join' }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 10px; min-height: 58px; padding: 0 32px; border-radius: 16px; background: #08DEF4; color: #04161B; font-weight: 900; font-size: 18px; text-decoration: none">سجّل محلك على واتساب</a>
@if($whatsappNumber)<small style="color: #9FB0BF; font-size: 14px" dir="ltr">+{{ $whatsappNumber }}</small>@endif
</div>
</section>

<footer style="border-top: 1px solid #172230; padding: 26px 0">
<div class="wrap" style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; color: #9FB0BF; font-size: 14px">
<span>أوزمان — <a href="{{ url('/') }}" style="text-decoration: none">ozman.online</a> · <a href="{{ route('policies.privacy') }}" style="text-decoration: none">الخصوصية</a></span>
<a href="#top" style="text-decoration: none">لفوق</a>
</div>
</footer>
</div>
</body>
</html>
