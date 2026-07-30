<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\Shop;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorController extends Controller
{
    public function index(): View
    {
        $distributors = Distributor::query()
            ->with(['shop', 'agent'])
            ->when(auth()->user()?->isDistributor(), fn($query) => $query->whereIn('id', $this->currentDistributorIds()))
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

    public function marketersIndex(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $distributorId = $request->integer('distributor_id') ?: null;
        $accessibleDistributors = $this->accessibleDistributors();

        $marketersQuery = DistributorMarketer::query()
            ->with(['distributor.shop', 'user'])
            ->withCount('frontOrders')
            ->withSum('frontOrders as stored_commission_total', 'marketer_commission_amount')
            ->withSum('frontOrders as orders_total', 'total')
            ->when(! $this->hasGlobalDashboardAccess(), function ($query) use ($accessibleDistributors) {
                $query->whereIn('distributor_id', $accessibleDistributors->pluck('id')->all());
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhereHas('distributor', fn($distributorQuery) => $distributorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'active', fn($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn($query) => $query->where('is_active', false))
            ->when($distributorId, fn($query) => $query->where('distributor_id', $distributorId))
            ->latest();

        $marketers = $marketersQuery->paginate(20)->withQueryString();
        $marketers->getCollection()->each(function (DistributorMarketer $marketer) {
            $rate = max((float) $marketer->commission_rate, 0);
            $marketer->commission_total = (float) $marketer->frontOrders()
                ->sum(DB::raw("COALESCE(marketer_commission_amount, total * {$rate} / 100, 0)"));
        });

        return view('admin.distributors.marketers', [
            'marketers' => $marketers,
            'distributors' => $accessibleDistributors,
            'search' => $search,
            'status' => $status,
            'selectedDistributorId' => $distributorId,
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
        $this->authorizeDistributorAccess($distributor);
        $distributor->load('shop');
        $distributor->load('agent');
        $distributor->load(['marketers' => fn($query) => $query->latest()]);
        $publicDistributorUrl = route('front.distributor', $distributor);
        $distributorQrTargetUrl = url(URL::signedRoute('merchant.login', [
            'referrer_type' => 'distributor',
            'referrer' => $distributor->id,
            'redirect' => parse_url($publicDistributorUrl, PHP_URL_PATH),
        ], absolute: false));
        $distributorQrCodeSvg = (new Writer(new ImageRenderer(
            new RendererStyle(360, 2),
            new SvgImageBackEnd()
        )))->writeString($distributorQrTargetUrl);
        $distributorQrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($distributorQrCodeSvg);
        $marketerShareLinks = $distributor->marketers
            ->map(function (DistributorMarketer $marketer) {
                $url = route('front.marketer', ['marketer' => $marketer->tracking_code]);
                $wheelUrl = route('front.marketer.direct-wheel', ['marketer' => $marketer->tracking_code]);
                $qrUrl = url(URL::signedRoute('merchant.login', [
                    'referrer_type' => 'marketer',
                    'referrer' => $marketer->tracking_code,
                    'redirect' => parse_url($url, PHP_URL_PATH),
                ], absolute: false));
                $qrCodeSvg = (new Writer(new ImageRenderer(
                    new RendererStyle(220, 2),
                    new SvgImageBackEnd()
                )))->writeString($qrUrl);
                $wheelQrCodeSvg = (new Writer(new ImageRenderer(
                    new RendererStyle(220, 2),
                    new SvgImageBackEnd()
                )))->writeString($wheelUrl);

                return [
                    'marketer' => $marketer,
                    'url' => $url,
                    'qr_url' => $qrUrl,
                    'qr' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg),
                    'wheel_url' => $wheelUrl,
                    'wheel_qr' => 'data:image/svg+xml;base64,' . base64_encode($wheelQrCodeSvg),
                ];
            });

        return view('admin.distributors.distributors_show', compact(
            'distributor',
            'publicDistributorUrl',
            'distributorQrCodeDataUri',
            'marketerShareLinks'
        ));
    }

    public function storeMarketer(Request $request, Distributor $distributor): RedirectResponse
    {
        $this->authorizeDistributorAccess($distributor);

        $data = $request->validateWithBag('createMarketer', [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'login_password' => ['nullable', 'string', 'min:6', 'max:255'],
        ], [
            'name.required' => 'أدخل اسم المسوق.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'commission_rate.numeric' => 'نسبة الربح يجب أن تكون رقماً.',
            'commission_rate.min' => 'نسبة الربح لا يمكن أن تكون أقل من صفر.',
            'commission_rate.max' => 'نسبة الربح لا يمكن أن تكون أكبر من 100%.',
            'login_password.min' => 'كلمة مرور المسوق يجب أن تكون 6 خانات على الأقل.',
        ]);

        $data['commission_rate'] = $data['commission_rate'] ?? 0;

        $password = $data['login_password'] ?? null;
        unset($data['login_password']);

        if (filled($password)) {
            if (! filled($data['email'] ?? null)) {
                throw ValidationException::withMessages([
                    'email' => 'أدخل بريد المسوق الإلكتروني لإنشاء حساب دخول.',
                ])->errorBag('createMarketer');
            }

            $user = User::query()->where('email', $data['email'])->first();

            if ($user && ! $user->isMarketer()) {
                throw ValidationException::withMessages([
                    'email' => 'هذا البريد مستخدم لحساب آخر. استخدم بريد مختلف للمسوق.',
                ])->errorBag('createMarketer');
            }

            if ($user) {
                $user->forceFill([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? $user->phone,
                    'password' => Hash::make($password),
                    'is_active' => true,
                ])->save();
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($password),
                    'role' => 'marketer',
                    'is_active' => true,
                ]);
            }

            $data['user_id'] = $user->id;
        }

        $data['tracking_code'] = $this->uniqueMarketerCode($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $distributor->marketers()->create($data);

        if ($request->input('return_to') === 'marketers_index') {
            return redirect()
                ->route('distributors.marketers.index')
                ->with('status', 'تمت إضافة المسوق بنجاح.');
        }

        return redirect()
            ->route('distributors.show', $distributor)
            ->with('status', 'تمت إضافة المسوق بنجاح.');
    }

    public function destroyMarketer(DistributorMarketer $marketer): RedirectResponse
    {
        $marketer->load('distributor');
        $this->authorizeDistributorAccess($marketer->distributor);
        $distributor = $marketer->distributor;

        $marketer->delete();

        return redirect()
            ->route('distributors.show', $distributor)
            ->with('status', 'تم حذف المسوق بنجاح.');
    }

    public function updateMarketer(Request $request, DistributorMarketer $marketer): RedirectResponse
    {
        $marketer->load(['distributor', 'user']);
        $this->authorizeDistributorAccess($marketer->distributor);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($marketer->user_id),
            ],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'login_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $password = $data['login_password'] ?? null;
        unset($data['login_password']);

        $user = $marketer->user;

        if (filled($password)) {
            if (! filled($data['email'] ?? null)) {
                throw ValidationException::withMessages([
                    'email' => 'أدخل بريد المسوق الإلكتروني لتحديث أو إنشاء حساب الدخول.',
                ]);
            }

            if (! $user) {
                $user = User::query()->where('email', $data['email'])->first();

                if ($user && ! $user->isMarketer()) {
                    throw ValidationException::withMessages([
                        'email' => 'هذا البريد مستخدم لحساب آخر. استخدم بريد مختلف للمسوق.',
                    ]);
                }
            }

            if ($user) {
                $user->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? $user->phone,
                    'password' => Hash::make($password),
                    'role' => 'marketer',
                    'is_active' => true,
                ])->save();
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($password),
                    'role' => 'marketer',
                    'is_active' => true,
                ]);
            }

            $data['user_id'] = $user->id;
        } elseif ($user) {
            $userData = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $user->phone,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ];

            if (filled($data['email'] ?? null)) {
                $userData['email'] = $data['email'];
            }

            $user->forceFill($userData)->save();
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $marketer->update($data);

        return back()->with('status', 'تم تحديث بيانات المسوق بنجاح.');
    }

    public function updateMarketerCommission(Request $request, DistributorMarketer $marketer): RedirectResponse
    {
        $marketer->load('distributor');
        $this->authorizeDistributorAccess($marketer->distributor);

        $data = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $marketer->update([
            'commission_rate' => $data['commission_rate'],
        ]);

        return back()->with('status', 'تم تحديث نسبة ربح المسوق بنجاح.');
    }

    public function editMarketerPermissions(DistributorMarketer $marketer): View|RedirectResponse
    {
        $marketer->load(['distributor', 'user.employeePermissions']);
        $this->authorizeDistributorAccess($marketer->distributor);

        if (! $marketer->user) {
            return redirect()
                ->route('distributors.marketers.index')
                ->withErrors(['user_id' => 'اربط المسوق بحساب دخول قبل تحديد الصلاحيات.']);
        }

        return view('admin.employees.permissions', [
            'employee' => $marketer->user,
            'permissionGroups' => config('employee_permissions.groups', []),
            'selectedPermissions' => $marketer->user->employeePermissions->pluck('permission')->all(),
            'pageTitle' => 'صلاحيات مسوق الموزع',
            'headerTitle' => 'صلاحيات مسوق الموزع',
            'subjectLabel' => 'المسوق',
            'description' => 'حدد الصفحات والعمليات التي يستطيع مسوق الموزع الوصول إليها داخل لوحة التحكم.',
            'formAction' => route('distributors.marketers.permissions.update', $marketer),
            'backUrl' => route('distributors.marketers.index'),
        ]);
    }

    public function updateMarketerPermissions(Request $request, DistributorMarketer $marketer): RedirectResponse
    {
        $marketer->load(['distributor', 'user']);
        $this->authorizeDistributorAccess($marketer->distributor);
        abort_unless($marketer->user, 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->validPermissionKeys())],
        ]);

        $marketer->user->employeePermissions()->delete();
        foreach (array_unique($data['permissions'] ?? []) as $permission) {
            $marketer->user->employeePermissions()->create(['permission' => $permission]);
        }

        return redirect()
            ->route('distributors.marketers.index')
            ->with('status', 'تم حفظ صلاحيات المسوق بنجاح.');
    }

    public function edit(Distributor $distributor): View
    {
        $this->authorizeDistributorAccess($distributor);

        return view('admin.distributors.distributors_edit', [
            'distributor' => $distributor,
            'shops' => $this->accessibleShops(),
            'agents' => $this->accessibleAgents(),
            'distributorUsers' => $this->distributorUsers(),
        ]);
    }

    public function update(Request $request, Distributor $distributor): RedirectResponse
    {
        $this->authorizeDistributorAccess($distributor);

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
        $this->authorizeDistributorAccess($distributor);
        $this->deleteUpload($distributor->image);
        $distributor->delete();

        return redirect()
            ->route('distributors')
            ->with('status', 'تم حذف الموزع بنجاح.');
    }

    public function editPermissions(Distributor $distributor): View|RedirectResponse
    {
        $this->authorizeDistributorAccess($distributor);
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
        $this->authorizeDistributorAccess($distributor);
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

    private function uniqueMarketerCode(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'marketer';

        do {
            $code = $base . '-' . Str::lower(Str::random(6));
        } while (DistributorMarketer::query()->where('tracking_code', $code)->exists());

        return $code;
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

    private function accessibleDistributors()
    {
        if (auth()->user()?->isDistributor()) {
            $ids = $this->currentDistributorIds();

            return Distributor::query()
                ->with('shop')
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get();
        }

        return $this->scopeToAccessibleShops(Distributor::query())
            ->with('shop')
            ->orderBy('name')
            ->get();
    }

    private function currentDistributorIds(): array
    {
        $user = auth()->user();
        if (! $user?->isDistributor()) {
            return [];
        }

        $ids = $user->distributorProfiles()->pluck('id');

        if ($user->email) {
            $ids = $ids->merge(Distributor::query()
                ->where('email', $user->email)
                ->pluck('id'));
        }

        return $ids->map(fn($id) => (int) $id)->unique()->values()->all();
    }

    private function authorizeDistributorAccess(Distributor $distributor): void
    {
        if (auth()->user()?->isDistributor()) {
            abort_unless(in_array((int) $distributor->id, $this->currentDistributorIds(), true), 403);

            return;
        }

        $this->authorizeShopAccess($distributor);
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
