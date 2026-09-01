<?php

namespace App\Console\Commands;

use App\Services\DoctorAttentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateDoctorAttentionScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctor-attention:calculate {--business-id=} {--branch-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate attention scores for all doctors and generate alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessId = $this->option('business-id');
        $branchId = $this->option('branch-id');

        try {
            $service = new DoctorAttentionService;

            if ($businessId) {
                // Calculate for specific business
                $service->calculateScoresForBusiness($businessId, $branchId);
                $this->info("Attention scores calculated for business {$businessId}");
            } else {
                // Calculate for all businesses
                // This would typically be handled by a queue worker in production
                $this->info('Skipping - business-id is required');
                $this->info('Use: php artisan doctor-attention:calculate --business-id=1');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Failed to calculate doctor attention scores', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
                'branch_id' => $branchId,
            ]);

            $this->error("Failed to calculate attention scores: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
