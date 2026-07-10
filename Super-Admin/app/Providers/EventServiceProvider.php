<?php

namespace App\Providers;

use App\Events\MultiPaymentProcessed;
use App\Events\SaleSms;
use App\Events\DuePaymentReceived;
use App\Events\PurchaseSms;
use App\Listeners\HandleMultiPayments;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        DuePaymentReceived::class => [
            \App\Listeners\GenerateSmsForDuePayment::class,
        ],
        SaleSms::class => [
            \App\Listeners\GenerateSmsForSale::class,
        ],
        PurchaseSms::class => [
            \App\Listeners\GenerateSmsForPurchase::class,
        ],
        MultiPaymentProcessed::class => [
            HandleMultiPayments::class,
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
