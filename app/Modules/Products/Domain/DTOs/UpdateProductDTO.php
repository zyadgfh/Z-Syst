<?php

declare(strict_types=1);

namespace App\Modules\Products\Domain\DTOs;

/**
 * Update Product Data Transfer Object
 *
 * Carries validated data for updating an existing product.
 * All fields are optional — only provided fields will be updated.
 * Uses string for UUID foreign keys.
 */
class UpdateProductDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $generic_name = null,
        public readonly ?string $barcode = null,
        public readonly ?string $sku = null,
        public readonly ?float $purchase_price = null,
        public readonly ?float $sale_price = null,
        public readonly ?string $category_id = null,
        public readonly ?string $manufacturer_id = null,
        public readonly ?int $reorder_level = null,
        public readonly ?string $updated_by = null,
    ) {}

    /**
     * Create from validated request array.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            generic_name: $data['generic_name'] ?? null,
            barcode: $data['barcode'] ?? null,
            sku: $data['sku'] ?? null,
            purchase_price: isset($data['purchase_price']) ? (float) $data['purchase_price'] : null,
            sale_price: isset($data['sale_price']) ? (float) $data['sale_price'] : null,
            category_id: $data['category_id'] ?? null,
            manufacturer_id: $data['manufacturer_id'] ?? null,
            reorder_level: isset($data['reorder_level']) ? (int) $data['reorder_level'] : null,
            updated_by: $data['updated_by'] ?? null,
        );
    }

    /**
     * Convert to array, filtering out null values.
     * Maps reorder_level → reorder_point (DB column).
     */
    public function toArray(): array
    {
        return array_filter([
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
            'updated_by' => $this->updated_by,
        ], fn ($value) => $value !== null);
    }
}

