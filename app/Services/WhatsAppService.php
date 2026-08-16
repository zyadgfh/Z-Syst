<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected ?string $apiKey;
    protected ?string $phoneNumberId;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl = env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0');
        $this->apiKey = env('WHATSAPP_API_KEY');
        $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->enabled = env('WHATSAPP_ENABLED', false);
    }

    /**
     * Send a message to a phone number
     */
    public function sendMessage(string $to, string $message, ?array $templateParams = null): array
    {
        if (!$this->enabled) {
            Log::warning('WhatsApp service is disabled');
            return [
                'success' => false,
                'message' => 'خدمة واتساب معطلة',
            ];
        }

        try {
            // Clean phone number (remove +, spaces, etc.)
            $cleanPhone = $this->cleanPhoneNumber($to);

            if ($templateParams) {
                // Send template message
                return $this->sendTemplateMessage($cleanPhone, $message, $templateParams);
            } else {
                // Send text message
                return $this->sendTextMessage($cleanPhone, $message);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'فشل إرسال الرسالة: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message
     */
    protected function sendTextMessage(string $to, string $message): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'message_id' => $response->json('messages.0.id'),
            ];
        }

        return [
            'success' => false,
            'message' => 'فشل إرسال الرسالة',
            'error' => $response->json(),
        ];
    }

    /**
     * Send a template message
     */
    protected function sendTemplateMessage(string $to, string $templateName, array $params): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'ar',
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => array_map(function ($param) {
                                return [
                                    'type' => 'text',
                                    'text' => $param,
                                ];
                            }, $params),
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'message_id' => $response->json('messages.0.id'),
            ];
        }

        return [
            'success' => false,
            'message' => 'فشل إرسال الرسالة',
            'error' => $response->json(),
        ];
    }

    /**
     * Send order message to supplier
     */
    public function sendOrderToSupplier(
        string $supplierPhone,
        string $businessName,
        array $orderItems,
        ?string $notes = null
    ): array {
        // Build order message
        $message = $this->buildOrderMessage($businessName, $orderItems, $notes);

        return $this->sendMessage($supplierPhone, $message);
    }

    /**
     * Build order message
     */
    protected function buildOrderMessage(string $businessName, array $orderItems, ?string $notes = null): string
    {
        $lines = [
            "📋 *طلب جديد من {$businessName}*",
            "",
            "📦 *تفاصيل الطلب:*",
            "",
        ];

        foreach ($orderItems as $item) {
            $lines[] = "• {$item['product_name']}";
            $lines[] = "  الكمية: {$item['quantity']}";
            $lines[] = "  الكود: {$item['product_code']}";
            $lines[] = "";
        }

        $lines[] = "📅 التاريخ: " . now()->format('Y-m-d H:i');
        $lines[] = "";

        if ($notes) {
            $lines[] = "📝 *ملاحظات:*";
            $lines[] = $notes;
            $lines[] = "";
        }

        $lines[] = "📍 يرجى تأكيد الطلب في أقرب وقت";
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "تم الإرسال من نظام إدارة الصيدلية";

        return implode("\n", $lines);
    }

    /**
     * Clean phone number
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if missing (assuming Saudi Arabia)
        if (strlen($clean) === 10 && strpos($clean, '0') === 0) {
            $clean = '966' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Check if service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->phoneNumberId);
    }
}
