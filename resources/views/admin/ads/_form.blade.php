@php
    $isEdit = isset($ad);
@endphp

<section class="form-section">
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-speakerphone" aria-hidden="true"></i></div>
        <div>
            <h2>بيانات الإعلان</h2>
            <p>العنوان، المتجر، النوع، والوسائط الخاصة بالإعلان.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="shop_id"><i class="ti ti-building-store" aria-hidden="true"></i>المتجر</label>
            <select id="shop_id" name="shop_id">
                <option value="">إعلان عام</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" @selected(old('shop_id', $ad->shop_id ?? $selectedShopId ?? '') == $shop->id)>{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="type"><i class="ti ti-list" aria-hidden="true"></i>نوع الإعلان</label>
            <select id="type" name="type" required>
                <option value="image" @selected(old('type', $ad->type ?? 'image') === 'image')>صورة</option>
                <option value="video" @selected(old('type', $ad->type ?? '') === 'video')>فيديو</option>
                <option value="youtube" @selected(old('type', $ad->type ?? '') === 'youtube')>يوتيوب</option>
            </select>
        </div>

        <div class="form-group full">
            <label class="form-label" for="title"><i class="ti ti-tag" aria-hidden="true"></i>عنوان الإعلان</label>
            <input type="text" id="title" name="title" value="{{ old('title', $ad->title ?? '') }}" required>
        </div>

        <div class="form-group full">
            <label class="form-label" for="description"><i class="ti ti-align-right" aria-hidden="true"></i>الوصف</label>
            <textarea id="description" name="description">{{ old('description', $ad->description ?? '') }}</textarea>
        </div>

        <div class="form-group full" id="fileMediaGroup">
            <label class="upload-box">
                <input type="file" name="media_file" id="media_file" accept="image/*,video/*">
                <span class="card-icon"><i class="ti ti-upload" aria-hidden="true"></i></span>
                <span>
                    <span class="card-title">ملف الإعلان</span>
                    <span class="card-sub">{{ $isEdit ? 'اتركه فارغاً للاحتفاظ بالملف الحالي' : 'ارفع صورة أو فيديو حسب النوع' }}</span>
                </span>
            </label>
        </div>

        <div class="form-group full" id="youtubeMediaGroup">
            <label class="form-label" for="media"><i class="ti ti-brand-youtube" aria-hidden="true"></i>رابط يوتيوب</label>
            <input type="url" id="media" name="media" value="{{ old('media', ($ad->type ?? '') === 'youtube' ? ($ad->media ?? '') : '') }}" placeholder="https://youtube.com/watch?v=...">
        </div>

        @if($isEdit && $ad->media)
            <div class="form-group full">
                <div class="current-media">
                    <span class="label">الوسيط الحالي</span>
                    <span class="value">{{ $ad->media }}</span>
                </div>
            </div>
        @endif
    </div>
</section>

<section class="form-section">
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-adjustments" aria-hidden="true"></i></div>
        <div>
            <h2>الإعدادات</h2>
            <p>مدة العرض، الترتيب، وحالة التفعيل.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="duration"><i class="ti ti-clock" aria-hidden="true"></i>المدة بالثواني</label>
            <input type="number" min="1" max="3600" id="duration" name="duration" value="{{ old('duration', $ad->duration ?? 10) }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="sort_order"><i class="ti ti-sort-ascending" aria-hidden="true"></i>الترتيب</label>
            <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $ad->sort_order ?? 0) }}">
        </div>

        <div class="form-group full">
            <div class="switch-card">
                <div class="card-copy">
                    <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                    <span>
                        <span class="card-title">تفعيل الإعلان</span>
                        <span class="card-sub">الإعلان النشط يظهر داخل واجهات العرض.</span>
                    </span>
                </div>
                <label class="switch" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $ad->is_active ?? true))>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
</section>

<script>
    const typeSelect = document.getElementById('type');
    const fileMediaGroup = document.getElementById('fileMediaGroup');
    const youtubeMediaGroup = document.getElementById('youtubeMediaGroup');
    const mediaFile = document.getElementById('media_file');

    function syncMediaFields() {
        const isYoutube = typeSelect.value === 'youtube';
        youtubeMediaGroup.style.display = isYoutube ? '' : 'none';
        fileMediaGroup.style.display = isYoutube ? 'none' : '';
        mediaFile.accept = typeSelect.value === 'image' ? 'image/*' : 'video/*';
    }

    typeSelect.addEventListener('change', syncMediaFields);
    syncMediaFields();
</script>
