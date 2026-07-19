<?php

namespace App\Providers;

use App\Core\Events\StockTransferApproved;
use App\Core\Events\StockTransferCancelled;
use App\Core\Events\StockTransferReceived;
use App\Core\Events\StockTransferRejected;
use App\Core\Events\StockTransferShipped;
use App\Core\Events\SubscriptionChanged;
use App\Shared\Listeners\ResetBranchLimitOnSubscriptionChange;
use App\Shared\Listeners\SendStockTransferApprovedNotification;
use App\Shared\Listeners\SendStockTransferCancelledNotification;
use App\Shared\Listeners\SendStockTransferReceivedNotification;
use App\Shared\Listeners\SendStockTransferRejectedNotification;
use App\Shared\Listeners\SendStockTransferShippedNotification;
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

