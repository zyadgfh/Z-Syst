<?php

namespace App\Providers;

use App\Events\StockTransferApproved;
use App\Events\StockTransferReceived;
use App\Events\StockTransferRejected;
use App\Events\StockTransferShipped;
use App\Events\SubscriptionChanged;
use App\Listeners\ResetBranchLimitOnSubscriptionChange;
use App\Listeners\SendStockTransferApprovedNotification;
use App\Listeners\SendStockTransferReceivedNotification;
use App\Listeners\SendStockTransferRejectedNotification;
use App\Listeners\SendStockTransferShippedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscriptionChanged::class => [
            ResetBranchLimitOnSubscriptionChange::class,
        ],
        StockTransferApproved::class => [
            SendStockTransferApprovedNotification::class,
        ],
        StockTransferRejected::class => [
            SendStockTransferRejectedNotification::class,
        ],
        StockTransferShipped::class => [
            SendStockTransferShippedNotification::class,
        ],
        StockTransferReceived::class => [
            SendStockTransferReceivedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
