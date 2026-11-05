@extends('promoters.layout')
@section('title', ($listing->exists ? __('تعديل منتج') : __('بيع منتج')).' · '.__('المتجر الحر'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')
<section class="panel market-form">
<span class="eyebrow">{{ __('المتجر الحر') }}</span>
<h1>{{ $listing->exists ? __('تعديل المنتج') : __('اعرض منتجك للبيع') }}</h1>
@unless($listing->exists)<p class="market-lead">{{ __('منتجك بيظهر بالمتجر فوراً. لما حدا يطلبه بيوصلك إيميل وبتلاقي الطلب بـ"طلبات وصلتني".') }}</p>@endunless
<form method="post" enctype="multipart/form-data" action="{{ $listing->exists ? route('market.update', $listing) : route('market.store') }}" class="stack" data-upload>@csrf @if($listing->exists) @method('PUT') @endif
    <fieldset class="market-photos"><legend>{{ __('الصور') }} <small>{{ __('لحد :max صور. أول صورة هي الغلاف.', ['max' => config('market.max_images', 6)]) }}</small></legend>
        @if($listing->exists && $listing->images->isNotEmpty())<div class="market-photo-list">@foreach($listing->images as $image)<label class="market-photo"><img src="{{ route('market.image', $image) }}" alt=""><span><input type="checkbox" name="remove_images[]" value="{{ $image->id }}"> {{ __('احذف') }}</span></label>@endforeach</div>@endif
        <label class="upload-area">{{ $listing->exists ? __('أضف صور جديدة') : __('اختار صور المنتج') }}<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple @unless($listing->exists) required @endunless data-photos><small>{{ __('JPG أو PNG أو WebP، حتى 8 ميجا للصورة.') }}</small></label>
        <div class="market-photo-preview" data-photo-preview></div>
    </fieldset>
    <label>{{ __('اسم المنتج') }}<input name="title" maxlength="120" minlength="3" value="{{ old('title', $listing->title) }}" required placeholder="{{ __('مثلاً: جاكيت جلد مقاس M') }}"></label>
    <div class="market-form-row">
        <label>{{ __('السعر (₪)') }}<input name="price" type="number" step="0.5" min="0.5" max="1000000" inputmode="decimal" value="{{ old('price', $listing->price !== null ? rtrim(rtrim((string) $listing->price, '0'), '.') : '') }}" required></label>
        <label>{{ __('الكمية') }}<input name="quantity" type="number" min="{{ $listing->exists ? 0 : 1 }}" max="999" value="{{ old('quantity', $listing->quantity ?? 1) }}" required></label>
    </div>
    <div class="market-form-row">
        <label>{{ __('القسم') }}<select name="category" required><option value="">{{ __('اختار القسم') }}</option>@foreach(config('market.categories') as $key => $label)<option value="{{ $key }}" @selected(old('category', $listing->category) === $key)>{{ __($label) }}</option>@endforeach</select></label>
        <label>{{ __('المدينة') }}<input name="city" maxlength="60" list="marketCities" value="{{ old('city', $listing->city) }}" required></label>
    </div>
    <fieldset class="account-type"><legend>{{ __('حالة المنتج') }}</legend>@foreach(config('market.conditions') as $key => $label)<label><input type="radio" name="condition" value="{{ $key }}" @checked(old('condition', $listing->condition) === $key) required><span><b>{{ __($label) }}</b></span></label>@endforeach</fieldset>
    <label>{{ __('الوصف') }}<textarea name="description" rows="5" maxlength="3000" placeholder="{{ __('المقاس، اللون، الحالة، سبب البيع، طريقة التسليم…') }}">{{ old('description', $listing->description) }}</textarea></label>
    <datalist id="marketCities">@foreach(config('market.cities') as $city)<option value="{{ $city }}">@endforeach</datalist>
    <button class="button" type="submit">{{ $listing->exists ? __('حفظ التعديلات') : __('انشر المنتج') }}</button>
    <p class="upload-status" role="status"></p>
</form>
</section>
@endsection
@section('scripts')
<script>
document.querySelectorAll('[data-photos]').forEach(input=>{const box=document.querySelector('[data-photo-preview]');input.addEventListener('change',()=>{box.innerHTML='';[...input.files].slice(0,{{ (int) config('market.max_images', 6) }}).forEach(f=>{const img=document.createElement('img');img.alt='';img.src=URL.createObjectURL(f);box.appendChild(img);});});});
document.querySelectorAll('[data-upload]').forEach(form=>form.addEventListener('submit',()=>{const b=form.querySelector('button[type=submit]');b.disabled=true;b.textContent='{{ __('جارٍ الرفع…') }}';const s=form.querySelector('.upload-status');if(s)s.textContent='{{ __('انتظر اكتمال رفع الصور، ولا تسكّر الصفحة.') }}';}));
window.addEventListener('pageshow',()=>document.querySelectorAll('[data-upload] button[type=submit]').forEach(b=>{b.disabled=false;}));
</script>
@endsection
