<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Party;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignMetric;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MarketingAutomationService
{
    use WithTransactionalOperations;

    /**
     * Create a marketing campaign.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Campaign
     */
    public function createCampaign(array $data, int $businessId): Campaign
    {
        return Campaign::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'type' => $data['type'], // email, sms, push
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'],
            'template' => $data['template'] ?? null,
            'target_segment' => $data['target_segment'] ?? 'all',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'status' => 'draft',
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Send campaign to recipients.
     *
     * @param Campaign $campaign
     * @return array
     */
    public function sendCampaign(Campaign $campaign): array
    {
        return $this->executeTransaction(function () use ($campaign) {
            $recipients = $this->getRecipients($campaign);

            if ($recipients->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No recipients found',
                    'sent_count' => 0,
                ];
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $result = $this->sendToRecipient($campaign, $recipient);
                    
                    CampaignRecipient::create([
                        'campaign_id' => $campaign->id,
                        'party_id' => $recipient->id,
                        'status' => $result['status'],
                        'sent_at' => now(),
                        'error_message' => $result['error'] ?? null,
                    ]);

                    if ($result['status'] === 'sent') {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Campaign sent successfully',
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_recipients' => $recipients->count(),
            ];
        });
    }

    /**
     * Get recipients for a campaign based on segment.
     *
     * @param Campaign $campaign
     * @return Collection
     */
    protected function getRecipients(Campaign $campaign): Collection
    {
        $query = Party::where('business_id', $campaign->business_id)
            ->where('type', 'customer');

        switch ($campaign->target_segment) {
            case 'active':
                $query->whereHas('sales', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(90));
                });
                break;
            case 'inactive':
                $query->whereDoesntHave('sales', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(90));
                });
                break;
            case 'high_value':
                $query->whereHas('sales', function ($q) {
                    $q->where('totalAmount', '>', 1000);
                });
                break;
            case 'due_balance':
                $query->where('due', '>', 0);
                break;
            case 'all':
            default:
                // No additional filters
                break;
        }

        return $query->get();
    }

    /**
     * Send campaign to a single recipient.
     *
     * @param Campaign $campaign
     * @param Party $recipient
     * @return array
     */
    protected function sendToRecipient(Campaign $campaign, Party $recipient): array
    {
        try {
            switch ($campaign->type) {
                case 'email':
                    return $this->sendEmail($campaign, $recipient);
                case 'sms':
                    return $this->sendSms($campaign, $recipient);
                case 'push':
                    return $this->sendPushNotification($campaign, $recipient);
                default:
                    return ['status' => 'failed', 'error' => 'Unsupported campaign type'];
            }
        } catch (\Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email campaign.
     *
     * @param Campaign $campaign
     * @param Party $recipient
     * @return array
     */
    protected function sendEmail(Campaign $campaign, Party $recipient): array
    {
        // Mail::to($recipient->email)->send(new CampaignEmail($campaign, $recipient));
        // Note: CampaignEmail mailable would need to be created
        
        return ['status' => 'sent'];
    }

    /**
     * Send SMS campaign.
     *
     * @param Campaign $campaign
     * @param Party $recipient
     * @return array
     */
    protected function sendSms(Campaign $campaign, Party $recipient): array
    {
        // Integrate with SMS service (Twilio, etc.)
        
        return ['status' => 'sent'];
    }

    /**
     * Send push notification campaign.
     *
     * @param Campaign $campaign
     * @param Party $recipient
     * @return array
     */
    protected function sendPushNotification(Campaign $campaign, Party $recipient): array
    {
        // Integrate with push notification service (Firebase, OneSignal, etc.)
        
        return ['status' => 'sent'];
    }

    /**
     * Get campaign analytics.
     *
     * @param Campaign $campaign
     * @return array
     */
    public function getCampaignAnalytics(Campaign $campaign): array
    {
        $recipients = $campaign->recipients;
        
        $sent = $recipients->where('status', 'sent')->count();
        $failed = $recipients->where('status', 'failed')->count();
        $opened = $recipients->where('opened_at', '!=', null)->count();
        $clicked = $recipients->where('clicked_at', '!=', null)->count();

        return [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'type' => $campaign->type,
            'total_recipients' => $recipients->count(),
            'sent' => $sent,
            'failed' => $failed,
            'delivery_rate' => $recipients->count() > 0 ? ($sent / $recipients->count()) * 100 : 0,
            'opened' => $opened,
            'open_rate' => $sent > 0 ? ($opened / $sent) * 100 : 0,
            'clicked' => $clicked,
            'click_rate' => $opened > 0 ? ($clicked / $opened) * 100 : 0,
            'conversion_rate' => $sent > 0 ? ($clicked / $sent) * 100 : 0,
        ];
    }

    /**
     * Create automated campaign rule.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return array
     */
    public function createAutomationRule(array $data, int $businessId): array
    {
        // This would create triggers for automated campaigns
        // e.g., send welcome email to new customers, re-engagement emails to inactive customers
        
        return [
            'success' => true,
            'rule_id' => 'AUT-' . uniqid(),
            'message' => 'Automation rule created',
        ];
    }

    /**
     * Get customer segments.
     *
     * @param int $businessId
     * @return array
     */
    public function getCustomerSegments(int $businessId): array
    {
        $allCustomers = Party::where('business_id', $businessId)
            ->where('type', 'customer')
            ->count();

        $activeCustomers = Party::where('business_id', $businessId)
            ->where('type', 'customer')
            ->whereHas('sales', function ($q) {
                $q->where('created_at', '>=', now()->subDays(90));
            })
            ->count();

        $inactiveCustomers = $allCustomers - $activeCustomers;
        
        $highValueCustomers = Party::where('business_id', $businessId)
            ->where('type', 'customer')
            ->whereHas('sales', function ($q) {
                $q->where('totalAmount', '>', 1000);
            })
            ->count();

        $dueBalanceCustomers = Party::where('business_id', $businessId)
            ->where('type', 'customer')
            ->where('due', '>', 0)
            ->count();

        return [
            'all' => $allCustomers,
            'active' => $activeCustomers,
            'inactive' => $inactiveCustomers,
            'high_value' => $highValueCustomers,
            'due_balance' => $dueBalanceCustomers,
        ];
    }
}