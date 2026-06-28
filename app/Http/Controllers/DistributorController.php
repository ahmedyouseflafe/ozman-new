<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Distributor;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorController extends Controller
{
    public function index(): View
    {
        $distributors = Distributor::query()
            ->with(['shop', 'agent'])
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
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
            'agents' => $this->accessibleAgents(),
            'distributorUsers' => $this->distributorUsers(),
            'selectedShopId' => $request->integer('shop_id') ?: $this->firstAccessibleShopId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->applyAgentShop($data);
        $this->normalizeShopId($data);
        $this->syncLoginAccount($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request);
        }

        $distributor = Distributor::create($data);

        if ($distributor->user_id) {
            return redirect()
                ->route('distributors.permissions.edit', $distributor)
                ->with('status', 'تمت إضافة الموزع. اختر صلاحياته الآن.');
        }

        return redirect()
            ->route('distributors')
            ->with('status', 'تمت إضافة الموزع بنجاح.');
    }

    public function show(Distributor $distributor): View
    {
        $this->authorizeShopAccess($distributor);
        $distributor->load('shop');
        $distributor->load('agent');

        return view('admin.distributors.distributors_show', compact('distributor'));
    }

    public function edit(Distributor $distributor): View
    {
        $this->authorizeShopAccess($distributor);

        return view('admin.distributors.distributors_edit', [
            'distributor' => $distributor,
            'shops' => $this->accessibleShops(),
            'agents' => $this->accessibleAgents(),
            'distributorUsers' => $this->distributorUsers(),
        ]);
    }

    public function update(Request $request, Distributor $distributor): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->applyAgentShop($data);
        $this->normalizeShopId($data);
        $this->syncLoginAccount($data);
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

    public function editPermissions(Distributor $distributor): View|RedirectResponse
    {
        $this->authorizeShopAccess($distributor);
        $user = $distributor->user;

        if (! $user) {
            return redirect()
                ->route('distributors.edit', $distributor)
                ->withErrors(['user_id' => 'اربط الموزع بحساب دخول قبل تحديد الصلاحيات.']);
        }

        $user->load('employeePermissions');

        return view('admin.employees.permissions', [
            'employee' => $user,
            'permissionGroups' => config('employee_permissions.groups', []),
            'selectedPermissions' => $user->employeePermissions->pluck('permission')->all(),
            'pageTitle' => 'صلاحيات الموزع',
            'headerTitle' => 'صلاحيات الموزع',
            'subjectLabel' => 'الموزع',
            'description' => 'حدد الصفحات والعمليات التي يستطيع الموزع الوصول إليها داخل لوحة التحكم.',
            'formAction' => route('distributors.permissions.update', $distributor),
            'backUrl' => route('distributors'),
        ]);
    }

    public function updatePermissions(Request $request, Distributor $distributor): RedirectResponse
    {
        $this->authorizeShopAccess($distributor);
        $user = $distributor->user;
        abort_unless($user, 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->validPermissionKeys())],
        ]);

        $user->employeePermissions()->delete();
        foreach (array_unique($data['permissions'] ?? []) as $permission) {
            $user->employeePermissions()->create(['permission' => $permission]);
        }

        return redirect()
            ->route('distributors')
            ->with('status', 'تم حفظ صلاحيات الموزع بنجاح.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'shop_id' => ['nullable', 'required_without:agent_id', 'integer', Rule::exists('shops', 'id')],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'distributor')],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'login_password' => ['nullable', 'string', 'min:6', 'max:255'],
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

    private function validPermissionKeys(): array
    {
        return collect(config('employee_permissions.groups', []))
            ->flatMap(fn($group) => array_keys($group['permissions'] ?? []))
            ->values()
            ->all();
    }

    private function applyAgentShop(array &$data): void
    {
        if (empty($data['agent_id'])) {
            return;
        }

        $agent = $this->scopeToAccessibleShops(Agent::query())
            ->whereKey($data['agent_id'])
            ->firstOrFail();

        $data['shop_id'] = $agent->shop_id;
    }

    private function syncLoginAccount(array &$data): void
    {
        $password = $data['login_password'] ?? null;
        unset($data['login_password']);

        if (! filled($password)) {
            return;
        }

        $email = $data['email'] ?? null;
        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => 'أدخل بريد الموزع الإلكتروني لإنشاء حساب دخول.',
            ]);
        }

        if (! empty($data['user_id'])) {
            $user = User::query()
                ->where('role', 'distributor')
                ->findOrFail($data['user_id']);

            $user->forceFill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $user->phone,
                'password' => $password,
                'is_active' => true,
            ])->save();

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'هذا البريد مستخدم بحساب آخر. اختر الحساب من خانة حساب دخول الموزع أو استخدم بريد مختلف.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $password,
            'role' => 'distributor',
            'is_active' => true,
        ]);

        $data['user_id'] = $user->id;
    }

    private function distributorUsers()
    {
        return User::query()
            ->where('role', 'distributor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function accessibleAgents()
    {
        return $this->scopeToAccessibleShops(Agent::query())
            ->with('shop')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
