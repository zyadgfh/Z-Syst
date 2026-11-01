<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register the custom "supabase" user provider declared in
        // config/auth.php. Without this the auth manager fails to resolve
        // the provider and the whole app 500s at boot.
        Auth::provider('supabase', function ($app, array $config) {
            return new \App\Auth\SupabaseUserProvider(
                $app->make(\App\Support\Supabase\SupabaseAuthClient::class),
                $config['model'] ?? \App\Models\User::class,
            );
        });

        // Optional: Define gates here if needed
        Gate::define('view-dashboard', function ($user) {
            return in_array($user->role, ['admin', 'superadmin']);
        });
    }
}
