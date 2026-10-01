<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\GoodsReceivedNote;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Party::factory()->state(['type' => 'supplier']),
            'business_id' => Business::factory(),
            'grn_number' => 'GRN-'.now()->format('Ymd').'-'.fake()->unique()->regexify('[0-9]{6}'),
            'received_date' => now(),
            'location' => fake()->optional()->word(),
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ];
    }
}
