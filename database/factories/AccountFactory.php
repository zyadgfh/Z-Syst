<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            AccountType::ASSET,
            AccountType::LIABILITY,
            AccountType::EQUITY,
            AccountType::REVENUE,
            AccountType::EXPENSE,
        ]);

        return [
            'business_id' => Business::factory(),
            'account_type_id' => AccountType::where('name', $type)->first()?->id ?? AccountType::factory(),
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'is_system' => false,
            'is_active' => true,
            'opening_balance' => 0,
        ];
    }

    public function asAsset(): static
    {
        return $this->state(fn () => [
            'account_type_id' => AccountType::where('name', AccountType::ASSET)->first()?->id,
        ]);
    }

    public function asLiability(): static
    {
        return $this->state(fn () => [
            'account_type_id' => AccountType::where('name', AccountType::LIABILITY)->first()?->id,
        ]);
    }

    public function asEquity(): static
    {
        return $this->state(fn () => [
            'account_type_id' => AccountType::where('name', AccountType::EQUITY)->first()?->id,
        ]);
    }

    public function asRevenue(): static
    {
        return $this->state(fn () => [
            'account_type_id' => AccountType::where('name', AccountType::REVENUE)->first()?->id,
        ]);
    }

    public function asExpense(): static
    {
        return $this->state(fn () => [
            'account_type_id' => AccountType::where('name', AccountType::EXPENSE)->first()?->id,
        ]);
    }
}
