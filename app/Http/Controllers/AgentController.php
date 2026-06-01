<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::query()
            ->with('shop')
            ->when(! $this->isSuperAdmin(), fn($query) => $this->scopeToAccessibleShops($query))
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
        ]);
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->normalizeShopId($data);
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
