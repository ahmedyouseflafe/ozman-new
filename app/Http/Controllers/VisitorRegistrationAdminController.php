<?php

namespace App\Http\Controllers;

use App\Models\VisitorRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitorRegistrationAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));

        $registrations = VisitorRegistration::query()
            ->when(in_array($type, ['customer', 'merchant'], true), fn($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('shop_name', 'like', "%{$search}%")
                        ->orWhere('tax_file', 'like', "%{$search}%")
                        ->orWhere('residence_address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.visitor_registrations.index', [
            'registrations' => $registrations,
            'totalCount' => VisitorRegistration::count(),
            'customersCount' => VisitorRegistration::where('type', 'customer')->count(),
            'merchantsCount' => VisitorRegistration::where('type', 'merchant')->count(),
            'pendingMerchantsCount' => VisitorRegistration::where('type', 'merchant')->where('status', 'pending')->count(),
            'selectedType' => $type,
            'search' => $search,
        ]);
    }

    public function status(Request $request, VisitorRegistration $registration): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($registration->type === 'merchant', 422);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $registration->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : null,
            'approved_by' => $validated['status'] === 'approved' ? $request->user()->id : null,
        ]);

        return back()->with('status', $validated['status'] === 'approved'
            ? 'تم قبول صاحب المتجر وأصبح بإمكانه الشراء من الموقع.'
            : 'تم رفض طلب صاحب المتجر.');
    }
}
