<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Shop;
use App\Services\VideoProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    public function index(): View
    {
        $ads = Advertisement::query()
            ->with('shop')
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(function (Advertisement $ad) {
                $ad->type_label = $this->typeLabel($ad->type);
                $ad->type_class = $ad->type === 'image' ? 'tag-blue' : ($ad->type === 'video' ? 'tag-y' : 'tag-r');
                $ad->status_label = $ad->is_active ? 'نشط' : 'غير نشط';
                $ad->status_class = $ad->is_active ? 'tag-g' : 'tag-r';
                $ad->shop_name = $ad->shop?->name ?? 'عام';
                $ad->order = $ad->sort_order;

                return $ad;
            });

        return view('admin.ads.ads', [
            'ads' => $ads,
            'adsCount' => $ads->count(),
            'imageAdsCount' => $ads->where('type', 'image')->count(),
            'videoAdsCount' => $ads->where('type', 'video')->count(),
            'youtubeAdsCount' => $ads->where('type', 'youtube')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.ads.ads_create', [
            'shops' => $this->accessibleShops(),
            'selectedShopId' => $request->integer('shop_id') ?: $this->firstAccessibleShopId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $data['duration'] = $data['duration'] ?? 10;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['media'] = $this->resolveMedia($request);

        $ad = Advertisement::create($data);
        if ($ad->type === 'video' && $request->hasFile('media_file')) {
            app(VideoProcessingService::class)->queue($ad);
        }

        $this->notifySuperAdmin(
            'advertisement_created',
            $ad,
            'تمت إضافة إعلان جديد',
            "المتجر {$ad->shop?->name} أضاف إعلان: {$ad->title}",
            route('ads.show', $ad)
        );

        return redirect()
            ->route('ads')
            ->with('status', 'تمت إضافة الإعلان بنجاح.');
    }

    public function show(Advertisement $ad): View
    {
        $this->authorizeShopAccess($ad);
        $ad->load('shop');

        return view('admin.ads.ads_show', compact('ad'));
    }

    public function edit(Advertisement $ad): View
    {
        $this->authorizeShopAccess($ad);

        return view('admin.ads.ads_edit', [
            'ad' => $ad,
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function update(Request $request, Advertisement $ad): RedirectResponse
    {
        $data = $this->validatedData($request, $ad);
        $this->normalizeShopId($data);
        $data['duration'] = $data['duration'] ?? 10;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        if ($this->shouldReplaceMedia($request, $ad)) {
            $this->deleteUpload($ad->media);
            $this->deleteUpload($ad->video_poster);
            $data['media'] = $this->resolveMedia($request);
            $data['video_status'] = null;
            $data['video_poster'] = null;
            $data['video_error'] = null;
        } else {
            unset($data['media']);
        }

        $ad->update($data);
        if ($ad->type === 'video' && $request->hasFile('media_file')) {
            app(VideoProcessingService::class)->queue($ad);
        }

        return redirect()
            ->route('ads')
            ->with('status', 'تم تحديث الإعلان بنجاح.');
    }

    public function destroy(Advertisement $ad): RedirectResponse
    {
        $this->authorizeShopAccess($ad);
        $this->deleteUpload($ad->media);
        $this->deleteUpload($ad->video_poster);
        $ad->delete();

        return redirect()
            ->route('ads')
            ->with('status', 'تم حذف الإعلان بنجاح.');
    }

    private function validatedData(Request $request, ?Advertisement $ad = null): array
    {
        $isCreate = $ad === null;

        return $request->validate([
            'shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['image', 'video', 'youtube'])],
            'media' => [
                Rule::requiredIf(fn() => $request->input('type') === 'youtube' || ($isCreate && !$request->hasFile('media_file'))),
                'nullable',
                'string',
                'max:2048',
            ],
            'media_file' => [
                Rule::requiredIf(fn() => $isCreate && in_array($request->input('type'), ['image', 'video'], true)),
                'nullable',
                'file',
                Rule::when($request->input('type') === 'video', ['mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo']),
                'max:' . config('media.video_max_upload_kb'),
            ],
            'duration' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function resolveMedia(Request $request): string
    {
        if ($request->input('type') === 'youtube') {
            return $request->input('media');
        }

        if ($request->hasFile('media_file')) {
            $directory = $request->input('type') === 'image' ? 'ads/images' : 'ads/videos';
            $path = $request->file('media_file')->store($directory, 'public');

            return 'storage/' . $path;
        }

        return $request->input('media');
    }

    private function shouldReplaceMedia(Request $request, Advertisement $ad): bool
    {
        return $request->input('type') !== $ad->type
            || $request->hasFile('media_file')
            || ($request->input('type') === 'youtube' && $request->filled('media') && $request->input('media') !== $ad->media);
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $path = str_replace('storage/', '', $path);
        Storage::disk('public')->delete($path);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'image' => 'صورة',
            'video' => 'فيديو',
            'youtube' => 'يوتيوب',
            default => $type,
        };
    }
}
