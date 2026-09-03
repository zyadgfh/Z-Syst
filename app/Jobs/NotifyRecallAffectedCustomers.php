<?php

namespace App\Jobs;

use App\Models\RecallEvent;
use App\Services\RecallNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyRecallAffectedCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public RecallEvent $recall,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(RecallNotificationService $notificationService): void
    {
        Log::info('Starting recall customer notifications', [
            'recall_id' => $this->recall->id,
            'product_id' => $this->recall->product_id,
        ]);

        $result = $notificationService->notifyAffectedCustomers($this->recall);

        Log::info('Recall customer notifications complete', [
            'recall_id' => $this->recall->id,
            'emails_sent' => $result['notifications_sent']['emails'],
            'sms_sent' => $result['notifications_sent']['sms'],
            'failed' => $result['notifications_sent']['failed'],
            'customers_notified' => count($result['customers']),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Recall notification job failed', [
            'recall_id' => $this->recall->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
