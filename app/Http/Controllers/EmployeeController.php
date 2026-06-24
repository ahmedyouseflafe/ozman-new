<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeEmployeeRoute();

        $query = User::query()
            ->where('role', 'employee')
            ->withCount('employeePermissions')
            ->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.employees.index', [
            'employees' => $query->paginate(20)->withQueryString(),
            'employeesCount' => User::query()->where('role', 'employee')->count(),
            'activeEmployeesCount' => User::query()->where('role', 'employee')->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeEmployeeRoute();

        return view('admin.employees.form', [
            'employee' => new User(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEmployeeRoute();

        $data = $this->validatedData($request);
        $data['role'] = 'employee';

        $employee = User::create($data);

        $this->notifySuperAdmin(
            'user_created',
            $employee,
            'تم إنشاء مستخدم جديد',
            "تم إنشاء حساب موظف جديد: {$employee->name}",
            route('employees.edit', $employee)
        );

        return redirect()
            ->route('employees.permissions.edit', $employee)
            ->with('status', 'تم إنشاء الموظف. اختر صلاحياته الآن.');
    }

    public function edit(User $employee): View
    {
        $this->authorizeEmployeeRoute();
        $this->authorizeEmployee($employee);

        return view('admin.employees.form', compact('employee'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployeeRoute();
        $this->authorizeEmployee($employee);

        $data = $this->validatedData($request, $employee);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $employee->update($data);

        return redirect()
            ->route('employees')
            ->with('status', 'تم تحديث بيانات الموظف بنجاح.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->authorizeEmployeeRoute();
        $this->authorizeEmployee($employee);
        abort_if($employee->is(auth()->user()), 422);

        $employee->delete();

        return redirect()
            ->route('employees')
            ->with('status', 'تم حذف الموظف بنجاح.');
    }

    public function editPermissions(User $employee): View
    {
        $this->authorizeEmployeeRoute();
        $this->authorizeEmployee($employee);
        $employee->load('employeePermissions');

        return view('admin.employees.permissions', [
            'employee' => $employee,
            'permissionGroups' => config('employee_permissions.groups', []),
            'selectedPermissions' => $employee->employeePermissions->pluck('permission')->all(),
        ]);
    }

    public function updatePermissions(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployeeRoute();
        $this->authorizeEmployee($employee);

        $validPermissions = collect(config('employee_permissions.groups', []))
            ->flatMap(fn($group) => array_keys($group['permissions'] ?? []))
            ->values()
            ->all();

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($validPermissions)],
        ]);

        $employee->employeePermissions()->delete();
        foreach (array_unique($data['permissions'] ?? []) as $permission) {
            $employee->employeePermissions()->create(['permission' => $permission]);
        }

        return redirect()
            ->route('employees')
            ->with('status', 'تم حفظ صلاحيات الموظف بنجاح.');
    }

    private function validatedData(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }

    private function authorizeEmployee(User $employee): void
    {
        abort_unless($employee->role === 'employee', 404);
    }

    private function authorizeEmployeeRoute(): void
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
    }
}
