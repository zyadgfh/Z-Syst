<?php

namespace App\Services\Integration;

use App\Models\Business;
use App\Models\MessageLog;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class MessagingService
{
    use WithTransactionalOperations;

    /**
     * Send SMS message.
     *
     * @param string $phoneNumber
     * @param string $message
     * @param int $businessId
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message, int $businessId): array
    {
        // Integrate with SMS service (Twilio, MessageBird, local SMS gateway)
        // For now, simulate the response
        
        $log = MessageLog::create([
            'business_id' => $businessId,
            'type' => 'sms',
            'recipient' => $phoneNumber,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'message_id' => $log->id,
            'status' => 'sent',
        ];
    }

    /**
     * Send WhatsApp message.
     *
     * @param string $phoneNumber
     * @param string $message
     * @param int $businessId
     * @return array
     */
    public function sendWhatsApp(string $phoneNumber, string $message, int $businessId): array
    {
        // Integrate with WhatsApp Business API
        // For now, simulate the response
        
        $log = MessageLog::create([
            'business_id' => $businessId,
            'type' => 'whatsapp',
            'recipient' => $phoneNumber,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'message_id' => $log->id,
            'status' => 'sent',
        ];
    }

    /**
     * Send bulk SMS messages.
     *
     * @param array<string> $phoneNumbers
     * @param string $message
     * @param int $businessId
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message, int $businessId): array
    {
        $results = [];
        $sentCount = 0;
        $failedCount = 0;

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                $result = $this->sendSms($phoneNumber, $message, $businessId);
                if ($result['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
                $results[] = $result;
            } catch (\Exception $e) {
                $failedCount++;
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => count($phoneNumbers),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Get messaging analytics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getMessagingAnalytics(int $businessId, array $filters = []): array
    {
        $query = MessageLog::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $logs = $query->get();

        return [
            'total_messages' => $logs->count(),
            'by_type' => $logs->groupBy('type')->map->count(),
            'by_status' => $logs->groupBy('status')->map->count(),
            'total_cost' => $this->calculateTotalCost($logs),
        ];
    }

    /**
     * Calculate total cost of messages.
     *
     * @param Collection $logs
     * @return float
     */
    protected function calculateTotalCost(Collection $logs): float
    {
        // Calculate based on SMS/WhatsApp pricing
        // SMS: ~$0.05 per message
        // WhatsApp: ~$0.02 per message
        
        $smsCount = $logs->where('type', 'sms')->count();
        $whatsappCount = $logs->where('type', 'whatsapp')->count();

        return ($smsCount * 0.05) + ($whatsappCount * 0.02);
    }
}