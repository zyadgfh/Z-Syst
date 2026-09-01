<?php

namespace App\Console\Commands;

use App\Services\CacheWarmingService;
use Illuminate\Console\Command;

class CacheWarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--business= : Warm cache for specific business ID}
                            {--user= : Warm cache for specific user ID}
                            {--dashboard : Warm dashboard cache only}
                            {--all : Warm all application cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache for better performance';

    protected CacheWarmingService $cacheWarmingService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheWarmingService $cacheWarmingService)
    {
        parent::__construct();
        $this->cacheWarmingService = $cacheWarmingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Warming up cache...');
        $this->newLine();

        try {
            if ($this->option('business')) {
                $businessId = (int) $this->option('business');
                $this->info("Warming cache for business ID: {$businessId}");
                $this->cacheWarmingService->warmupBusiness($businessId);
                $this->info("✅ Business cache warmed up successfully");
            } elseif ($this->option('user')) {
                $userId = (int) $this->option('user');
                $user = \App\Models\User::find($userId);
                
                if (!$user) {
                    $this->error("❌ User not found with ID: {$userId}");
                    return Command::FAILURE;
                }
                
                $this->info("Warming cache for user: {$user->name}");
                $this->cacheWarmingService->warmupForUser($user);
                $this->info("✅ User cache warmed up successfully");
            } elseif ($this->option('dashboard')) {
                $businessId = auth()->id() ? auth()->user()->business_id : 1;
                $this->info("Warming dashboard cache for business ID: {$businessId}");
                $this->cacheWarmingService->warmupDashboard($businessId);
                $this->info("✅ Dashboard cache warmed up successfully");
            } else {
                $this->info("Warming application cache...");
                $this->cacheWarmingService->warmupApplication();
                $this->info("✅ Application cache warmed up successfully");
            }

            $this->newLine();
            $this->info('🎉 Cache warming completed!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Cache warming failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
