<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExpiryAlertService;
use Illuminate\Console\Command;

class CheckExpiringProducts extends Command
{
    protected $signature = 'pharmacy:check-expiry {--days=30 : Number of days to check}';

    protected $description = 'Check for expiring products and send alerts to managers';

    public function __construct(
        private readonly ExpiryAlertService $expiryAlertService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Checking for products expiring within {$days} days...");

        $results = $this->expiryAlertService->runPeriodicCheck();

        $totalCompanies = count($results);
        $totalProducts = 0;

        foreach ($results as $companyId => $result) {
            $totalProducts += $result['products_count'] ?? 0;
            $this->line("Company #{$companyId}: {$result['message']}");
        }

        $this->info("Checked {$totalCompanies} companies. Found {$totalProducts} expiring products.");

        return self::SUCCESS;
    }
}