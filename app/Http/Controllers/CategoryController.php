<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->with('shop')
            ->withCount('products')
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->when(auth()->user()?->isAgent(), fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds()))
            ->latest()
            ->get()
            ->map(function (Category $category) {
                $category->status_label = $category->is_active ? 'نشط' : 'غير نشط';
                $category->status_class = $category->is_active ? 'tag-g' : 'tag-r';

                return $category;
            });

        return view('admin.categories.categories', [
            'categories' => $categories,
            'categoriesCount' => $categories->count(),
            'productsInCategoriesCount' => $categories->sum('products_count'),
            'activeCategoriesCount' => $categories->where('is_active', true)->count(),
            'emptyCategoriesCount' => $categories->where('products_count', 0)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.categories.categories_create', [
            'shops' => $this->accessibleShops(),
            'selectedShopId' => $request->integer('shop_id') ?: $this->firstAccessibleShopId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $this->applyAgentOwnership($data);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request, 'image', 'categories');
        }

        $category = Category::create($data);

        $this->notifySuperAdmin(
            'category_created',
            $category,
            'تمت إضافة فئة جديدة',
            "المتجر {$category->shop?->name} أضاف فئة: {$category->name}",
            route('categories.show', $category)
        );

        return redirect()
            ->route('categories')
            ->with('status', 'تمت إضافة الفئة بنجاح.');
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
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $this->normalizeShopId($data);
        $this->authorizeCategoryManagement($category);
        $this->applyAgentOwnership($data, $category);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $category);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteUpload($category->image);
            $data['image'] = $this->storeUpload($request, 'image', 'categories');
        }

        $category->update($data);

        return redirect()
            ->route('categories')
            ->with('status', 'تم تحديث الفئة بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizeShopAccess($category);
        $this->authorizeCategoryManagement($category);
        $this->deleteUpload($category->image);
        $category->delete();

        return redirect()
            ->route('categories')
            ->with('status', 'تم حذف الفئة بنجاح.');
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
        if (! auth()->user()?->isAgent()) {
            return;
        }

        abort_unless(in_array((int) $category->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function authorizeCategoryManagement(Category $category): void
    {
        if (auth()->user()?->isDistributor()) {
            abort(403);
        }

        if (! auth()->user()?->isAgent()) {
            return;
        }

        abort_unless(in_array((int) $category->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function applyAgentOwnership(array &$data, ?Category $category = null): void
    {
        $user = auth()->user();

        if (! $user?->isAgent()) {
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
