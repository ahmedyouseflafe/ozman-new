<?php

namespace App\Http\Controllers;

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
            ->when(! $this->isSuperAdmin(), fn($query) => $this->scopeToAccessibleShops($query))
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
        $category->load('shop')->loadCount('products');

        return view('admin.categories.categories_show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->authorizeShopAccess($category);

        return view('admin.categories.categories_edit', [
            'category' => $category,
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $this->normalizeShopId($data);
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
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
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
