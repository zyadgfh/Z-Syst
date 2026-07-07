<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrescriptionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $prescriptions = Prescription::forCompany($request->user()->company_id)
            ->with(['patient', 'doctor', 'branch', 'createdBy'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id, fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->from_date, fn($q, $v) => $q->whereDate('prescribed_date', '>=', $v))
            ->when($request->to_date, fn($q, $v) => $q->whereDate('prescribed_date', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->success($prescriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'nullable|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'branch_id' => 'required|exists:branches,id',
            'prescribed_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:prescribed_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.instructions' => 'nullable|string',
            'items.*.substitution_allowed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $prescription = DB::transaction(function () use ($request, $validator) {
            $data = $validator->validated();
            $items = $data['items'];
            unset($data['items']);

            $prescription = Prescription::create(array_merge($data, [
                'company_id' => $request->user()->company_id,
                'prescription_number' => 'RX-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'created_by' => $request->user()->id,
            ]));

            foreach ($items as $item) {
                $prescription->items()->create([
                    'product_id' => $item['product_id'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'quantity' => $item['quantity'],
                    'dispensed_quantity' => 0,
                    'instructions' => $item['instructions'] ?? null,
                    'substitution_allowed' => $item['substitution_allowed'] ?? true,
                ]);
            }

            return $prescription->load(['patient', 'doctor', 'items.product']);
        });

        return $this->created($prescription, 'Prescription created successfully');
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($prescription->load(['patient', 'doctor', 'branch', 'items.product', 'createdBy']));
    }

    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'nullable|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $prescription->update($validator->validated());
        return $this->success($prescription, 'Prescription updated successfully');
    }

    public function destroy(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $prescription->delete();
        return $this->success(null, 'Prescription deleted successfully');
    }

    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if ($prescription->status === 'cancelled' || $prescription->status === 'expired') {
            return $this->error('Cannot dispense a cancelled or expired prescription', 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:prescription_items,id',
            'items.*.dispensed_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        DB::transaction(function () use ($request, $prescription) {
            $allFullyDispensed = true;

            foreach ($request->items as $item) {
                $prescriptionItem = $prescription->items()->findOrFail($item['id']);
                $newDispensed = $prescriptionItem->dispensed_quantity + $item['dispensed_quantity'];

                if ($newDispensed > $prescriptionItem->quantity) {
                    throw new \RuntimeException('Dispensed quantity exceeds prescribed quantity for item');
                }

                $prescriptionItem->update(['dispensed_quantity' => $newDispensed]);

                if ($newDispensed < $prescriptionItem->quantity) {
                    $allFullyDispensed = false;
                }
            }

            $prescription->update([
                'status' => $allFullyDispensed ? 'dispensed' : 'partially_dispensed',
            ]);
        });

        return $this->success($prescription->fresh()->load('items'), 'Prescription dispensed successfully');
    }
}