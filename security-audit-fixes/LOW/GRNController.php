<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GRNRequest;
use App\Models\GRN;
use App\Services\GRNService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GRNController extends Controller
{
    public function __construct(GRNService $grnService)
    {
        $this->grnService = $grnService;
    }

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = GRN::forBusiness()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $grns = $query->paginate($perPage);

        return view('admin.grns.index', compact('grns'));
    }

    public function store(GRNRequest $request)
    {
        $validated = $request->validated();
        $validated['business_id'] = Auth::user()->business_id;

        $grn = $this->grnService->create($validated);

        return redirect()->route('admin.grns.show', $grn->id)
                         ->with('success', 'GRN created successfully.');
    }

    public function show(GRN $grn)
    {
        $grn->load('items.product');

        return view('admin.grns.show', compact('grn'));
    }

    public function update(GRNRequest $request, GRN $grn)
    {
        $validated = $request->validated();

        // Explicitly prevent business_id overwrite
        unset($validated['business_id']);

        $this->grnService->update($grn, $validated);

        return redirect()->route('admin.grns.show', $grn->id)
                         ->with('success', 'GRN updated successfully.');
    }

    public function destroy(GRN $grn)
    {
        $this->grnService->delete($grn);

        return redirect()->route('admin.grns.index')
                         ->with('success', 'GRN deleted successfully.');
    }
}
