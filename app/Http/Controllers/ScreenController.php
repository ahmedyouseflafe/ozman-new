<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\MainScreen;
use App\Models\Shop;
use App\Services\VideoProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScreenController extends Controller
{
    private const MAX_SCREEN_UPLOAD_KILOBYTES = 25600; // 25 MB before processing

    public function index(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $screens = $this->visibleScreensQuery()
            ->latest()
            ->get()
            ->map(function (MainScreen $screen) {
                $screen->type_label = $this->typeLabel($screen->type);
                $screen->placement_label = $this->placementLabel($screen->placement ?? 'top');
                $screen->status_label = $screen->is_active ? 'نشط' : 'معطل';
                $screen->status_class = $screen->is_active ? 'tag-g' : 'tag-r';

                return $screen;
            });

        return view('admin.screens.screens', [
            'screens' => $screens,
            'screensCount' => $screens->count(),
            'activeScreensCount' => $screens->where('is_active', true)->count(),
            'inactiveScreensCount' => $screens->where('is_active', false)->count(),
            'averageScreenDuration' => round($screens->avg('duration') ?: 0),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        abort_if($this->allowedPlacements() === [], 403);

        return view('admin.screens.screens_create', [
            'allowedPlacements' => $this->allowedPlacements(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $this->validatedData($request);
        $data['duration'] = $data['duration'] ?? 10;
        $data['placement'] = $data['placement'] ?? 'top';
        $this->authorizePlacement($data['placement']);
        $data['user_id'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active');
        $data['media'] = $this->resolveMedia($request);

        $screen = MainScreen::create($data);
        if ($screen->type === 'video' && $request->hasFile('media_file')) {
            app(VideoProcessingService::class)->queue($screen);
        }

        return redirect()
            ->route('screens')
            ->with('status', 'تمت إضافة شاشة رئيسية بنجاح.');
    }

    public function show(MainScreen $screen): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        return view('admin.screens.screens_show', compact('screen'));
    }

    public function edit(MainScreen $screen): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        abort_if($this->allowedPlacements() === [], 403);

        return view('admin.screens.screens_edit', [
            'screen' => $screen,
            'allowedPlacements' => $this->allowedPlacements(),
        ]);
    }

    public function update(Request $request, MainScreen $screen): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        $data = $this->validatedData($request, $screen);
        $data['duration'] = $data['duration'] ?? 10;
        $data['placement'] = $data['placement'] ?? 'top';
        $this->authorizePlacement($data['placement']);
        $data['is_active'] = $request->boolean('is_active');

        if ($this->shouldReplaceMedia($request, $screen)) {
            $this->deleteUpload($screen->media);
            $this->deleteUpload($screen->video_poster);
            $data['media'] = $this->resolveMedia($request);
            $data['video_status'] = null;
            $data['video_poster'] = null;
            $data['video_error'] = null;
        } else {
            unset($data['media']);
        }

        $screen->update($data);
        if ($screen->type === 'video' && $request->hasFile('media_file')) {
            app(VideoProcessingService::class)->queue($screen);
        }

        return redirect()
            ->route('screens')
            ->with('status', 'تم تحديث الشاشة بنجاح.');
    }

    public function destroy(MainScreen $screen): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        $this->deleteUpload($screen->media);
        $this->deleteUpload($screen->video_poster);
        $screen->delete();

        return redirect()
            ->route('screens')
            ->with('status', 'تم حذف الشاشة بنجاح.');
    }

    public function mainDisplay(): View
    {
        $items = MainScreen::query()
            ->where('is_active', true)
            ->publiclyReady()
            ->latest()
            ->get();

        return view('screens.main', [
            'title' => 'الشاشة الرئيسية',
            'items' => $items,
            'shop' => null,
        ]);
    }

    public function shopDisplay(Shop $shop): View
    {
        $items = Advertisement::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->publiclyReady()
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('screens.main', [
            'title' => $shop->name,
            'items' => $items,
            'shop' => $shop,
        ]);
    }

    private function validatedData(Request $request, ?MainScreen $screen = null): array
    {
        $isCreate = $screen === null;

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
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
                'max:' . self::MAX_SCREEN_UPLOAD_KILOBYTES,
            ],
            'duration' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'placement' => ['nullable', Rule::in(['top', 'bottom'])],
        ]);
    }

    private function resolveMedia(Request $request): string
    {
        if ($request->input('type') === 'youtube') {
            return $request->input('media');
        }

        if ($request->hasFile('media_file')) {
            $directory = $request->input('type') === 'image' ? 'screens/images' : 'screens/videos';
            $path = $request->file('media_file')->store($directory, 'public');

            return 'storage/' . $path;
        }

        return $request->input('media');
    }

    private function shouldReplaceMedia(Request $request, MainScreen $screen): bool
    {
        return $request->input('type') !== $screen->type
            || $request->hasFile('media_file')
            || ($request->input('type') === 'youtube' && $request->filled('media') && $request->input('media') !== $screen->media);
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('storage/', '', $path));
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

    private function placementLabel(string $placement): string
    {
        return match ($placement) {
            'bottom' => 'الشاشة السفلية',
            default => 'الشاشة العلوية',
        };
    }

    private function allowedPlacements(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin() || ! $user->hasAssignedPermissions()) {
            return ['top', 'bottom'];
        }

        return collect([
            'top' => $user->hasEmployeePermission('screens.place_top'),
            'bottom' => $user->hasEmployeePermission('screens.place_bottom'),
        ])
            ->filter()
            ->keys()
            ->all();
    }

    private function authorizePlacement(string $placement): void
    {
        abort_unless(in_array($placement, $this->allowedPlacements(), true), 403);
    }

    private function visibleScreensQuery()
    {
        $query = MainScreen::query();

        if ($this->canViewAllScreens()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    private function canViewAllScreens(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || ! $user->hasAssignedPermissions()
            || $user->hasEmployeePermission('screens.view_all');
    }

    private function authorizeScreenVisibility(MainScreen $screen): void
    {
        abort_unless($this->canViewAllScreens() || (int) $screen->user_id === (int) Auth::id(), 403);
    }
}
