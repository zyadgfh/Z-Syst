<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InsuranceClaimController extends Controller
{
    /**
     * FIX (L-01): Cap per_page to max 100.
     * FIX (L-02): Sanitize search input to prevent wildcard abuse.
     * FIX (L-03): Add forBusiness() scope to all queries.
     * FIX (L-04): destroy() should only allow deletion of draft/pending claims.
     */

    public function index(Request $request)
    {
        // L-03: Scope to current business
        $query = InsuranceClaim::forBusiness();

        if ($request->has('search') && !empty($request->search)) {
            // L-02: Sanitize search input
            $search = Str::of($request->search)->trim()->studi()->escapes()->value();
            $query->where(function ($q) use ($search) {
                $q->where('claim_number', 'LIKE', "%{$search}%")
                  ->orWhere('patient_name', 'LIKE', "%{$search}%")
                  ->orWhere('insurance_company_name', 'LIKE', "%{$search}%");
            });
        }

        // L-01: Cap per_page
        $perPage = min((int) ($request->per_page ?? 10), 100);
        $perPage = max($perPage, 1);

        return response()->json(
            $query->orderBy('created_at', 'desc')
                  ->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_number'       => 'required|string|max:255',
            'patient_name'       => 'required|string|max:255',
            'insurance_company_name' => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0',
            'status'             => 'in:pending,approved,rejected,draft',
            'notes'              => 'nullable|string',
        ]);

        // Set business_id from authenticated user
        $validated['business_id'] = Auth::user()->business_id;
        $validated['status'] = $validated['status'] ?? 'draft';

        $claim = InsuranceClaim::create($validated);

        \App\Helpers\StructuredLogger::audit('insurance_claims.created', Auth::user(), [
            'claim_id' => $claim->id,
            'claim_number' => $claim->claim_number,
        ]);

        return response()->json($claim, 201);
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        // L-03: Verify business scope
        if ($insuranceClaim->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to this claim.');
        }

        return response()->json($insuranceClaim);
    }

    public function update(Request $request, InsuranceClaim $insuranceClaim)
    {
        // L-03: Verify business scope
        if ($insuranceClaim->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to this claim.');
        }

        // Unset business_id to prevent overwrite
        $validated = $request->except(['business_id', 'id']);

        $insuranceClaim->update($validated);

        \App\Helpers\StructuredLogger::audit('insurance_claims.updated', Auth::user(), [
            'claim_id' => $insuranceClaim->id,
        ]);

        return response()->json($insuranceClaim);
    }

    public function destroy(InsuranceClaim $insuranceClaim)
    {
        // L-03: Verify business scope
        if ($insuranceClaim->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to this claim.');
        }

        // L-04: Only allow deletion of draft or pending claims
        if (!in_array($insuranceClaim->status, ['draft', 'pending'])) {
            return response()->json([
                'error' => "Cannot delete claim with status '{$insuranceClaim->status}'. Only draft or pending claims can be deleted.",
            ], 422);
        }

        $insuranceClaim->delete();

        \App\Helpers\StructuredLogger::audit('insurance_claims.deleted', Auth::user(), [
            'claim_id' => $insuranceClaim->id,
            'claim_number' => $insuranceClaim->claim_number,
        ]);

        return response()->json(['message' => 'Claim deleted successfully.']);
    }
}
