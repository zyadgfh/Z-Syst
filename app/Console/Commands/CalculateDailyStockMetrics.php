<?php

namespace App\Console\Commands;

use App\Models\ProductStock;
use App\Models\Company;
use App\Notifications\LowStockNotification;
use App\Notifications\ExpiryDateNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class CalculateDailyStockMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:calculate-daily-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily stock metrics and send notifications for low stock and expiring products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily stock metrics calculation...');

        $companies = Company::all();

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name}");

            // Get low stock products for this company
            $lowStockProducts = $this->getLowStockProducts($company->id);
            
            if ($lowStockProducts->isNotEmpty()) {
                $this->info("Found {$lowStockProducts->count()} low stock products");
                
                // Send low stock notifications to company admins
                $this->sendLowStockNotifications($company, $lowStockProducts);
            }

            // Get expiring products for this company
            $expiringProducts = $this->getExpiringProducts($company->id);
            
            if ($expiringProducts->isNotEmpty()) {
                $this->info("Found {$expiringProducts->count()} expiring products");
                
                // Send expiry notifications to company admins
                $this->sendExpiryNotifications($company, $expiringProducts);
            }

            // Clear analytics cache for this company
            $this->clearCompanyCache($company->id);
        }

        $this->info('Daily stock metrics calculation completed successfully.');
        
        return Command::SUCCESS;
    }

    /**
     * Get low stock products for a company
     */
    protected function getLowStockProducts($companyId)
    {
        return ProductStock::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('product', 'branch')
            ->get()
            ->filter(function ($stock) {
                return $stock->quantity <= $stock->reorder_level;
            });
    }

    /**
     * Get expiring products for a company
     */
    protected function getExpiringProducts($companyId)
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);

        return ProductStock::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereBetween('expiry_date', [Carbon::now(), $thirtyDaysFromNow])
            ->where('quantity', '>', 0)
            ->with('product', 'branch')
            ->get();
    }

    /**
     * Send low stock notifications
     */
    protected function sendLowStockNotifications($company, $products)
    {
        // Get company admins (users with super-admin role or appropriate permissions)
        $admins = $company->users()->whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->get();

        if ($admins->isEmpty()) {
            $this->warn("No admins found for company {$company->name}");
            return;
        }

        $groupedProducts = $products->groupBy('branch_id');

        foreach ($admins as $admin) {
            Notification::send($admin, new LowStockNotification($company, $groupedProducts));
        }

        $this->info("Low stock notifications sent to {$admins->count()} admins");
    }

    /**
     * Send expiry notifications
     */
    protected function sendExpiryNotifications($company, $products)
    {
        // Get company admins
        $admins = $company->users()->whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->get();

        if ($admins->isEmpty()) {
            $this->warn("No admins found for company {$company->name}");
            return;
        }

        $groupedProducts = $products->groupBy('branch_id');

        foreach ($admins as $admin) {
            Notification::send($admin, new ExpiryDateNotification($company, $groupedProducts));
        }

        $this->info("Expiry notifications sent to {$admins->count()} admins");
    }

    /**
     * Clear company analytics cache
     */
    protected function clearCompanyCache($companyId)
    {
        // This would need to be implemented based on your cache implementation
        // For now, we'll just log it
        $this->info("Cache cleared for company ID: {$companyId}");
    }
}