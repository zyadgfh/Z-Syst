<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_id' => Supplier::factory(),
            'branch_id' => Branch::factory(),
            'grn_number' => fake()->unique()->bothify('GRN-#####'),
            'notes' => fake()->sentence(),
            'received_by' => User::factory(),
        ];
    }
}
