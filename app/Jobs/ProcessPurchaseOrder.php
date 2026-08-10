<?php

namespace App\Jobs;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPurchaseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public int $purchaseOrderId
    ) {
    }

    public function handle(): void
    {
        $purchaseOrder = PurchaseOrder::find($this->purchaseOrderId);
        
        if (! $purchaseOrder) {
            Log::warning("Purchase order not found: {$this->purchaseOrderId}");
            return;
        }

        // Process the purchase order
        try {
            // Add your business logic here
            $purchaseOrder->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            Log::info("Purchase order processed successfully: {$this->purchaseOrderId}");
        } catch (\Exception $e) {
            Log::error("Failed to process purchase order: {$this->purchaseOrderId}", [
                'error' => $e->getMessage(),
            ]);
            
            $this->release(60); // Release back to queue for retry after 60 seconds
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Purchase order job failed permanently: {$this->purchaseOrderId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}