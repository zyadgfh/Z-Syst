<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageSubscriptions extends Command
{
    protected $signature = 'subscriptions:manage';
    protected $description = 'Manage subscriptions - handle trials, expirations, and renewals';

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    public function handle(): int
    {
        $this->info('Starting subscription management...');

        // Handle trial to active conversion
        $this->handleTrialConversions();

        // Handle expiring subscriptions
        $this->handleExpiringSubscriptions();

        // Handle expired subscriptions
        $this->handleExpiredSubscriptions();

        // Generate invoices for upcoming renewals
        $this->generateUpcomingInvoices();

        $this->info('Subscription management completed successfully.');

        return Command::SUCCESS;
    }

    protected function handleTrialConversions(): void
    {
        $this->info('Handling trial conversions...');

        $endingTrials = Subscription::trialing()
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($endingTrials as $subscription) {
            try {
                $subscription->update([
                    'status' => 'active',
                    'trial_ends_at' => null,
                ]);

                $this->info("Trial converted to active for subscription {$subscription->id}");

                // Generate first invoice
                $this->subscriptionService->generateInvoice($subscription);

                Log::info("Trial converted to active", [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business_id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to convert trial to active", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Converted {$endingTrials->count()} trials to active.");
    }

    protected function handleExpiringSubscriptions(): void
    {
        $this->info('Handling expiring subscriptions...');

        $expiringSoon = $this->subscriptionService->getExpiringSubscriptions(7);

        foreach ($expiringSoon as $subscription) {
            try {
                // Send notification to business
                // This would integrate with your notification system
                $daysRemaining = $subscription->daysUntilRenewal();

                $this->info("Subscription {$subscription->id} expires in {$daysRemaining} days");

                Log::info("Subscription expiring soon", [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business_id,
                    'days_remaining' => $daysRemaining,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to handle expiring subscription", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$expiringSoon->count()} expiring subscriptions.");
    }

    protected function handleExpiredSubscriptions(): void
    {
        $this->info('Handling expired subscriptions...');

        $expired = Subscription::active()
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            try {
                $subscription->update([
                    'status' => 'expired',
                ]);

                $this->info("Subscription {$subscription->id} marked as expired");

                Log::info("Subscription expired", [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business_id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to handle expired subscription", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$expired->count()} expired subscriptions.");
    }

    protected function generateUpcomingInvoices(): void
    {
        $this->info('Generating upcoming invoices...');

        $expiringSoon = $this->subscriptionService->getExpiringSubscriptions(3);

        foreach ($expiringSoon as $subscription) {
            try {
                // Only generate if not already generated for this period
                $lastInvoice = $subscription->invoices()
                    ->where('invoice_date', '>=', now()->subMonth())
                    ->latest()
                    ->first();

                if (!$lastInvoice) {
                    $this->subscriptionService->generateInvoice($subscription);
                    $this->info("Generated invoice for subscription {$subscription->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate invoice", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Invoice generation completed.");
    }
}
