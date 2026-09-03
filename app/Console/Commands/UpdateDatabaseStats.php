<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateDatabaseStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:update-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update database statistics for query optimization';

    /**
     * Tables to analyze
     *
     * @var array
     */
    protected $tables = [
        'products',
        'stocks',
        'sales',
        'parties',
        'purchases',
        'purchase_details',
        'sale_details',
        'categories',
        'manufacturers',
        'units',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating database statistics...');
        $startTime = microtime(true);

        try {
            foreach ($this->tables as $table) {
                $this->info("Analyzing table: {$table}");
                
                DB::statement("ANALYZE {$table}");
                
                $this->info("✓ {$table} analyzed successfully");
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✓ Database statistics updated successfully in {$duration} seconds");
            
            Log::info('Database statistics updated', [
                'duration' => $duration,
                'tables' => $this->tables,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Failed to update database statistics: {$e->getMessage()}");
            
            Log::error('Database statistics update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
