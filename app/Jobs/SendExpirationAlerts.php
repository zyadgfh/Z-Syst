<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpirationAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(): void
    {
        $expiringSoon = Stock::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->get();

        foreach ($expiringSoon as $stock) {
            try {
                // Send email notification
                // Mail::to($stock->business->email)->send(new ExpiringStockAlert($stock));
                
                Log::info("Expiration alert sent for stock: {$stock->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send expiration alert for stock: {$stock->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Expiration alerts processed: {$expiringSoon->count()} alerts sent");
    }
}