<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = InsuranceClaim::whereHas('insuranceCompany', function ($q) use ($request) {
                $q->where('company_id', $request->user()->company_id);
            })
            ->with([
                'insuranceCompany:id,name',
                'patient:id,name',
                'sale' => fn($q) => $q->select('id', 'invoice_number', 'total_amount')
            ])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->insurance_company_id, fn($q, $v) => $q->where('insurance_company_id', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('submitted_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('submitted_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($claims);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'patient_id' => 'required|exists:patients,id',
            'sale_id' => 'required|exists:sales,id',
            'amount_claimed' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Verify authorization
        $insuranceCompany = InsuranceCompany::findOrFail($request->insurance_company_id);
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $claim = InsuranceClaim::create($validator->validated() + [
            'claim_number' => 'CLM-' . strtoupper(uniqid()),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json($claim->load([
            'insuranceCompany:id,name',
            'patient:id,name',
            'sale' => fn($q) => $q->select('id', 'invoice_number')
        ]), 201);
    }

    public function show(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($insuranceClaim->load([
            'insuranceCompany:id,name,contact_person,phone,email',
            'patient:id,name,phone,insurance_info',
            'sale',
            'sale.items.product:id,productName',
        ]));
    }

    public function update(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,approved,rejected,paid',
            'amount_approved' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $updateData = $validator->validated();

        if (isset($updateData['status']) && $updateData['status'] === 'approved' && !isset($updateData['amount_approved'])) {
            $updateData['amount_approved'] = $insuranceClaim->amount_claimed;
        }

        if (isset($updateData['status'])) {
            if ($updateData['status'] === 'approved') {
                $updateData['resolved_at'] = now();
            } elseif ($updateData['status'] === 'rejected') {
                $updateData['resolved_at'] = now();
            } elseif ($updateData['status'] === 'paid') {
                $updateData['paid_at'] = now();
            }
        }

        $insuranceClaim->update($updateData);

        return response()->json($insuranceClaim->fresh());
    }

    public function destroy(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($insuranceClaim->status, ['pending', 'rejected'])) {
            return response()->json(['message' => 'Cannot delete a claim that is approved or paid'], 422);
        }

        $insuranceClaim->delete();

        return response()->json(['message' => 'Insurance claim deleted successfully']);
    }
}