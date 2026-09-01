<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS via configured gateway.
     *
     * Supports:
     * - Twilio (default)
     * - Infobip
     * - Custom API (via config)
     *
     * Config in config/sms.php:
     *   driver, account_sid, auth_token, from_number, api_url, api_key
     */
    public function send(string $to, string $message): bool
    {
        $driver = config('sms.driver', 'log');

        return match ($driver) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'infobip' => $this->sendViaInfobip($to, $message),
            'custom' => $this->sendViaCustomApi($to, $message),
            default => $this->sendViaLog($to, $message),
        };
    }

    /**
     * Send order confirmation SMS
     */
    public function sendOrderConfirmation(string $phone, string $orderNumber, float $total): bool
    {
        $message = __("Your order :number has been confirmed! Total: :total. Thank you for shopping with us.", [
            'number' => $orderNumber,
            'total' => number_format($total, 2),
        ]);

        return $this->send($phone, $message);
    }

    /**
     * Send order status update SMS
     */
    public function sendOrderStatusUpdate(string $phone, string $orderNumber, string $status): bool
    {
        $statusText = match ($status) {
            'confirmed' => __('has been confirmed and is being prepared'),
            'processing' => __('is now being processed'),
            'shipped' => __('has been shipped and is on its way to you'),
            'delivered' => __('has been delivered successfully'),
            'cancelled' => __('has been cancelled'),
            default => __('status updated to :status', ['status' => $status]),
        };

        $message = __("Your order :number :statusText.", [
            'number' => $orderNumber,
            'statusText' => $statusText,
        ]);

        return $this->send($phone, $message);
    }

    // ── Gateway Implementations ──

    protected function sendViaTwilio(string $to, string $message): bool
    {
        try {
            $accountSid = config('sms.account_sid');
            $authToken = config('sms.auth_token');
            $fromNumber = config('sms.from_number');

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post($url, [
                    'To' => $to,
                    'From' => $fromNumber,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Twilio SMS failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;

        } catch (\Throwable $e) {
            Log::error('Twilio SMS error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendViaInfobip(string $to, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . config('sms.api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(config('sms.api_url', 'https://api.infobip.com/sms/2/text/advanced'), [
                'messages' => [[
                    'destinations' => [['to' => $to]],
                    'from' => config('sms.from_number'),
                    'text' => $message,
                ]],
            ]);

            return $response->successful();

        } catch (\Throwable $e) {
            Log::error('Infobip SMS error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendViaCustomApi(string $to, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('sms.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('sms.api_url'), [
                'to' => $to,
                'message' => $message,
                'from' => config('sms.from_number'),
            ]);

            return $response->successful();

        } catch (\Throwable $e) {
            Log::error('Custom SMS error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendViaLog(string $to, string $message): bool
    {
        Log::info('SMS sent (log driver)', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }
}
