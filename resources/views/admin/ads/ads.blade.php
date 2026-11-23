@php
    $adsList = collect($ads ?? []);
    $adsCount = $adsCount ?? $adsList->count();
    $imageAdsCount = $imageAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['صورة', 'image']))->count();
    $videoAdsCount = $videoAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['فيديو', 'video']))->count();
    $youtubeAdsCount = $youtubeAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['يوتيوب', 'youtube']))->count();
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الإعلانات — Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.ads.ads')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => 'الإعلانات'])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">✨ إدارة إعلانات Ozman ✨ صور وفيديوهات ويوتيوب بتجربة عرض فاخرة ✨</span>
                            <span class="welcome-msg">✨ {{ $adsCount }} إعلان داخل النظام ✨ تحكم بالترتيب والحالة والمتجر من مكان واحد ✨</span>
                            <span class="welcome-msg">✨ إدارة إعلانات Ozman ✨ صور وفيديوهات ويوتيوب بتجربة عرض فاخرة ✨</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-speakerphone" aria-hidden="true"></i>
                        <span>الإعلانات</span>
                    </div>
                </div>

                <div class="page-header-row">
                    <div>
                        <h1>الإعلانات</h1>
                        <p>إدارة إعلانات المتاجر وطريقة ظهورها داخل واجهة العرض.</p>
                    </div>
                    <a href="{{ route('ads.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        إعلان جديد
                    </a>
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--primary-color)">
                        <div class="stat-label">إجمالي الإعلانات</div>
                        <div class="stat-val">{{ $adsCount }}</div>
                        <i class="ti ti-speakerphone stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: #00ffd5">
                        <div class="stat-label">صور</div>
                        <div class="stat-val">{{ $imageAdsCount }}</div>
                        <i class="ti ti-photo stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">فيديوهات</div>
                        <div class="stat-val">{{ $videoAdsCount }}</div>
                        <i class="ti ti-video stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--yellow)">
                        <div class="stat-label">يوتيوب</div>
                        <div class="stat-val">{{ $youtubeAdsCount }}</div>
                        <i class="ti ti-brand-youtube stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة الإعلانات</h3>
                        <div class="filter-row">
                            <div class="type-filter" id="adTypeFilter">
                                <input type="hidden" id="adTypeValue" value="">
                                <button type="button" class="type-filter-btn" id="adTypeButton" aria-expanded="false">
                                    <span id="adTypeLabel">كل الأنواع</span>
                                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                                </button>
                                <div class="type-filter-menu" id="adTypeMenu">
                                    <button type="button" class="type-filter-option active" data-value="">كل الأنواع</button>
                                    <button type="button" class="type-filter-option" data-value="صورة">صورة</button>
                                    <button type="button" class="type-filter-option" data-value="فيديو">فيديو</button>
                                    <button type="button" class="type-filter-option" data-value="يوتيوب">يوتيوب</button>
                                </div>
                            </div>
                            <div class="input-wrap">
                                <i class="ti ti-search search-icon" aria-hidden="true"></i>
                                <input class="search-inp" id="adSearch" placeholder="بحث...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="adsTable">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>الوصف</th>
                                    <th>النوع</th>
                                    <th>المتجر</th>
                                    <th>المدة (ث)</th>
                                    <th>الترتيب</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adsList as $ad)
                                    @php
                                        $typeLabel = data_get($ad, 'type_label', data_get($ad, 'type', '-'));
                                    @endphp
                                    <tr data-type="{{ $typeLabel }}">
                                        <td class="ad-title">{{ data_get($ad, 'title', '-') }}</td>
                                        <td>{{ data_get($ad, 'description', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($ad, 'type_class', 'tag-blue') }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td>{{ data_get($ad, 'shop_name', '-') }}</td>
                                        <td>{{ data_get($ad, 'duration', '-') }}</td>
                                        <td>{{ data_get($ad, 'order', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($ad, 'status_class', 'tag-g') }}">
                                                {{ data_get($ad, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="ad-actions">
                                                <a href="{{ route('ads.show', $ad) }}" class="action-btn" aria-label="عرض">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                <a href="{{ route('ads.edit', $ad) }}" class="action-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الإعلان؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <button class="action-btn" style="margin-left:6px" aria-label="تعديل">
                                                <i class="ti ti-edit" aria-hidden="true"></i>
                                            </button>
                                            <button class="action-btn" aria-label="حذف">
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="ti ti-speakerphone-off" aria-hidden="true"></i>
                                            لا توجد إعلانات حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @viewScript('admin.ads.ads')
</body>

</html>
