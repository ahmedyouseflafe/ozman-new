<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $shops = $this->accessibleShops();
        $currentShop = null;

        if ($request->user()?->isShopOwner()) {
            $preferredShopId = $request->integer('shop_id')
                ?: (int) $request->session()->get('current_shop_id')
                ?: $this->firstAccessibleShopId();
            $currentShop = $shops->firstWhere('id', $preferredShopId) ?: $shops->first();
        }

        $categories = Category::query()
            ->with('shop')
            ->withCount('products')
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->when($currentShop, fn($query) => $query->where('shop_id', $currentShop->id))
            ->when(
                auth()->user()?->isAgent() && $this->categoryHasColumn('agent_id'),
                fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds())
            )
            ->latest()
            ->get()
            ->map(function (Category $category) {
                $category->status_label = $category->is_active ? 'نشط' : 'غير نشط';
                $category->status_class = $category->is_active ? 'tag-g' : 'tag-r';

                return $category;
            });

        return view('admin.categories.categories', [
            'categories' => $categories,
            'currentShop' => $currentShop,
            'categoriesCount' => $categories->count(),
            'productsInCategoriesCount' => $categories->sum('products_count'),
            'activeCategoriesCount' => $categories->where('is_active', true)->count(),
            'emptyCategoriesCount' => $categories->where('products_count', 0)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $shops = $this->accessibleShops();
        $preferredShopId = $request->integer('shop_id')
            ?: (int) $request->session()->get('current_shop_id')
            ?: $this->firstAccessibleShopId();
        $selectedShop = $shops->firstWhere('id', $preferredShopId)
            ?: ($request->user()?->isShopOwner() ? $shops->first() : null);

        return view('admin.categories.categories_create', [
            'shops' => $shops,
            'selectedShopId' => $selectedShop?->id,
            'selectedShop' => $selectedShop,
            'lockShopSelection' => $request->user()?->isShopOwner() && $selectedShop !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $this->applyAgentOwnership($data);
        $this->applyOptionalCategoryColumns($request, $data);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request, 'image', 'categories');
        }

        if ($request->hasFile('background_video')) {
            $data['background_video'] = $this->storeUpload($request, 'background_video', 'categories/backgrounds');
        }

        $category = Category::create($data);
        $isRestaurant = $category->shop?->catalog_type === 'restaurant';

        $this->notifySuperAdmin(
            'category_created',
            $category,
            $isRestaurant ? 'تمت إضافة قسم منيو جديد' : 'تمت إضافة فئة جديدة',
            $isRestaurant
                ? "المطعم {$category->shop?->name} أضاف قسم منيو: {$category->name}"
                : "المتجر {$category->shop?->name} أضاف فئة: {$category->name}",
            route('categories.show', $category)
        );

        return redirect()
            ->route('categories')
            ->with('status', $isRestaurant ? 'تمت إضافة قسم المنيو بنجاح.' : 'تمت إضافة الفئة بنجاح.');
    }

    public function show(Category $category): View
    {
        $this->authorizeShopAccess($category);
        $this->authorizeCategoryVisibility($category);
        $category->load('shop')->loadCount('products');

        return view('admin.categories.categories_show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->authorizeShopAccess($category);
        $this->authorizeCategoryManagement($category);

        return view('admin.categories.categories_edit', [
            'category' => $category,
            'shops' => $this->accessibleShops(),
            'selectedShop' => $category->shop,
            'lockShopSelection' => auth()->user()?->isShopOwner() === true,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $this->normalizeShopId($data);
        $this->authorizeCategoryManagement($category);
        $this->applyAgentOwnership($data, $category);
        $this->applyOptionalCategoryColumns($request, $data);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $category);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteUpload($category->image);
            $data['image'] = $this->storeUpload($request, 'image', 'categories');
        }

        if ($request->hasFile('background_video')) {
            $this->deleteUpload($category->background_video);
            $data['background_video'] = $this->storeUpload($request, 'background_video', 'categories/backgrounds');
        } elseif ($request->boolean('remove_background_video')) {
            $this->deleteUpload($category->background_video);
            $data['background_video'] = null;
        }

        $category->update($data);
        $category->load('shop');
        $isRestaurant = $category->shop?->catalog_type === 'restaurant';

        return redirect()
            ->route('categories')
            ->with('status', $isRestaurant ? 'تم تحديث قسم المنيو بنجاح.' : 'تم تحديث الفئة بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizeShopAccess($category);
        $this->authorizeCategoryManagement($category);
        $isRestaurant = $category->shop?->catalog_type === 'restaurant';
        $this->deleteUpload($category->image);
        $this->deleteUpload($category->background_video);
        $category->delete();

        return redirect()
            ->route('categories')
            ->with('status', $isRestaurant ? 'تم حذف قسم المنيو بنجاح.' : 'تم حذف الفئة بنجاح.');
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_he' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
            'background_video' => ['nullable', 'file', 'mimes:mp4,webm', 'max:20480'],
            'remove_background_video' => ['nullable', 'boolean'],
        ]);
    }

    private function localizedInput(Request $request, string $field, ?string $arabicValue = null): array
    {
        return array_filter([
            'ar' => $arabicValue,
            'en' => $request->input("{$field}_en"),
            'he' => $request->input("{$field}_he"),
        ], fn($value) => filled($value));
    }

    private function authorizeCategoryVisibility(Category $category): void
    {
        if (! auth()->user()?->isAgent() || ! $this->categoryHasColumn('agent_id')) {
            return;
        }

        abort_unless(in_array((int) $category->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function authorizeCategoryManagement(Category $category): void
    {
        $user = auth()->user();

        if ($user?->isDistributor() && ! $user->canAccessRouteName(request()->route()?->getName())) {
            abort(403);
        }

        if (! $user?->isAgent() || ! $this->categoryHasColumn('agent_id')) {
            return;
        }

        abort_unless(in_array((int) $category->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function applyAgentOwnership(array &$data, ?Category $category = null): void
    {
        $user = auth()->user();

        if (! $user?->isAgent() || ! $this->categoryHasColumn('agent_id')) {
            return;
        }

        $agentId = $category?->agent_id ?: $this->currentUserAgentIdForShop((int) $data['shop_id']);
        abort_if(! $agentId, 403);

        $data['agent_id'] = $agentId;
    }

    private function currentUserAgentIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return Agent::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function currentUserAgentIdForShop(int $shopId): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Agent::query()
            ->where('shop_id', $shopId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->value('id');
    }

    private function applyOptionalCategoryColumns(Request $request, array &$data): void
    {
        if ($this->categoryHasColumn('name_translations')) {
            $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        } else {
            unset($data['name_translations']);
        }

        if (! $this->categoryHasColumn('agent_id')) {
            unset($data['agent_id']);
        }
    }

    private function categoryHasColumn(string $column): bool
    {
        static $columns = [];

        return $columns[$column] ??= Schema::hasColumn('categories', $column);
    }

    private function uniqueSlug(string $value, ?Category $category = null): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'category';
        $slug = $base;
        $counter = 2;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($category, fn($query) => $query->where('id', '!=', $category->id))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function storeUpload(Request $request, string $field, string $directory): string
    {
        $path = $request->file($field)->store($directory, 'public');

        return 'storage/' . $path;
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path) {
            return;
        }

        $path = Str::of($path)->replaceStart('storage/', '')->toString();
        Storage::disk('public')->delete($path);
    }
}
