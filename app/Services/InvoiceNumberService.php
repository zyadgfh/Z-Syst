<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Generate a unique thread-safe invoice number for a given type.
     *
     * @param string $type The invoice type (S for Sale, P for Purchase, D for DueCollect)
     * @param int $businessId The business ID
     * @return string The generated invoice number
     */
    public function generateInvoiceNumber(string $type, int $businessId): string
    {
        return DB::transaction(function () use ($type, $businessId) {
            // Get the maximum invoice number for this business and type
            $tableName = $this->getTableNameForType($type);
            $prefix = $this->getPrefixForType($type);

            $lastNumber = DB::table($tableName)
                ->where('business_id', $businessId)
                ->where('invoiceNumber', 'like', "{$prefix}%")
                ->max('invoiceNumber');

            // Extract the numeric part or start from 1
            if ($lastNumber) {
                $lastId = (int) str_replace($prefix, '', $lastNumber);
                $newId = $lastId + 1;
            } else {
                $newId = 1;
            }

            // Generate the new invoice number
            return $prefix . str_pad($newId, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the table name for a given invoice type.
     *
     * @param string $type
     * @return string
     */
    private function getTableNameForType(string $type): string
    {
        return match ($type) {
            'S' => 'sales',
            'P' => 'purchases',
            'D' => 'due_collects',
            default => throw new \InvalidArgumentException("Invalid invoice type: {$type}"),
        };
    }

    /**
     * Get the prefix for a given invoice type.
     *
     * @param string $type
     * @return string
     */
    private function getPrefixForType(string $type): string
    {
        return match ($type) {
            'S' => 'S-',
            'P' => 'P-',
            'D' => 'D-',
            default => throw new \InvalidArgumentException("Invalid invoice type: {$type}"),
        };
    }

    /**
     * Generate a sale invoice number.
     *
     * @param int $businessId
     * @return string
     */
    public function generateSaleInvoiceNumber(int $businessId): string
    {
        return $this->generateInvoiceNumber('S', $businessId);
    }

    /**
     * Generate a purchase invoice number.
     *
     * @param int $businessId
     * @return string
     */
    public function generatePurchaseInvoiceNumber(int $businessId): string
    {
        return $this->generateInvoiceNumber('P', $businessId);
    }

    /**
     * Generate a due collect invoice number.
     *
     * @param int $businessId
     * @return string
     */
    public function generateDueCollectInvoiceNumber(int $businessId): string
    {
        return $this->generateInvoiceNumber('D', $businessId);
    }
}
