<?php

namespace DatabaseFactories;

use AppModelsBusiness;
use AppModelsGoodsReceivedNote;
use AppModelsParty;
use IlluminateDatabaseEloquentFactoriesFactory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => null,
            'supplier_id' => Party::factory()->state(['type' => 'supplier']),
            'business_id' => Business::factory(),
            'branch_id' => null,
            'received_by' => null,
            'verified_by' => null,
            'grn_number' => GoodsReceivedNote::generateGRNNumber(),
            'received_date' => now(),
            'location' => fake()->optional()->address(),
            'status' => GoodsReceivedNote::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
            'verified_at' => null,
        ];
    }
}
