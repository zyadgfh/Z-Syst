<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;
    protected $phoneNumber;
    protected $apiVersion;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp_api.url', 'https://graph.facebook.com/v17.0');
        $this->apiKey = config('services.whatsapp_api.key');
        $this->phoneNumber = config('services.whatsapp_api.phone_number');
        $this->apiVersion = config('services.whatsapp_api.api_version', 'v17.0');
    }

    /**
     * Send invoice image to customer via WhatsApp
     *
     * @param array $customer Customer data with phone and name
     * @param string $imagePath Path to invoice image file
     * @param int $invoiceId Invoice ID
     * @param string|null $caption Optional caption/message
     * @return array Response data
     */
    public function sendInvoice(array $customer, string $imagePath, int $invoiceId, ?string $caption = null): array
    {
        try {
            $customerPhone = $this->formatPhoneNumber($customer['phone'] ?? $customer['phone_number'] ?? '');

            // Get full URL for the image
            $imageUrl = $this->getFileUrl($imagePath);

            // Send via WhatsApp Business API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->apiVersion . '/' . $this->phoneNumber . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $customerPhone,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                    'caption' => $caption ?? $this->getDefaultMessage($invoiceId, $customer),
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("WhatsApp invoice sent successfully for invoice #{$invoiceId} to {$customerPhone}");

                return [
                    'success' => true,
                    'message_id' => $data['messages']['id'] ?? null,
                    'status' => $data['messages']['status'] ?? 'sent',
                ];
            }

            Log::error("WhatsApp API Error: " . $response->body());
            return [
                'success' => false,
                'error' => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send text message to customer via WhatsApp
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        try {
            $customerPhone = $this->formatPhoneNumber($phone);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->apiVersion . '/' . $this->phoneNumber . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $customerPhone,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages']['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Text Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send PDF document via WhatsApp
     *
     * @param string $phone Recipient phone number
     * @param string $pdfPath Path to PDF file
     * @param int $invoiceId Invoice ID
     * @return array Response data
     */
    public function sendPdfDocument(string $phone, string $pdfPath, int $invoiceId): array
    {
        try {
            $customerPhone = $this->formatPhoneNumber($phone);

            // Get full URL for the PDF
            $pdfUrl = $this->getFileUrl($pdfPath);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->apiVersion . '/' . $this->phoneNumber . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $customerPhone,
                'type' => 'document',
                'document' => [
                    'link' => $pdfUrl,
                    'caption' => "فاتورة رقم #{$invoiceId}",
                    'filename' => "invoice-{$invoiceId}.pdf",
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages']['id'] ?? null,
                ];
            }

            Log::error("WhatsApp PDF Error: " . $response->body());
            return [
                'success' => false,
                'error' => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp PDF Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send invoice to customer with both image and PDF
     */
    public function sendInvoiceWithBoth(Sale $sale, string $imagePath, string $pdfPath, ?string $customMessage = null): array
    {
        $customer = $sale->party;
        $customerPhone = $customer->phone;
        $caption = $customMessage ?? $this->getDefaultMessage($sale->id, ['name' => $customer->name]);

        // Send image first
        $imageResult = $this->sendInvoice(
            customer: ['phone' => $customerPhone, 'name' => $customer->name],
            imagePath: $imagePath,
            invoiceId: $sale->id,
            caption: $caption
        );

        // Send PDF
        $pdfResult = $this->sendPdfDocument(
            phone: $customerPhone,
            pdfPath: $pdfPath,
            invoiceId: $sale->id
        );

        return [
            'image' => $imageResult,
            'pdf' => $pdfResult,
        ];
    }

    /**
     * Format phone number to international format (E.164)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Add Egypt country code if not present
        if (!str_starts_with($phone, '20')) {
            $phone = '20' . $phone;
        }

        return $phone;
    }

    /**
     * Get full URL for file
     */
    protected function getFileUrl(string $path): string
    {
        // If already a full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // If storage path, get the URL
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        // If file exists in storage, return URL
        if (file_exists(storage_path('app/public/' . $path))) {
            return asset('storage/' . $path);
        }

        // Return the path as is for external URLs
        return $path;
    }

    /**
     * Get default message template
     */
    protected function getDefaultMessage(int $invoiceId, array $customer = []): string
    {
        $name = $customer['name'] ?? $customer['customer_name'] ?? 'عميلنا العزيز';
        return "مرحباً {$name}،\nفاتورتك رقم #{$invoiceId} مرفقة.\nشكراً لتعاملك معنا!";
    }
}
