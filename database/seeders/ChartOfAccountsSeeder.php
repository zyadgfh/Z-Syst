<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Business;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed the chart of accounts for all existing businesses.
     */
    public function run(): void
    {
        // Ensure account types exist first
        AccountType::seed();

        $businesses = Business::all();

        foreach ($businesses as $business) {
            Account::seedForBusiness($business->id);
            $this->command->info("Chart of accounts seeded for Business #{$business->id} ({$business->companyName}).");
        }

        $this->command->info('Chart of Accounts seeding complete for all businesses.');
    }
}
