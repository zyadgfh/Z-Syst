<?php

namespace App\Console\Commands;

use App\Services\BranchLimitService;
use Illuminate\Console\Command;

class CheckBranchLimits extends Command
{
    protected $signature = 'branches:check-limits';

    protected $description = 'Check all companies branch limits and send notifications';

    public function handle(BranchLimitService $branchLimitService): int
    {
        $stats = $branchLimitService->getAllBranchStats();

        $this->info('Branch Limit Statistics:');
        $this->info('Total Companies: '.$stats['total_companies']);
        $this->info('Unlimited Companies: '.$stats['unlimited_companies']);
        $this->info('Companies Near Limit (>=80%): '.$stats['companies_near_limit']);
        $this->info('Companies At Limit: '.$stats['companies_at_limit']);
        $this->info('Total Branches: '.$stats['total_branches']);

        return Command::SUCCESS;
    }
}
