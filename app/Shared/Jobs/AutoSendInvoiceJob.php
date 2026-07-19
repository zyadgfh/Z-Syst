<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\Invoice\InvoiceNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoSendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Sale $sale,
        public array $channels = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InvoiceNotificationService $notificationService): void
    {
        try {
            // Get default channels from company settings
            if (empty($this->channels)) {
                $settings = $this->sale->company->invoice_notification_settings ?? [];
                $defaultChannels = [];
                
                if ($settings['send_as_image'] ?? false) {
                    $defaultChannels['whatsapp'] = ['sendImage' => true];
                }
                if ($settings['send_as_pdf'] ?? false) {
                    $defaultChannels['whatsapp'] = ['sendPDF' => true];
                }
                
                $this->channels = $defaultChannels;
            }

            $result = $notificationService->sendInvoice(
                sale: $this->sale,
                channels: $this->channels,
                options: []
            );

            Log::info('Auto invoice sent', [
                'sale_id' => $this->sale->id,
                'channels' => array_keys($this->channels),
                'results' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Auto send invoice failed', [
                'sale_id' => $this->sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AutoSendInvoiceJob failed permanently', [
            'sale_id' => $this->sale->id,
            'error' => $exception->getMessage(),
        ]);
    }
}