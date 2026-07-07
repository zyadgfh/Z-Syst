<?php

namespace Database\Factories;

use App\Models\StockTransferItem;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransferItem>
 */
class StockTransferItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockTransferItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stockTransfer = StockTransfer::factory()->create();
        $product = Product::factory()->create(['company_id' => $stockTransfer->company_id]);
        $productStock = ProductStock::factory()->create([
            'company_id' => $stockTransfer->company_id,
            'product_id' => $product->id,
            'branch_id' => $stockTransfer->from_branch_id,
        ]);

        $quantity = $this->faker->numberBetween(1, 100);
        $unitCost = $this->faker->randomFloat(2, 1, 100);

        return [
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
            'product_stock_id' => $productStock->id,
            'quantity_requested' => $quantity,
            'quantity_sent' => 0,
            'quantity_received' => 0,
            'batch_number' => $this->faker->optional()->bothify('BATCH-####'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years'),
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item has been sent.
     *
     * @return \Database\Factories\StockTransferItemFactory
     */
    public function sent(): self
    {
        return $this->state(fn (array $attributes) => [
            'quantity_sent' => $attributes['quantity_requested'],
        ]);
    }

    /**
     * Indicate that the item has been received.
     *
     * @return \Database\Factories\StockTransferItemFactory
     */
    public function received(): self
    {
        return $this->state(fn (array $attributes) => [
            'quantity_sent' => $attributes['quantity_requested'],
            'quantity_received' => $attributes['quantity_requested'],
        ]);
    }

    /**
     * Indicate that the item has a partial send.
     *
     * @return \Database\Factories\StockTransferItemFactory
     */
    public function partiallySent(): self
    {
        return $this->state(fn (array $attributes) => [
            'quantity_sent' => $attributes['quantity_requested'] * 0.5,
        ]);
    }

    /**
     * Indicate that the item has a partial receive.
     *
     * @return \Database\Factories\StockTransferItemFactory
     */
    public function partiallyReceived(): self
    {
        return $this->state(fn (array $attributes) => [
            'quantity_sent' => $attributes['quantity_requested'],
            'quantity_received' => $attributes['quantity_requested'] * 0.8,
        ]);
    }
}
