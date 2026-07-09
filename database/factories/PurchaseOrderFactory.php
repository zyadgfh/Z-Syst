<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'supplier_id' => Supplier::factory(),
            'branch_id' => Branch::factory(),
            'uuid' => fake()->uuid(),
            'po_number' => 'PO-' . strtoupper(fake()->bothify('????####')),
            'status' => 'draft',
            'subtotal' => fake()->randomFloat(2, 100, 10000),
            'discount' => fake()->randomFloat(2, 0, 500),
            'tax' => fake()->randomFloat(2, 0, 1000),
            'total' => fake()->randomFloat(2, 100, 10000),
            'expected_delivery_date' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}