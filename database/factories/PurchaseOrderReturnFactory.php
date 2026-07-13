<?php

namespace Database\Factories;

use App\Models\PurchaseOrderReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderReturnFactory extends Factory
{
    protected $model = PurchaseOrderReturn::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'purchase_order_id' => 1,
            'supplier_id' => 1,
            'branch_id' => 1,
            'return_number' => 'POR-'.strtoupper($this->faker->bothify('????####')),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}
