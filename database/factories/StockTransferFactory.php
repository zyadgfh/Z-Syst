<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory()->create();
        $branches = Branch::factory()->count(2)->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return [
            'company_id' => $company->id,
            'from_branch_id' => $branches[0]->id,
            'to_branch_id' => $branches[1]->id,
            'requested_by' => $user->id,
            'transfer_number' => 'STF-'.str_pad($company->id, 4, '0', STR_PAD_LEFT).'-'.now()->format('Ymd').'-TEST',
            'status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
            'requested_at' => now(),
            'total_items' => 0,
            'total_quantity' => 0,
            'total_value' => 0,
        ];
    }

    /**
     * Indicate that the transfer is approved.
     */
    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the transfer is in transit.
     */
    public function inTransit(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
            'approved_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'approved_at' => now()->subHours(2),
            'shipped_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'shipped_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the transfer is received.
     */
    public function received(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
            'approved_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'approved_at' => now()->subDay(),
            'shipped_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'shipped_at' => now()->subHours(20),
            'received_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'received_at' => now()->subHours(18),
        ]);
    }

    /**
     * Indicate that the transfer is rejected.
     */
    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory()->create(['company_id' => $attributes['company_id']]),
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the transfer is cancelled.
     */
    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
