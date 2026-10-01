<?php

namespace App\Services;

use App\Mail\BusinessMail;
use App\Notifications\SendNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Unified notification dispatcher.
 *
 * Centralizes the three channels used across the application:
 *  - in-app (database) notifications via the existing SendNotification,
 *  - transactional emails via the BusinessMail mailable,
 *  - SMS through a driver configured in config/services.php
 *    (defaults to the "log" driver until an SMS gateway package is added).
 *
 * Every channel degrades gracefully: failures are logged and never bubble
 * up to break the business operation that triggered them.
 */
class NotificationService
{
    /**
     * Send an in-app (database) notification to a set of users.
     *
     * @param  iterable  $users  Users that use the Notifiable trait.
     * @param  string  $subject  Short subject / title.
     * @param  string  $message  Notification body.
     * @param  array  $extra  Optional extras, supports a "url" key.
     * @return int Number of notifications dispatched.
     */
    public function notifyUsers(iterable $users, string $subject, string $message, array $extra = []): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if (! $user) {
                continue;
            }

            try {
                $user->notify(new SendNotification([
                    'id' => uniqid('ntf_', true),
                    'user' => $user->name ?? null,
                    'message' => $subject.': '.$message,
                    'url' => $extra['url'] ?? null,
                ]));

                $sent++;
            } catch (Throwable $e) {
                Log::warning('In-app notification failed', [
                    'user_id' => $user->id ?? null,
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Send a transactional email to a single address.
     *
     * @param  string|null  $email  Recipient address; skipped when empty.
     * @param  string  $subject  Email subject.
     * @param  array  $lines  Body paragraphs.
     * @param  array|null  $table  Optional table ['headers' => [...], 'rows' => [[...]]].
     * @param  string|null  $footer  Optional footer note.
     * @return bool Whether the email was sent.
     */
    public function emailParty(?string $email, string $subject, array $lines = [], ?array $table = null, ?string $footer = null): bool
    {
        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->send(new BusinessMail($subject, $lines, $table, $footer));

            return true;
        } catch (Throwable $e) {
            Log::warning('Notification email failed', [
                'to' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a transactional email to a set of users (deduplicated by email).
     *
     * @param  iterable  $users  Users with an "email" attribute.
     * @param  string  $subject  Email subject.
     * @param  array  $lines  Body paragraphs.
     * @param  array|null  $table  Optional table ['headers' => [...], 'rows' => [[...]]].
     * @return int Number of recipients the email was sent to.
     */
    public function emailUsers(iterable $users, string $subject, array $lines = [], ?array $table = null): int
    {
        $recipients = collect($users)
            ->map(function ($user) {
                return $user->email ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return 0;
        }

        try {
            Mail::to($recipients)->send(new BusinessMail($subject, $lines, $table));

            return $recipients->count();
        } catch (Throwable $e) {
            Log::warning('Notification email failed', [
                'recipients' => $recipients->all(),
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Send an SMS message through the configured driver.
     *
     * Only the "log" driver is bundled; any other configured driver falls
     * back to logging with a warning so flows never break before a gateway
     * package is installed.
     *
     * @param  mixed  $numbers  A number or list of numbers.
     * @param  string  $message  Message body.
     * @return int Number of messages dispatched.
     */
    public function sms($numbers, string $message): int
    {
        $numbers = collect(Arr::wrap($numbers))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return 0;
        }

        $driver = config('services.sms.driver', 'log');

        if ($driver !== 'log') {
            Log::warning("SMS driver [{$driver}] has no implementation yet; falling back to the log driver.");
        }

        foreach ($numbers as $number) {
            Log::info('SMS notification dispatched (log driver)', [
                'to' => $number,
                'message' => $message,
            ]);
        }

        return $numbers->count();
    }

    /**
     * Notify a supplier (Party) about a document event via email and SMS.
     *
     * @param  mixed  $supplier  Supplier/party with optional email and phone.
     * @param  string  $subject  Notification subject.
     * @param  array  $lines  Email body paragraphs.
     * @param  array|null  $table  Optional items table.
     * @return array{email: bool, sms: int}
     */
    public function notifySupplier($supplier, string $subject, array $lines = [], ?array $table = null): array
    {
        $result = ['email' => false, 'sms' => 0];

        if (! $supplier) {
            Log::info('Supplier notification skipped: no supplier linked.', [
                'subject' => $subject,
            ]);

            return $result;
        }

        $result['email'] = $this->emailParty($supplier->email ?? null, $subject, $lines, $table);
        $result['sms'] = $this->sms($supplier->phone ?? null, $subject);

        Log::info('Supplier notified', [
            'supplier_id' => $supplier->id ?? null,
            'subject' => $subject,
            'email_sent' => $result['email'],
            'sms_sent' => $result['sms'],
        ]);

        return $result;
    }
}
