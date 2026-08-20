<?php

namespace App\Observers;

use App\Events\CacheInvalidationEvent;
use Illuminate\Database\Eloquent\Model;

class CacheInvalidationObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->fireEvent('created', $model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->fireEvent('updated', $model);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->fireEvent('deleted', $model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->fireEvent('restored', $model);
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->fireEvent('force_deleted', $model);
    }

    /**
     * Fire cache invalidation event
     */
    protected function fireEvent(string $action, Model $model): void
    {
        $businessId = null;

        // Try to get business_id from model
        if (property_exists($model, 'business_id') || method_exists($model, 'getAttribute')) {
            $businessId = $model->business_id ?? null;
        }

        // Fire the event
        event(new CacheInvalidationEvent(
            action: $action,
            modelClass: get_class($model),
            modelId: $model->getKey(),
            businessId: $businessId,
            data: [
                'original' => $model->getOriginal(),
                'changes' => $model->getChanges(),
            ]
        ));
    }
}
