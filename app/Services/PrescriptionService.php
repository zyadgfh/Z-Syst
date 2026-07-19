<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PrescriptionCreated;
use App\Events\PrescriptionDispensed;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {
    }

    /**
     * Create a new prescription with items.
     */
    public function createPrescription(array $data, int $companyId, int $createdBy): Prescription
    {
        return DB::transaction(function () use ($data, $companyId, $createdBy) {
            $prescription = Prescription::create([
                'company_id' => $companyId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'branch_id' => $data['branch_id'],
                'prescribed_date' => $data['prescribed_date'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'prescription_number' => 'RX-' . strtoupper(uniqid()),
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            $items = $data['items'] ?? [];
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

            // Dispatch event for prescription creation
            event(new PrescriptionCreated($prescription));

            return $prescription;
        });
    }

    /**
     * Dispense prescription items.
     * Uses FEFO (First Expiry First Out) logic for stock deduction.
     */
    public function dispenseItems(Prescription $prescription, array $items, int $companyId): Prescription
    {
        return DB::transaction(function () use ($prescription, $items, $companyId) {
            $allFullyDispensed = true;

            foreach ($items as $itemData) {
                $item = $this->resolvePrescriptionItem($prescription, $itemData);

                $dispensedQty = (int) $itemData['dispensed_quantity'];
                $item->update(['dispensed_quantity' => $dispensedQty]);

                if ($dispensedQty < $item->quantity) {
                    $allFullyDispensed = false;
                }

                // Deduct from stock using FEFO
                $this->deductStock($prescription, $item, $dispensedQty, $companyId);
            }

            $newStatus = $allFullyDispensed ? 'dispensed' : 'partially_dispensed';
            $prescription->update(['status' => $newStatus]);

            // Dispatch event for dispensing
            event(new PrescriptionDispensed($prescription));

            return $prescription;
        });
    }

    /**
     * Resolve prescription item by ID or barcode.
     */
    private function resolvePrescriptionItem(Prescription $prescription, array $itemData): PrescriptionItem
    {
        if (!empty($itemData['id'])) {
            return $prescription->items()->findOrFail($itemData['id']);
        }

        $barcode = trim((string) ($itemData['barcode'] ?? ''));
        $product = Product::query()
            ->where('company_id', $prescription->company_id)
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            throw ValidationException::withMessages(['barcode' => ['Barcode not found']]);
        }

        $item = $prescription->items()->where('product_id', $product->id)->first();

        if (!$item) {
            throw ValidationException::withMessages(['barcode' => ['This barcode does not belong to a prescribed item']]);
        }

        return $item;
    }

    /**
     * Deduct stock using FEFO (First Expiry First Out) strategy.
     */
    private function deductStock(Prescription $prescription, PrescriptionItem $item, int $quantity, int $companyId): void
    {
        // Find the oldest batch that hasn't expired (FEFO)
        $stock = Stock::query()
            ->where('product_id', $item->product_id)
            ->whereHas('product', function ($query) use ($prescription) {
                $query->where('company_id', $prescription->company_id);
            })
            ->orderBy('expire_date', 'asc') // FEFO: First Expiry First Out
            ->first();

        if ($stock) {
            $newStock = max(0, (int) $stock->productStock - $quantity);
            $stock->update(['productStock' => $newStock]);

            // Log stock movement
            $this->stockMovementService->logMovement(
                (int) $item->product_id,
                (int) $prescription->branch_id,
                'out',
                (float) $quantity,
                'prescription_dispense',
                (int) $prescription->id,
                null,
                [
                    'prescription_number' => $prescription->prescription_number,
                    'item_id' => $item->id,
                ]
            );
        }
    }

    /**
     * Dispense by barcode (quick dispense).
     */
    public function dispenseByBarcode(Prescription $prescription, string $barcode, int $quantity, int $companyId): Prescription
    {
        return DB::transaction(function () use ($prescription, $quantity, $companyId, $barcode) {
            $product = Product::query()
                ->where('company_id', $prescription->company_id)
                ->where('barcode', $barcode)
                ->first();

            if (!$product) {
                throw ValidationException::withMessages(['barcode' => ['Barcode not found']]);
            }

            $item = $prescription->items()->where('product_id', $product->id)->first();

            if (!$item) {
                throw ValidationException::withMessages(['barcode' => ['This barcode does not belong to a prescribed item']]);
            }

            $newDispensed = min($quantity, $item->quantity);
            $currentDispensed = (int) $item->dispensed_quantity;
            $updatedDispensed = min($item->quantity, $currentDispensed + $newDispensed);

            $item->update(['dispensed_quantity' => $updatedDispensed]);

            // Deduct stock
            $stock = Stock::query()
                ->where('product_id', $product->id)
                ->whereHas('product', function ($query) use ($prescription) {
                    $query->where('company_id', $prescription->company_id);
                })
                ->first();

            if ($stock) {
                $stock->update(['productStock' => max(0, (int) $stock->productStock - $newDispensed)]);
            }

            $this->stockMovementService->logMovement(
                (int) $product->id,
                (int) $prescription->branch_id,
                'out',
                (float) $newDispensed,
                'prescription_dispense',
                (int) $prescription->id,
                null,
                [
                    'prescription_number' => $prescription->prescription_number,
                    'barcode' => $barcode,
                ]
            );

            $allFullyDispensed = $prescription->items()
                ->whereColumn('dispensed_quantity', '<', 'quantity')
                ->doesntExist();

            $prescription->update([
                'status' => $allFullyDispensed ? 'dispensed' : 'partially_dispensed',
            ]);

            event(new PrescriptionDispensed($prescription));

            return $prescription;
        });
    }

    /**
     * Check if prescription can be modified.
     */
    public function canModify(Prescription $prescription): void
    {
        if (!in_array($prescription->status, ['pending', 'partially_dispensed'])) {
            throw ValidationException::withMessages([
                'status' => ['Cannot modify a completed or cancelled prescription']
            ]);
        }
    }

    /**
     * Check if prescription can be deleted.
     */
    public function canDelete(Prescription $prescription): void
    {
        if (!in_array($prescription->status, ['pending', 'cancelled', 'expired'])) {
            throw ValidationException::withMessages([
                'status' => ['Cannot delete a dispensed prescription']
            ]);
        }
    }

    /**
     * Validate prescription ownership.
     */
    public function validateOwnership(Prescription $prescription, int $companyId): void
    {
        if ($prescription->company_id !== $companyId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Forbidden');
        }
    }

    /**
     * Get prescription statistics for dashboard.
     */
    public function getStatistics(int $companyId, int $branchId = null): array
    {
        return [
            'total' => Prescription::where('company_id', $companyId)->count(),
            'pending' => Prescription::where('company_id', $companyId)->where('status', 'pending')->count(),
            'dispensed_today' => Prescription::where('company_id', $companyId)
                ->where('status', 'dispensed')
                ->whereDate('updated_at', today())
                ->count(),
            'expired' => Prescription::where('company_id', $companyId)
                ->where('status', 'expired')
                ->orWhere('expiry_date', '<', now())
                ->count(),
        ];
    }
}