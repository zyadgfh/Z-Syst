<?php

namespace App\Services;

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
     */
    public function sendInvoice(array $customer, string $imageBase64, int $invoiceId, ?string $caption = null): bool
    {
        try {
            // Get customer phone number
            $customerPhone = $this->formatPhoneNumber($customer['phone']);
            
            // Upload image to get public URL
            $imageUrl = $this->uploadImageAndGetUrl($imageBase64);
            
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
                    'caption' => $caption ?? "فاتورة البيع رقم #{$invoiceId}\nاليكم فاتورتكم، شكراً لتعاملكم معنا"
                ],
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp invoice sent successfully for invoice #{$invoiceId} to {$customerPhone}");
                return true;
            }

            Log::error("WhatsApp API Error: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send text message to customer via WhatsApp
     */
    public function sendTextMessage(string $phone, string $message): bool
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
                    'body' => $message
                ],
            ]);

            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Text Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send PDF document via WhatsApp
     */
    public function sendPdfDocument(string $phone, string $pdfPath, int $invoiceId): bool
    {
        try {
            $customerPhone = $this->formatPhoneNumber($phone);
            
            // Upload PDF and get URL
            $pdfUrl = Storage::url($pdfPath);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->apiVersion . '/' . $this->phoneNumber . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $customerPhone,
                'type' => 'document',
                'document' => [
                    'link' => $pdfUrl,
                    'caption' => "فاتورة البيع رقم #{$invoiceId}",
                ],
            ]);

            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('WhatsApp PDF Send Error: ' . $e->getMessage());
            return false;
        }
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
        
        // Ensure it starts with +
        return $phone;
    }

    /**
     * Upload image to storage and return public URL
     */
    protected function uploadImageAndGetUrl(string $base64Image): string
    {
        // Decode base64 image
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        
        // Generate unique filename
        $fileName = 'invoices/' . uniqid() . '_' . time() . '.jpg';
        
        // Store in public disk
        Storage::disk('public')->put($fileName, $imageData);
        
        // Return full URL
        return asset('storage/' . $fileName);
    }
}