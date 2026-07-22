<?php

declare(strict_types=1);

namespace App\Modules\Products\Domain\DTOs;

/**
 * Create Product Data Transfer Object
 *
 * Carries validated data for creating a new product.
 * Supports optional initial stock tracking with batch_number, expiry_date, and quantity.
 */
class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $generic_name,
        public readonly ?string $barcode,
        public readonly ?string $sku,
        public readonly ?float $purchase_price,
        public readonly ?float $sale_price,
        public readonly ?string $category_id,
        public readonly ?string $manufacturer_id,
        public readonly ?int $reorder_level,
        public readonly ?string $company_id,
        public readonly ?string $created_by,

        // Initial Stock fields (optional)
        public readonly ?string $batch_number = null,
        public readonly ?string $expiry_date = null,
        public readonly ?float $quantity_available = null,
        public readonly ?string $branch_id = null,
    ) {}

    /**
     * Create from validated request array.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            generic_name: $data['generic_name'] ?? null,
            barcode: $data['barcode'] ?? null,
            sku: $data['sku'] ?? null,
            purchase_price: isset($data['purchase_price']) ? (float) $data['purchase_price'] : null,
            sale_price: isset($data['sale_price']) ? (float) $data['sale_price'] : null,
            category_id: $data['category_id'] ?? null,
            manufacturer_id: $data['manufacturer_id'] ?? null,
            reorder_level: isset($data['reorder_level']) ? (int) $data['reorder_level'] : null,
            company_id: $data['company_id'] ?? null,
            created_by: $data['created_by'] ?? null,

            // Initial Stock
            batch_number: $data['batch_number'] ?? null,
            expiry_date: $data['expiry_date'] ?? null,
            quantity_available: isset($data['quantity_available']) ? (float) $data['quantity_available'] : null,
            branch_id: $data['branch_id'] ?? null,
        );
    }

    /**
     * Convert back to array for model creation.
     * Maps 'reorder_level' → 'reorder_point' (DB column).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'product_name' => $this->name,
            'generic_name' => $this->generic_name,
            'barcode' => $this->barcode,
            'product_code' => $this->sku,
            'purchase_price' => $this->purchase_price,
            'sales_price' => $this->sale_price,
            'category_id' => $this->category_id,
            'manufacturer_id' => $this->manufacturer_id,
            'reorder_point' => $this->reorder_level,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->created_by,
        ];
    }
}

