<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService — outbound WhatsApp via the Meta Cloud API.
 *
 * Template messages only (business-initiated), e.g.:
 *   - sale confirmation with invoice number
 *   - prescription ready for pickup
 *   - due-collection reminder
 *   - batch recall notice
 *
 * Config: config/integrations.php (WHATSAPP_* envs). No-op with a warning
 * when disabled so calling code never needs feature branches.
 */
class WhatsAppService
{
    public function enabled(): bool
    {
        return (bool) config('integrations.whatsapp.enabled')
            && config('integrations.whatsapp.token')
            && config('integrations.whatsapp.phone_number_id');
    }

    /**
     * Send an approved template message.
     *
     * @param  string  $to        E.164 without '+', e.g. 201012345678
     * @param  string  $template  approved template name, e.g. sale_confirmation
     * @param  string  $lang      template language code, e.g. ar / ar_EG / en
     * @param  array   $params    ordered body parameters
     */
    public function sendTemplate(string $to, string $template, array $params = [], string $lang = 'ar'): bool
    {
        if (! $this->enabled()) {
            Log::info('WhatsApp disabled — message skipped', ['to' => $to, 'template' => $template]);
            return false;
        }

        $version = config('integrations.whatsapp.version', 'v21.0');
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => $params ? [[
                    'type' => 'body',
                    'parameters' => array_map(fn ($text) => ['type' => 'text', 'text' => (string) $text], $params),
                ]] : [],
            ],
        ];

        try {
            $response = Http::withToken(config('integrations.whatsapp.token'))
                ->acceptJson()
                ->timeout(config('integrations.http_timeout', 15))
                ->post("https://graph.facebook.com/{$version}/" . config('integrations.whatsapp.phone_number_id') . '/messages', $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp send failed', ['status' => $response->status(), 'body' => $response->json()]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Convenience helpers mapping domain events to templates.
     */
    public function sendSaleConfirmation(string $to, string $invoiceNo, string $total): bool
    {
        return $this->sendTemplate($to, 'sale_confirmation', [$invoiceNo, $total]);
    }

    public function sendDueReminder(string $to, string $invoiceNo, string $due_amount): bool
    {
        return $this->sendTemplate($to, 'due_reminder', [$invoiceNo, $due_amount]);
    }

    public function sendRecallNotice(string $to, string $productName, string $batchNo): bool
    {
        return $this->sendTemplate($to, 'batch_recall', [$productName, $batchNo]);
    }
}
