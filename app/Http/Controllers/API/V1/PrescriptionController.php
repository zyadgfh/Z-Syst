<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $prescriptions = Prescription::where('company_id', $request->user()->company_id)
            ->with(['patient:id,name,phone', 'doctor:id,name,specialization', 'branch:id,name', 'items.product:id,productName'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id, fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('prescribed_date', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('prescribed_date', '<=', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('prescription_number', 'like', "%{$v}%")
                        ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$v}%"))
                        ->orWhereHas('doctor', fn($d) => $d->where('name', 'like', "%{$v}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($prescriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'branch_id' => 'required|exists:branches,id',
            'prescribed_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:prescribed_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.dosage' => 'required|string|max:255',
            'items.*.frequency' => 'required|string|max:255',
            'items.*.duration' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.instructions' => 'nullable|string',
            'items.*.substitution_allowed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'];
        unset($data['items']);

        $prescription = Prescription::create($data + [
            'company_id' => $request->user()->company_id,
            'prescription_number' => 'RX-' . strtoupper(uniqid()),
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        foreach ($items as $item) {
            $prescription->items()->create([
                'product_id' => $item['product_id'],
                'dosage' => $item['dosage'],
                'frequency' => $item['frequency'],
                'duration' => $item['duration'],
                'quantity' => $item['quantity'],
                'dispensed_quantity' => 0,
                'instructions' => $item['instructions'] ?? null,
                'substitution_allowed' => $item['substitution_allowed'] ?? true,
            ]);
        }

        return response()->json($prescription->load(['patient:id,name', 'doctor:id,name', 'items.product:id,productName']), 201);
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($prescription->load([
            'patient:id,name,phone,date_of_birth,gender,blood_group,allergies',
            'doctor:id,name,specialization,license_number,clinic_name,phone',
            'branch:id,name',
            'createdBy:id,name',
            'items.product:id,productName',
        ]));
    }

    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($prescription->status, ['pending', 'partially_dispensed'])) {
            return response()->json(['message' => 'Cannot modify a completed or cancelled prescription'], 422);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'branch_id' => 'sometimes|exists:branches,id',
            'expiry_date' => 'nullable|date|after_or_equal:prescribed_date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,partially_dispensed,dispensed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $prescription->update($validator->validated());

        return response()->json($prescription->fresh());
    }

    public function destroy(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($prescription->status, ['pending', 'cancelled', 'expired'])) {
            return response()->json(['message' => 'Cannot delete a dispensed prescription'], 422);
        }

        $prescription->items()->delete();
        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully']);
    }

    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($prescription->status === 'dispensed') {
            return response()->json(['message' => 'Prescription already fully dispensed'], 422);
        }

        if ($prescription->expiry_date && $prescription->expiry_date->isPast()) {
            return response()->json(['message' => 'Prescription has expired'], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:prescription_items,id',
            'items.*.dispensed_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $allFullyDispensed = true;
        foreach ($request->items as $itemData) {
            $item = $prescription->items()->findOrFail($itemData['id']);
            $item->update(['dispensed_quantity' => $itemData['dispensed_quantity']]);

            if ($itemData['dispensed_quantity'] < $item->quantity) {
                $allFullyDispensed = false;
            }
        }

        $prescription->update([
            'status' => $allFullyDispensed ? 'dispensed' : 'partially_dispensed',
        ]);

        return response()->json($prescription->fresh()->load(['items']));
    }
}