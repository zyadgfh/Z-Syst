<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\User;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendNotification;
use Illuminate\Support\Facades\Notification;

class SendExpiryAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expiry-alert:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for products that are expiring or expired to all business users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->startOfDay();
        $thresholds = [
            '365' => $today->copy()->addDays(365),
            '90'  => $today->copy()->addDays(90),
            '60'  => $today->copy()->addDays(60),
            '30'  => $today->copy()->addDays(30),
            '7'   => $today->copy()->addDays(7),
        ];

        // Get all expiring/expired stocks with productStock > 0
        $stocks = Stock::where('productStock', '>', 0)
                    ->whereNotNull('expire_date')
                    ->where(function ($query) use ($today) {
                        $query->where('expire_date', '<=', $today->copy()->addDays(365))
                              ->orWhere('expire_date', '<', $today);
                    })
                    ->with(['product:id,productName,business_id'])
                    ->get()
                    ->groupBy('product.business_id');

        if ($stocks->isEmpty()) {
            $this->info('No expiring products found.');
            return 0;
        }

        $notificationsSent = 0;

        foreach ($stocks as $businessId => $businessStocks) {
            // Get all users for this business including superadmin/owner
            $users = User::where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)
                      ->orWhere('role', 'superadmin');
            })->get();

            if ($users->isEmpty()) {
                continue;
            }

            // Group stocks by urgency
            $expired = [];
            $expiringToday = [];
            $expiring7 = [];
            $expiring30 = [];
            $expiring60 = [];
            $expiring90 = [];
            $expiring365 = [];

            foreach ($businessStocks as $stock) {
                $expireDate = $stock->expire_date ? \Carbon\Carbon::parse($stock->expire_date)->startOfDay() : null;
                if (!$expireDate) continue;

                $daysRemaining = $today->diffInDays($expireDate, false);

                if ($daysRemaining < 0) {
                    $expired[] = $stock;
                } elseif ($daysRemaining == 0) {
                    $expiringToday[] = $stock;
                } elseif ($daysRemaining <= 7) {
                    $expiring7[] = $stock;
                } elseif ($daysRemaining <= 30) {
                    $expiring30[] = $stock;
                } elseif ($daysRemaining <= 60) {
                    $expiring60[] = $stock;
                } elseif ($daysRemaining <= 90) {
                    $expiring90[] = $stock;
                } else {
                    $expiring365[] = $stock;
                }
            }

            $productName = $businessStocks->first()->product->productName ?? 'Unknown';

            // Build comprehensive notification message
            $message = "🔔 تنبيه انتهاء الصلاحية - Expiry Alert\n";

            if (!empty($expired)) {
                $names = $expired->pluck('product.productName')->unique()->take(5)->join(', ');
                $message .= "🚫 منتهية الصلاحية (Expired): {$expired->count()} منتج\n";
                if ($names) $message .= "   مثل: {$names}\n";
            }
            if (!empty($expiringToday)) {
                $message .= "⚠️ تنتهي اليوم (Expiring Today): {$expiringToday->count()} منتج\n";
            }
            if (!empty($expiring7)) {
                $message .= "⏰ تنتهي خلال 7 أيام (Within 7 days): {$expiring7->count()} منتج\n";
            }
            if (!empty($expiring30)) {
                $message .= "📅 تنتهي خلال 30 يوماً (Within 30 days): {$expiring30->count()} منتج\n";
            }
            if (!empty($expiring60)) {
                $message .= "📅 تنتهي خلال 60 يوماً (Within 60 days): {$expiring60->count()} منتج\n";
            }
            if (!empty($expiring90)) {
                $message .= "📅 تنتهي خلال 90 يوماً (Within 90 days): {$expiring90->count()} منتج\n";
            }
            if (!empty($expiring365)) {
                $message .= "📅 تنتهي خلال 365 يوماً (Within 365 days): {$expiring365->count()} منتج\n";
            }

            // Send notification to all users of this business
            foreach ($users as $user) {
                try {
                    Notification::send($user, new SendNotification([
                        'id' => uniqid(),
                        'user' => $user->name ?? 'User',
                        'message' => $message,
                        'url' => '/expiry-alerts',
                    ]));
                    $notificationsSent++;
                } catch (\Exception $e) {
                    Log::error("Failed to send expiry notification to user {$user->id}: {$e->getMessage()}");
                }
            }

            // Send SMS if configured
            $this->sendExpirySms($businessStocks, $businessId, $expired, $expiringToday, $expiring7, $expiring30);
        }

        $this->info("Expiry alert notifications sent successfully. Total: {$notificationsSent}");
        return 0;
    }

    /**
     * Send SMS notification for expiring products if SMS gateway is configured.
     */
    private function sendExpirySms($stocks, $businessId, $expired, $expiringToday, $expiring7, $expiring30)
    {
        try {
            $smsSettings = \App\Models\Option::where('key', 'sms-settings')->first();

            if (!$smsSettings || empty($smsSettings->value['api_url'])) {
                return; // SMS not configured
            }

            $settings = $smsSettings->value;
            $business = \App\Models\Business::find($businessId);

            if (!$business || !$business->phoneNumber) {
                return;
            }

            $message = "🔔 Expiry Alert - {$business->companyName}\n";

            if (!empty($expired)) {
                $message .= "🚫 Expired: {$expired->count()} products\n";
            }
            if (!empty($expiringToday)) {
                $message .= "⚠️ Expiring Today: {$expiringToday->count()}\n";
            }
            if (!empty($expiring7)) {
                $message .= "⏰ Within 7 days: {$expiring7->count()}\n";
            }
            if (!empty($expiring30)) {
                $message .= "📅 Within 30 days: {$expiring30->count()}\n";
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer " . ($settings['api_token'] ?? ''),
                'Content-Type' => "application/json",
                'Accept' => "application/json",
            ])->post($settings['api_url'], [
                'recipient' => $business->phoneNumber,
                'sender_id' => $settings['sender_id'] ?? '',
                'type' => $settings['type'] ?? '',
                'message' => $message,
            ]);

            Log::info("SMS expiry alert sent to {$business->phoneNumber}: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Failed to send SMS expiry alert: {$e->getMessage()}");
        }
    }
}

