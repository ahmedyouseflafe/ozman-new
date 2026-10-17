<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\MainScreen;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScreenController extends Controller
{
    private const MAX_SCREEN_UPLOAD_KILOBYTES = 1048576; // 1 GB

    public function index(): View
    {
        abort_unless($this->canAccessScreenRoute(), 403);

        $screens = $this->visibleScreensQuery()
            ->with('shop')
            ->when($this->selectedShopId(), fn ($query, $shopId) => $query->where('shop_id', $shopId))
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
            'shops' => $this->screenShops(),
            'selectedShopId' => $this->selectedShopId(),
            'screensCount' => $screens->count(),
            'activeScreensCount' => $screens->where('is_active', true)->count(),
            'inactiveScreensCount' => $screens->where('is_active', false)->count(),
            'averageScreenDuration' => round($screens->avg('duration') ?: 0),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->canAccessScreenRoute(), 403);

        abort_if($this->allowedPlacements() === [], 403);

        return view('admin.screens.screens_create', [
            'allowedPlacements' => $this->allowedPlacements(),
            ...$this->screenFormContext(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessScreenRoute(), 403);

        $data = $this->validatedData($request);
        $data['shop_id'] = $this->screenShopId($request);
        $data['needs_shop_assignment'] = false;
        $data['duration'] = $data['duration'] ?? 10;
        $data['placement'] = $data['placement'] ?? 'top';
        $this->authorizePlacement($data['placement']);
        $data['user_id'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active');
        $data['media'] = $this->resolveMedia($request);

        MainScreen::create($data);

        return redirect()
            ->route('screens', ['shop_id' => $data['shop_id'] ?? ''])
            ->with('status', 'تمت إضافة شاشة رئيسية بنجاح.');
    }

    public function show(MainScreen $screen): View
    {
        abort_unless($this->canAccessScreenRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        return view('admin.screens.screens_show', compact('screen'));
    }

    public function edit(MainScreen $screen): View
    {
        abort_unless($this->canAccessScreenRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        abort_if($this->allowedPlacements() === [], 403);

        return view('admin.screens.screens_edit', [
            'screen' => $screen,
            'allowedPlacements' => $this->allowedPlacements(),
            ...$this->screenFormContext($screen),
        ]);
    }

    public function update(Request $request, MainScreen $screen): RedirectResponse
    {
        abort_unless($this->canAccessScreenRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        $data = $this->validatedData($request, $screen);
        $data['shop_id'] = $this->screenShopId($request, $screen);
        $data['needs_shop_assignment'] = false;
        $data['duration'] = $data['duration'] ?? 10;
        $data['placement'] = $data['placement'] ?? 'top';
        $this->authorizePlacement($data['placement']);
        $data['is_active'] = $request->boolean('is_active');

        if ($this->shouldReplaceMedia($request, $screen)) {
            $this->deleteUpload($screen->media);
            $data['media'] = $this->resolveMedia($request);
        } else {
            unset($data['media']);
        }

        $screen->update($data);

        return redirect()
            ->route('screens', ['shop_id' => $data['shop_id'] ?? ''])
            ->with('status', 'تم تحديث الشاشة بنجاح.');
    }

    public function destroy(MainScreen $screen): RedirectResponse
    {
        abort_unless($this->canAccessScreenRoute(), 403);
        $this->authorizeScreenVisibility($screen);

        $this->deleteUpload($screen->media);
        $screen->delete();

        return redirect()
            ->route('screens', ['shop_id' => $screen->shop_id ?? ''])
            ->with('status', 'تم حذف الشاشة بنجاح.');
    }

    public function mainDisplay(): View
    {
        $items = MainScreen::query()
            ->forPlatform()
            ->where('is_active', true)
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
        abort_unless($shop->is_active, 404);
        $items = Advertisement::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $items = $shop->activeScreens()->get()->toBase()->concat($items);

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

        $shopIds = Auth::user()->accessibleShopIds();
        return $query->where(function ($query) use ($shopIds) {
            $query->whereIn('shop_id', $shopIds)
                ->orWhere(fn ($legacy) => $legacy->where('needs_shop_assignment', true)->where('user_id', Auth::id()));
            if ($this->canManagePlatformScreens()) {
                $query->orWhere(fn ($platform) => $platform->forPlatform()->where('user_id', Auth::id()));
            }
        });
    }

    private function canViewAllScreens(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || ($user->isEmployee() && $user->hasEmployeePermission('screens.view_all'));
    }

    private function authorizeScreenVisibility(MainScreen $screen): void
    {
        abort_unless($this->visibleScreensQuery()->whereKey($screen->id)->exists(), 403);
    }

    private function canAccessScreenRoute(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'employee', 'shop_owner', 'agent', 'distributor'], true)
            && $user->canAccessRouteName(request()->route()->getName());
    }

    private function canManagePlatformScreens(): bool
    {
        return Auth::user()->isSuperAdmin() || Auth::user()->isEmployee();
    }

    private function screenShops()
    {
        return Shop::query()->when(! $this->canViewAllScreens(), fn ($query) =>
            $query->whereIn('id', Auth::user()->accessibleShopIds()))->orderBy('name')->get();
    }

    private function selectedShopId(): ?int
    {
        if (request()->exists('shop_id') && ! request()->filled('shop_id')) return null;
        $id = request()->integer('shop_id') ?: (int) session('current_shop_id');
        if (! $id) {
            $ids = Auth::user()->accessibleShopIds();
            return count($ids) === 1 ? $ids[0] : null;
        }
        abort_unless($this->screenShops()->contains('id', $id), 403);
        return $id;
    }

    private function screenFormContext(?MainScreen $screen = null): array
    {
        return ['shops' => $this->screenShops(), 'canManagePlatformScreens' => $this->canManagePlatformScreens(),
            'selectedShopId' => $screen ? $screen->shop_id : $this->selectedShopId()];
    }

    private function screenShopId(Request $request, ?MainScreen $screen = null): ?int
    {
        $request->validate(['shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')]]);
        $id = $request->exists('shop_id') ? ($request->filled('shop_id') ? (int) $request->input('shop_id') : null)
            : ($screen ? $screen->shop_id : $this->selectedShopId());
        if ($id !== null) {
            abort_unless($this->screenShops()->contains('id', $id), 403);
        } elseif (! $this->canManagePlatformScreens()) {
            throw ValidationException::withMessages(['shop_id' => 'اختر المحل الذي ستظهر فيه الشاشة.']);
        }
        return $id;
    }
}
