<?php

namespace App\Providers;

use App\Events\CacheInvalidationEvent;
use App\Listeners\CacheInvalidationListener;
use App\Listeners\WarmUserCacheOnLogin;
use App\Observers\CacheInvalidationObserver;
use App\Models\Business;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Party;
use App\Models\PlanSubscribe;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Domain\Product\Listeners\ProductEventSubscriber;

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
        CacheInvalidationEvent::class => [
            CacheInvalidationListener::class,
        ],
        Login::class => [
            WarmUserCacheOnLogin::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        ProductEventSubscriber::class,
    ];

    /**
     * The model observers to register.
     *
     * @var array<class-string, class-string>
     */
    protected $observers = [
        Sale::class => CacheInvalidationObserver::class,
        Purchase::class => CacheInvalidationObserver::class,
        Product::class => CacheInvalidationObserver::class,
        Stock::class => CacheInvalidationObserver::class,
        Party::class => CacheInvalidationObserver::class,
        PlanSubscribe::class => CacheInvalidationObserver::class,
        Subscription::class => CacheInvalidationObserver::class,
        Setting::class => CacheInvalidationObserver::class,
        User::class => CacheInvalidationObserver::class,
        Business::class => CacheInvalidationObserver::class,
        Income::class => CacheInvalidationObserver::class,
        Expense::class => CacheInvalidationObserver::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        $this->registerObservers();
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        foreach ($this->observers as $model => $observer) {
            $model::observe($observer);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
