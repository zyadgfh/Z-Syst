<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Business;
use App\Models\FiscalPeriod;
use Illuminate\Console\Command;

class InitializeBookkeeping extends Command
{
    protected $signature = 'accounting:initialize
                            {--business= : Run initialization for a specific business ID only}
                            {--force : Re-seed chart of accounts even if already initialized}';

    protected $description = 'Initialize double-entry bookkeeping: seed account types, chart of accounts, and fiscal periods for existing businesses';

    public function handle(): int
    {
        $this->info('📊 Initializing bookkeeping system...');

        // Step 1: Seed account types
        $this->info('  → Seeding account types...');
        AccountType::seed();
        $this->info('  ✅ Account types seeded (Asset, Liability, Equity, Revenue, Expense).');

        // Step 2: Determine target businesses
        $businessId = $this->option('business') ? (int) $this->option('business') : null;
        $force = $this->option('force');

        $query = Business::query();
        if ($businessId) {
            $query->where('id', $businessId);
        }
        $businesses = $query->get();

        if ($businesses->isEmpty()) {
            $this->error('  ❌ No businesses found.');
            return Command::FAILURE;
        }

        $this->info("  → Found {$businesses->count()} business(es).");

        $initialized = 0;
        $skipped = 0;

        foreach ($businesses as $business) {
            $hasAccounts = Account::where('business_id', $business->id)->exists();

            if ($hasAccounts && !$force) {
                $this->warn("  ⏭  Business #{$business->id} ({$business->companyName}) — already initialized. Use --force to re-seed.");
                $skipped++;
                continue;
            }

            $this->info("  → Seeding chart of accounts for Business #{$business->id} ({$business->companyName})...");
            Account::seedForBusiness($business->id);

            // Create fiscal period for current year if none exists
            $currentYear = now()->year;
            $periodName = "FY{$currentYear}";

            FiscalPeriod::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $periodName,
                ],
                [
                    'start_date' => "{$currentYear}-01-01",
                    'end_date' => "{$currentYear}-12-31",
                    'is_closed' => false,
                ]
            );

            $accountCount = Account::where('business_id', $business->id)->count();
            $this->info("  ✅ Business #{$business->id} — {$accountCount} accounts created, fiscal period '{$periodName}' initialized.");
            $initialized++;
        }

        $this->info('');
        $this->info("🎉 Bookkeeping initialization complete: {$initialized} initialized, {$skipped} skipped.");

        return Command::SUCCESS;
    }
}
