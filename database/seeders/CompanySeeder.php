<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::factory()
            ->count(20)
            ->state(fn () => ['branch_limit_updated_by' => 1])
            ->create();

        Company::factory()
            ->unlimited()
            ->count(5)
            ->create();
    }
}
