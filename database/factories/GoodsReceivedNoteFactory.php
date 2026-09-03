<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\GoodsReceivedNote;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition(): array
    {
        static $grnCounter = 0;
        $grnCounter++;
        $date = now()->format('Ymd');
        $seq = str_pad($grnCounter, 6, '0', STR_PAD_LEFT);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_id' => Party::factory(),
            'business_id' => Business::factory(),
            'branch_id' => null,
            'received_by' => User::factory(),
            'verified_by' => null,
            'grn_number' => 'GRN-' . $date . '-' . $seq,
            'received_date' => fake()->dateTimeBetween('-1 week', 'now'),
            'location' => fake()->optional()->city(),
            'status' => GoodsReceivedNote::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
            'verified_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceivedNote::STATUS_DRAFT,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceivedNote::STATUS_VERIFIED,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceivedNote::STATUS_ACCEPTED,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceivedNote::STATUS_REJECTED,
        ]);
    }
}
