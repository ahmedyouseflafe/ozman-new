<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::query()
            ->with('shop')
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->latest()
            ->get()
            ->map(function (Agent $agent) {
                $agent->status_label = $agent->is_active ? 'نشط' : 'غير نشط';
                $agent->status_class = $agent->is_active ? 'tag-g' : 'tag-r';
                $agent->shop_name = $agent->shop?->name ?? '-';

                return $agent;
            });

        return view('admin.agent.agents', [
            'agents' => $agents,
            'agentsCount' => $agents->count(),
            'activeAgentsCount' => $agents->where('is_active', true)->count(),
            'inactiveAgentsCount' => $agents->where('is_active', false)->count(),
            'coveredCitiesCount' => $agents->pluck('shop.city')->filter()->unique()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.agent.agents_create', [
            'shops' => $this->accessibleShops(),
            'agentUsers' => $this->agentUsers(),
            'selectedShopId' => $request->integer('shop_id') ?: $this->firstAccessibleShopId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $this->syncLoginAccount($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUpload($request);
        }

        Agent::create($data);

        return redirect()
            ->route('agents')
            ->with('status', 'تمت إضافة الوكيل بنجاح.');
    }

    public function show(Agent $agent): View
    {
        $this->authorizeShopAccess($agent);
        $agent->load('shop');

        return view('admin.agent.agents_show', compact('agent'));
    }

    public function edit(Agent $agent): View
    {
        $this->authorizeShopAccess($agent);

        return view('admin.agent.agents_edit', [
            'agent' => $agent,
            'shops' => $this->accessibleShops(),
            'agentUsers' => $this->agentUsers(),
        ]);
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
        $this->syncLoginAccount($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteUpload($agent->image);
            $data['image'] = $this->storeUpload($request);
        }

        $agent->update($data);

        return redirect()
            ->route('agents')
            ->with('status', 'تم تحديث بيانات الوكيل بنجاح.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $this->authorizeShopAccess($agent);
        $this->deleteUpload($agent->image);
        $agent->delete();

        return redirect()
            ->route('agents')
            ->with('status', 'تم حذف الوكيل بنجاح.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'agent')],
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
                'email' => 'أدخل بريد الوكيل الإلكتروني لإنشاء حساب دخول.',
            ]);
        }

        if (! empty($data['user_id'])) {
            $user = User::query()
                ->where('role', 'agent')
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
                'email' => 'هذا البريد مستخدم بحساب آخر. اختر الحساب من خانة حساب دخول الوكيل أو استخدم بريد مختلف.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $password,
            'role' => 'agent',
            'is_active' => true,
        ]);

        $data['user_id'] = $user->id;
    }

    private function agentUsers()
    {
        return User::query()
            ->where('role', 'agent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function storeUpload(Request $request): string
    {
        $path = $request->file('image')->store('agents', 'public');

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
