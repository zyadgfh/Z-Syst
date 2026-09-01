<?php

namespace App\Services\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Exceptions\StockUnavailableException;
use Illuminate\Support\Facades\DB;

class StockAllocationService
{
    /**
     * Allocate (deduct) stock and record movement.
     *
     * @param Stock $stock
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int $userId
     * @param string|null $notes
     * @return StockMovement
     * @throws StockUnavailableException
     */
    public function allocate(Stock $stock, int $quantity, string $referenceType, int $referenceId, int $userId, ?string $notes = null): StockMovement
    {
        if ($stock->productStock < $quantity) {
            throw new StockUnavailableException("الكمية المطلوبة غير متوفرة. المتاح: {$stock->productStock}");
        }

        $beforeQuantity = $stock->productStock;
        $stock->decrement('productStock', $quantity);
        $afterQuantity = $stock->productStock; // or $beforeQuantity - $quantity

        return $this->recordMovement(
            $stock,
            'out',
            $quantity,
            $beforeQuantity,
            $afterQuantity,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        );
    }

    /**
     * Release (add back) stock and record movement (e.g. sale return or deleting a sale).
     *
     * @param Stock $stock
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int $userId
     * @param string|null $notes
     * @return StockMovement
     */
    public function release(Stock $stock, int $quantity, string $referenceType, int $referenceId, int $userId, ?string $notes = null): StockMovement
    {
        $beforeQuantity = $stock->productStock;
        $stock->increment('productStock', $quantity);
        $afterQuantity = $stock->productStock;

        return $this->recordMovement(
            $stock,
            'in',
            $quantity,
            $beforeQuantity,
            $afterQuantity,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        );
    }

    /**
     * Add new stock (e.g. from purchase) and record movement.
     *
     * @param Stock $stock
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int $userId
     * @param string|null $notes
     * @return StockMovement
     */
    public function addStock(Stock $stock, int $quantity, string $referenceType, int $referenceId, int $userId, ?string $notes = null): StockMovement
    {
        $beforeQuantity = $stock->productStock;
        $stock->increment('productStock', $quantity);
        $afterQuantity = $stock->productStock;

        return $this->recordMovement(
            $stock,
            'in',
            $quantity,
            $beforeQuantity,
            $afterQuantity,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        );
    }

    /**
     * Adjust stock manually.
     *
     * @param Stock $stock
     * @param int $newQuantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int $userId
     * @param string|null $notes
     * @return StockMovement
     */
    public function adjustStock(Stock $stock, int $newQuantity, string $referenceType, int $referenceId, int $userId, ?string $notes = null): StockMovement
    {
        $beforeQuantity = $stock->productStock;
        
        if ($beforeQuantity == $newQuantity) {
            // No movement needed
            return null; // Or throw exception, or return the latest movement
        }

        $stock->productStock = $newQuantity;
        $stock->save();

        $movementType = 'adjustment';
        // You could also use 'in' or 'out' depending on whether it increased or decreased, but 'adjustment' is explicitly for this.
        $quantityDiff = abs($newQuantity - $beforeQuantity);

        return $this->recordMovement(
            $stock,
            $movementType,
            $quantityDiff,
            $beforeQuantity,
            $newQuantity,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        );
    }

    /**
     * Helper to record movement.
     */
    private function recordMovement(Stock $stock, string $type, int $quantity, int $beforeQuantity, int $afterQuantity, string $referenceType, int $referenceId, int $userId, ?string $notes): StockMovement
    {
        return StockMovement::create([
            'business_id' => $stock->business_id,
            'product_id' => $stock->product_id,
            'stock_id' => $stock->id,
            'user_id' => $userId,
            'movement_type' => $type,
            'quantity' => $quantity,
            'before_quantity' => $beforeQuantity,
            'after_quantity' => $afterQuantity,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }
}
