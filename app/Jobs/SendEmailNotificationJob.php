<?php

namespace App\Jobs;

use App\Mail\SendMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 60;
    public $maxExceptions = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $template,
        public array $data = [],
        public ?int $businessId = null,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        Log::info("Sending email notification", [
            'to' => $this->toEmail,
            'subject' => $this->subject,
            'template' => $this->template,
        ]);

        Mail::to($this->toEmail)->send(
            new SendMail($this->subject, $this->template, $this->data)
        );

        Log::info("Email sent successfully", [
            'to' => $this->toEmail,
            'subject' => $this->subject,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendEmailNotificationJob failed permanently", [
            'to' => $this->toEmail,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
