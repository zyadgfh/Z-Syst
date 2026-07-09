<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StockTransfer;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        User::class => UserPolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
        StockTransfer::class => StockTransferPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Define a simple gate that maps the 'super-admin' ability
        // to the User::isSuperAdmin() helper. This powers the
        // route-level `can:super-admin` middleware used in routes/api.php.
        Gate::define('super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}
