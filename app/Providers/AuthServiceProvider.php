<?php

namespace App\Providers;

use App\Models\StockAudit;
use App\Models\FinancialAuditLog;
use App\Policies\StockAuditPolicy;
use App\Policies\FinancialAuditPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        StockAudit::class => StockAuditPolicy::class,
        FinancialAuditLog::class => FinancialAuditPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
