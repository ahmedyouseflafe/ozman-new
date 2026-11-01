{{-- Optional photo field; the form using it needs enctype="multipart/form-data". Pass $user to show their current photo. --}}
@php($currentAvatar = isset($user) ? $user->avatarUrl() : null)
<div class="avatar-picker {{ $errors->has('avatar') ? 'has-error' : '' }}" data-avatar-picker
    data-i18n="{{ json_encode(['type' => __('الصورة لازم تكون JPG أو PNG أو WebP.'), 'size' => __('حجم الصورة لازم يكون 5 ميجا أو أقل.')], JSON_UNESCAPED_UNICODE) }}">
    <label class="avatar-picker-circle">
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input aria-describedby="avatarPickerHint avatarPickerError">
        <img data-avatar-preview alt="" @if($currentAvatar) src="{{ $currentAvatar }}" @else hidden @endif>
        <span class="avatar-picker-empty" data-avatar-empty @if($currentAvatar) hidden @endif>
            <svg viewBox="0 0 24 24" width="30" height="30" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1.4-2h6.2l1.4 2h2A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
            <span>{{ $label ?? __('أضف صورتك (اختياري)') }}</span>
        </span>
    </label>
    <button type="button" class="avatar-picker-remove" data-avatar-remove aria-label="{{ __('إزالة الصورة') }}" title="{{ __('إزالة الصورة') }}" hidden>×</button>
    <small id="avatarPickerHint">{{ $currentAvatar ? __('اضغط على الصورة لتغييرها.') : __('JPG أو PNG أو WebP، حتى 5 ميجا.') }}</small>
    <p class="avatar-picker-error" id="avatarPickerError" data-avatar-error role="alert">@error('avatar'){{ $message }}@enderror</p>
</div>
@once
<script src="{{ asset('customer-avatar.js') }}?v={{ filemtime(public_path('customer-avatar.js')) }}" defer></script>
@endonce
