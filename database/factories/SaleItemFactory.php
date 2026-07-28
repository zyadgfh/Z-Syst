<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 50);
        $discount = $this->faker->randomFloat(2, 0, 5);
        $taxRate = $this->faker->randomFloat(2, 0, 15);
        $taxAmount = round(($unitPrice * $quantity - $discount) * ($taxRate / 100), 3);
        $lineTotal = round(($unitPrice * $quantity) - $discount + $taxAmount, 3);

        return [
            'id' => (string) Str::uuid(),
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'name_snapshot' => $this->faker->words(3, true),
            'inventory_id' => null,
            'batch_number' => $this->faker->bothify('BATCH-#####'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cost_price' => $unitPrice * 0.7,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $unitPrice * $quantity,
            'line_total' => $lineTotal,
            'is_gift' => false,
            'notes' => null,
        ];
    }

    public function forSale(Sale $sale): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_id' => $sale->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'name_snapshot' => $product->name ?? $product->product_name ?? $product->generic_name ?? 'Product',
        ]);
    }
}

