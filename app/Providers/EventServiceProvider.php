<?php

namespace App\Providers;

use App\Events\SubscriptionChanged;
use App\Listeners\ResetBranchLimitOnSubscriptionChange;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscriptionChanged::class => [
            ResetBranchLimitOnSubscriptionChange::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
