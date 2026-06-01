@php($isEdit = isset($screen))

<section class="form-section">
    <div class="section-head">
        <div class="section-icon"><i class="ti ti-device-tv" aria-hidden="true"></i></div>
        <div>
            <h2>بيانات الشاشة</h2>
            <p>هذا المحتوى يظهر في الشاشة الرئيسية العامة.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="type">نوع المحتوى</label>
            <select id="type" name="type" required>
                <option value="image" @selected(old('type', $screen->type ?? 'image') === 'image')>صورة</option>
                <option value="video" @selected(old('type', $screen->type ?? '') === 'video')>فيديو</option>
                <option value="youtube" @selected(old('type', $screen->type ?? '') === 'youtube')>يوتيوب</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="duration">مدة العرض بالثواني</label>
            <input type="number" min="1" max="3600" id="duration" name="duration" value="{{ old('duration', $screen->duration ?? 10) }}">
        </div>

        <div class="form-group full">
            <label class="form-label" for="title">العنوان</label>
            <input type="text" id="title" name="title" value="{{ old('title', $screen->title ?? '') }}" required>
        </div>

        <div class="form-group full" id="fileMediaGroup">
            <label class="upload-box">
                <input type="file" name="media_file" id="media_file" accept="image/*,video/*">
                <span class="card-icon"><i class="ti ti-upload" aria-hidden="true"></i></span>
                <span>
                    <span class="card-title">ملف الشاشة</span>
                    <span class="card-sub">{{ $isEdit ? 'اتركه فارغاً للاحتفاظ بالملف الحالي' : 'ارفع صورة أو فيديو حسب النوع' }}</span>
                </span>
            </label>
        </div>

        <div class="form-group full" id="youtubeMediaGroup">
            <label class="form-label" for="media">رابط يوتيوب</label>
            <input type="url" id="media" name="media" value="{{ old('media', ($screen->type ?? '') === 'youtube' ? ($screen->media ?? '') : '') }}" placeholder="https://youtube.com/watch?v=...">
        </div>

        @if($isEdit && $screen->media)
            <div class="form-group full">
                <div class="detail-box">
                    <span class="label">الوسيط الحالي</span>
                    <span class="value">{{ $screen->media }}</span>
                </div>
            </div>
        @endif

        <div class="form-group full">
            <div class="switch-card">
                <div class="card-copy">
                    <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                    <span>
                        <span class="card-title">تفعيل الشاشة</span>
                        <span class="card-sub">الشاشات النشطة تظهر في العرض الرئيسي.</span>
                    </span>
                </div>
                <label class="switch" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $screen->is_active ?? true))>
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
