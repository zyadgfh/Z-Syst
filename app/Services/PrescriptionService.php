<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrescriptionService
{
    use WithTransactionalOperations;

    /**
     * Create a new prescription with items.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Prescription
     * @throws \Exception
     */
    public function createPrescription(array $data, int $businessId): Prescription
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $prescription = Prescription::create([
                'business_id' => $businessId,
                'sale_id' => $data['sale_id'] ?? null,
                'party_id' => $data['party_id'] ?? null,
                'image' => $data['image'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'prescription_number' => $this->generatePrescriptionNumber($businessId),
                'review_status' => 'pending',
                'patient_name' => $data['patient_name'] ?? null,
                'patient_phone' => $data['patient_phone'] ?? null,
                'doctor_name' => $data['doctor_name'] ?? null,
                'doctor_license' => $data['doctor_license'] ?? null,
                'expires_at' => $data['expires_at'] ?? Carbon::now()->addDays(30),
                'meta' => $data['meta'] ?? null,
            ]);

            // Add prescription items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addPrescriptionItem($prescription, $item, $businessId);
                }
            }

            return $prescription->fresh(['items']);
        });
    }

    /**
     * Add an item to a prescription.
     *
     * @param Prescription $prescription
     * @param array<string, mixed> $itemData
     * @param int $businessId
     * @return PrescriptionItem
     */
    public function addPrescriptionItem(Prescription $prescription, array $itemData, int $businessId): PrescriptionItem
    {
        $product = Product::findOrFail($itemData['product_id']);

        return PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'product_id' => $product->id,
            'business_id' => $businessId,
            'dosage' => $itemData['dosage'] ?? null,
            'frequency' => $itemData['frequency'] ?? null,
            'duration' => $itemData['duration'] ?? null,
            'instructions' => $itemData['instructions'] ?? null,
            'quantity' => $itemData['quantity'] ?? 0,
            'notes' => $itemData['notes'] ?? null,
        ]);
    }

    /**
     * Update a prescription.
     *
     * @param Prescription $prescription
     * @param array<string, mixed> $data
     * @return Prescription
     * @throws \Exception
     */
    public function updatePrescription(Prescription $prescription, array $data): Prescription
    {
        return $this->executeTransaction(function () use ($prescription, $data) {
            // Handle image update
            if (isset($data['image']) && $data['image'] !== $prescription->image) {
                if ($prescription->image && Storage::exists($prescription->image)) {
                    Storage::delete($prescription->image);
                }
            }

            $prescription->update([
                'party_id' => $data['party_id'] ?? $prescription->party_id,
                'image' => $data['image'] ?? $prescription->image,
                'notes' => $data['notes'] ?? $prescription->notes,
                'patient_name' => $data['patient_name'] ?? $prescription->patient_name,
                'patient_phone' => $data['patient_phone'] ?? $prescription->patient_phone,
                'doctor_name' => $data['doctor_name'] ?? $prescription->doctor_name,
                'doctor_license' => $data['doctor_license'] ?? $prescription->doctor_license,
                'expires_at' => $data['expires_at'] ?? $prescription->expires_at,
                'meta' => $data['meta'] ?? $prescription->meta,
            ]);

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $prescription->items()->delete();
                foreach ($data['items'] as $item) {
                    $this->addPrescriptionItem($prescription, $item, $prescription->business_id);
                }
            }

            return $prescription->fresh(['items']);
        });
    }

    /**
     * Review a prescription.
     *
     * @param Prescription $prescription
     * @param int $reviewerId
     * @param string $status
     * @param string|null $notes
     * @return Prescription
     */
    public function reviewPrescription(Prescription $prescription, int $reviewerId, string $status, ?string $notes = null): Prescription
    {
        $prescription->update([
            'review_status' => $status,
            'review_notes' => $notes,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $prescription->fresh();
    }

    /**
     * Dispense items from a prescription.
     *
     * @param Prescription $prescription
     * @param array<int, int> $itemsToDispense // [item_id => quantity]
     * @param int $dispensedBy
     * @param int $businessId
     * @return array
     * @throws \Exception
     */
    public function dispensePrescription(Prescription $prescription, array $itemsToDispense, int $dispensedBy, int $businessId): array
    {
        if (!$prescription->canBeUsed()) {
            throw new \Exception('Prescription cannot be dispensed. Status: ' . $prescription->status . ', Review: ' . $prescription->review_status);
        }

        return $this->executeTransaction(function () use ($prescription, $itemsToDispense, $dispensedBy, $businessId) {
            $dispensedItems = [];
            $totalDispensed = 0;

            foreach ($itemsToDispense as $itemId => $quantity) {
                $item = $prescription->items()->findOrFail($itemId);
                
                if ($item->dispensed) {
                    continue; // Already dispensed
                }

                if ($quantity > $item->quantity) {
                    throw new \Exception("Cannot dispense more than prescribed. Item: {$item->id}, Requested: {$quantity}, Prescribed: {$item->quantity}");
                }

                // Check stock availability
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('business_id', $businessId)
                    ->where('productStock', '>=', $quantity)
                    ->first();

                if (!$stock) {
                    throw new \Exception("Insufficient stock for product ID: {$item->product_id}");
                }

                // Deduct stock
                $stock->decrement('productStock', $quantity);

                // Update prescription item
                $item->update([
                    'dispensed' => true,
                    'dispensed_quantity' => $quantity,
                    'dispensed_at' => now(),
                    'dispensed_by' => $dispensedBy,
                ]);

                $dispensedItems[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_dispensed' => $quantity,
                    'batch_no' => $stock->batch_no,
                ];

                $totalDispensed += $quantity;
            }

            // Mark prescription as used if all items are dispensed
            $allDispensed = $prescription->items()->where('dispensed', true)->count() === $prescription->items()->count();
            if ($allDispensed) {
                $prescription->markAsUsed([
                    'dispensed_by' => $dispensedBy,
                    'total_dispensed' => $totalDispensed,
                ]);
            }

            return [
                'prescription_id' => $prescription->id,
                'dispensed_items' => $dispensedItems,
                'total_dispensed' => $totalDispensed,
                'prescription_status' => $prescription->status,
            ];
        });
    }

    /**
     * Link a prescription to a sale.
     *
     * @param Prescription $prescription
     * @param Sale $sale
     * @return Prescription
     */
    public function linkToSale(Prescription $prescription, Sale $sale): Prescription
    {
        $prescription->update([
            'sale_id' => $sale->id,
            'party_id' => $sale->party_id,
        ]);

        return $prescription->fresh();
    }

    /**
     * Get expiring prescriptions.
     *
     * @param int $businessId
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringPrescriptions(int $businessId, int $days = 7)
    {
        $expiryDate = Carbon::now()->addDays($days);

        return Prescription::where('business_id', $businessId)
            ->where('status', 'pending')
            ->where('review_status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $expiryDate)
            ->where('expires_at', '>=', now())
            ->with(['items.product', 'party'])
            ->get();
    }

    /**
     * Get expired prescriptions.
     *
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredPrescriptions(int $businessId)
    {
        return Prescription::where('business_id', $businessId)
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['items.product', 'party'])
            ->get();
    }

    /**
     * Generate a unique prescription number.
     *
     * @param int $businessId
     * @return string
     */
    protected function generatePrescriptionNumber(int $businessId): string
    {
        $prefix = 'RX';
        $date = now()->format('Ymd');
        $sequence = Prescription::where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Delete a prescription and its image.
     *
     * @param Prescription $prescription
     * @return bool
     * @throws \Exception
     */
    public function deletePrescription(Prescription $prescription): bool
    {
        if ($prescription->status === 'used') {
            throw new \Exception('Cannot delete a used prescription');
        }

        if ($prescription->image && Storage::exists($prescription->image)) {
            Storage::delete($prescription->image);
        }

        return $prescription->delete();
    }

    /**
     * Get prescription statistics.
     *
     * @param int $businessId
     * @return array
     */
    public function getPrescriptionStatistics(int $businessId): array
    {
        $total = Prescription::where('business_id', $businessId)->count();
        $pending = Prescription::where('business_id', $businessId)->where('status', 'pending')->count();
        $used = Prescription::where('business_id', $businessId)->where('status', 'used')->count();
        $approved = Prescription::where('business_id', $businessId)->where('review_status', 'approved')->count();
        $rejected = Prescription::where('business_id', $businessId)->where('review_status', 'rejected')->count();

        $expiringSoon = $this->getExpiringPrescriptions($businessId, 7)->count();
        $expired = $this->getExpiredPrescriptions($businessId)->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'used' => $used,
            'review_approved' => $approved,
            'review_rejected' => $rejected,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
    }
}