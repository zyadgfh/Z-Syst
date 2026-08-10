<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Events\ProductUpdated;
use App\Domain\Product\Events\ProductDeleted;
use App\Domain\Product\Events\StockChanged;
use App\Services\ErrorLoggingService;
use Illuminate\Support\Facades\Log;

class ProductEventSubscriber
{
    public function __construct(
        private ErrorLoggingService $errorLogger
    ) {}

    /**
     * Handle product created event.
     */
    public function handleProductCreated(ProductCreated $event): void
    {
        Log::info('Product created', [
            'product_id' => $event->productId,
            'business_id' => $event->businessId,
            'product_name' => $event->productName,
        ]);

        // Trigger additional actions:
        // - Update search index
        // - Send notifications
        // - Update analytics
    }

    /**
     * Handle product updated event.
     */
    public function handleProductUpdated(ProductUpdated $event): void
    {
        Log::info('Product updated', [
            'product_id' => $event->productId,
            'business_id' => $event->businessId,
            'changes' => array_keys(array_diff_assoc($event->newData, $event->oldData)),
        ]);

        // Trigger additional actions:
        // - Update search index
        // - Invalidate cache
        // - Send notifications if price changed
    }

    /**
     * Handle product deleted event.
     */
    public function handleProductDeleted(ProductDeleted $event): void
    {
        Log::info('Product deleted', [
            'product_id' => $event->productId,
            'business_id' => $event->businessId,
            'product_name' => $event->productName,
        ]);

        // Trigger additional actions:
        // - Remove from search index
        // - Archive data
        // - Update analytics
    }

    /**
     * Handle stock changed event.
     */
    public function handleStockChanged(StockChanged $event): void
    {
        Log::info('Stock changed', [
            'stock_id' => $event->stockId,
            'product_id' => $event->productId,
            'business_id' => $event->businessId,
            'old_quantity' => $event->oldQuantity,
            'new_quantity' => $event->newQuantity,
            'change_type' => $event->changeType,
        ]);

        // Trigger additional actions:
        // - Check for low stock alerts
        // - Update inventory analytics
        // - Send notifications if critical
        // - Invalidate cache

        // Check for low stock
        if ($event->newQuantity <= 10) {
            $this->errorLogger->logBusinessError('Low stock alert', [
                'product_id' => $event->productId,
                'quantity' => $event->newQuantity,
                'business_id' => $event->businessId,
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            ProductCreated::class => 'handleProductCreated',
            ProductUpdated::class => 'handleProductUpdated',
            ProductDeleted::class => 'handleProductDeleted',
            StockChanged::class => 'handleStockChanged',
        ];
    }
}