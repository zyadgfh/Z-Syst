<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZSystBusiness;
use App\Traits\HasUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ZSystBusinessController extends Controller
{
    use HasUploader;

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = ZSystBusiness::query()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $businesses = $query->paginate($perPage);

        return view('admin.businesses.index', compact('businesses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:1000',
            'logo'     => 'nullable|image|max:2048',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->uploadFile($request->file('logo'), 'businesses/logos');
        }

        $business = ZSystBusiness::create($validated);

        return redirect()->route('admin.businesses.index')
                         ->with('success', 'Business created successfully.');
    }

    public function show(ZSystBusiness $business)
    {
        $business->load(['users', 'subscriptions']);

        return view('admin.businesses.show', compact('business'));
    }

    public function update(Request $request, ZSystBusiness $business)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|max:255',
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:1000',
            'logo'     => 'nullable|image|max:2048',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        // Explicitly prevent business_id overwrite
        unset($validated['business_id']);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($business->logo && \Storage::disk('public')->exists($business->logo)) {
                \Storage::disk('public')->delete($business->logo);
            }
            $validated['logo'] = $this->uploadFile($request->file('logo'), 'businesses/logos');
        }

        $business->update($validated);

        return redirect()->route('admin.businesses.show', $business->id)
                         ->with('success', 'Business updated successfully.');
    }

    public function destroy(ZSystBusiness $business)
    {
        // Soft-delete or archive instead of hard-delete if business has active subscriptions
        if ($business->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete a business with active subscriptions.');
        }

        $business->delete();

        return redirect()->route('admin.businesses.index')
                         ->with('success', 'Business deleted successfully.');
    }

    public function deleteAll(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:z_syst_businesses,id',
        ]);

        $businessIds = $request->input('ids');

        // Block bulk delete of businesses with active subscriptions
        $activeCount = ZSystBusiness::whereIn('id', $businessIds)
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active');
            })->count();

        if ($activeCount > 0) {
            return back()->with('error', "{$activeCount} business(es) have active subscriptions and cannot be deleted.");
        }

        ZSystBusiness::whereIn('id', $businessIds)->delete();

        return redirect()->route('admin.businesses.index')
                         ->with('success', count($businessIds) . ' business(es) deleted successfully.');
    }

    public function deleteImage(ZSystBusiness $business)
    {
        if ($business->logo && \Storage::disk('public')->exists($business->logo)) {
            \Storage::disk('public')->delete($business->logo);
            $business->update(['logo' => null]);
        }

        return back()->with('success', 'Image deleted successfully.');
    }

    public function export(Request $request)
    {
        // Export logic handled by export class
        return redirect()->route('admin.businesses.index')
                         ->with('info', 'Export functionality coming soon.');
    }

    public function zsystFilter(Request $request)
    {
        $query = ZSystBusiness::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $businesses = $query->latest()->limit(20)->get(['id', 'name', 'email']);

        return response()->json($businesses);
    }
}
