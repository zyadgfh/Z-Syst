<?php

namespace App\Providers;

use App\Events\StockTransferApproved;
use App\Events\StockTransferCancelled;
use App\Events\StockTransferReceived;
use App\Events\StockTransferRejected;
use App\Events\StockTransferShipped;
use App\Events\SubscriptionChanged;
use App\Listeners\ResetBranchLimitOnSubscriptionChange;
use App\Listeners\SendStockTransferApprovedNotification;
use App\Listeners\SendStockTransferCancelledNotification;
use App\Listeners\SendStockTransferReceivedNotification;
use App\Listeners\SendStockTransferRejectedNotification;
use App\Listeners\SendStockTransferShippedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Stock Transfer Events
        StockTransferApproved::class => [
            SendStockTransferApprovedNotification::class,
        ],
        StockTransferCancelled::class => [
            SendStockTransferCancelledNotification::class,
        ],
        StockTransferReceived::class => [
            SendStockTransferReceivedNotification::class,
        ],
        StockTransferRejected::class => [
            SendStockTransferRejectedNotification::class,
        ],
        StockTransferShipped::class => [
            SendStockTransferShippedNotification::class,
        ],

        // Subscription Events
        SubscriptionChanged::class => [
            ResetBranchLimitOnSubscriptionChange::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
