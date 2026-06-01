<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorController extends Controller
{
    public function index(): View
    {
        $distributors = Distributor::query()
            ->with('shop')
            ->when(! $this->isSuperAdmin(), fn($query) => $this->scopeToAccessibleShops($query))
            ->latest()
            ->get()
            ->map(function (Distributor $distributor) {
                $distributor->status_label = $distributor->is_active ? 'نشط' : 'غير نشط';
                $distributor->status_class = $distributor->is_active ? 'tag-g' : 'tag-r';
                $distributor->shop_name = $distributor->shop?->name ?? '-';

                return $distributor;
            });

        return view('admin.distributors.distributors', [
            'distributors' => $distributors,
            'distributorsCount' => $distributors->count(),
            'activeDistributorsCount' => $distributors->where('is_active', true)->count(),
            'inactiveDistributorsCount' => $distributors->where('is_active', false)->count(),
            'linkedShopsCount' => $distributors->pluck('shop_id')->filter()->unique()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.distributors.distributors_create', [
            'shops' => $this->accessibleShops(),
            'selectedShopId' => $request->integer('shop_id') ?: $this->firstAccessibleShopId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request);
        }

        Distributor::create($data);

        return redirect()
            ->route('distributors')
            ->with('status', 'تمت إضافة الموزع بنجاح.');
    }

    public function show(Distributor $distributor): View
    {
        $this->authorizeShopAccess($distributor);
        $distributor->load('shop');

        return view('admin.distributors.distributors_show', compact('distributor'));
    }

    public function edit(Distributor $distributor): View
    {
        $this->authorizeShopAccess($distributor);

        return view('admin.distributors.distributors_edit', [
            'distributor' => $distributor,
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function update(Request $request, Distributor $distributor): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteUpload($distributor->image);
            $data['image'] = $this->storeUpload($request);
        }

        $distributor->update($data);

        return redirect()
            ->route('distributors')
            ->with('status', 'تم تحديث بيانات الموزع بنجاح.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $this->authorizeShopAccess($distributor);
        $this->deleteUpload($distributor->image);
        $distributor->delete();

        return redirect()
            ->route('distributors')
            ->with('status', 'تم حذف الموزع بنجاح.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }

    private function storeUpload(Request $request): string
    {
        $path = $request->file('image')->store('distributors', 'public');

        return 'storage/' . $path;
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete(str_replace('storage/', '', $path));
    }
}
