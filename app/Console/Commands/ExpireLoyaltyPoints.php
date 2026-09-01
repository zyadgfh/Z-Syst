<?php

namespace App\Console\Commands;

use App\Mail\LoyaltyPointsExpiringMail;
use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points';
    protected $description = 'Expire loyalty points after 12 months and send email warnings 30 days before expiry';

    public function handle(): int
    {
        $notificationCount = $this->sendExpiryWarnings();
        $expiredCount      = $this->expirePoints();

        $this->info("Loyalty points: {$notificationCount} warnings sent, {$expiredCount} expired.");

        return self::SUCCESS;
    }

    /**
     * Send email warnings to users whose points expire within 30 days.
     */
    protected function sendExpiryWarnings(): int
    {
        $sent = 0;

        // Group expiring points by user
        $expiringByUser = LoyaltyPoint::where('type', 'earned')
            ->where('points', '>', 0)
            ->where('expires_at', '!=', null)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('expiration_notification_sent', false)
            ->select('user_id', DB::raw('SUM(points) as total_points'), DB::raw('MIN(expires_at) as earliest_expiry'))
            ->groupBy('user_id')
            ->get();

        foreach ($expiringByUser as $group) {
            $user = User::find($group->user_id);
            if (!$user || !$user->email) continue;

            $daysUntilExpiry = (int) now()->diffInDays($group->earliest_expiry, false);

            try {
                Mail::to($user->email)->send(new LoyaltyPointsExpiringMail(
                    $user,
                    $group->total_points,
                    max($daysUntilExpiry, 1),
                ));

                // Mark as notified
                LoyaltyPoint::where('user_id', $group->user_id)
                    ->where('type', 'earned')
                    ->where('points', '>', 0)
                    ->where('expires_at', '!=', null)
                    ->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays(30))
                    ->where('expiration_notification_sent', false)
                    ->update(['expiration_notification_sent' => true]);

                $sent++;
            } catch (\Exception $e) {
                $this->error("Failed to send email to user {$group->user_id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Expire points past their expiration date and adjust user balances.
     */
    protected function expirePoints(): int
    {
        $expired = 0;

        $expiredPoints = LoyaltyPoint::where('type', 'earned')
            ->where('points', '>', 0)
            ->where('expires_at', '!=', null)
            ->where('expires_at', '<=', now())
            ->get();

        // Group by user to batch balance updates
        $byUser = $expiredPoints->groupBy('user_id');

        foreach ($byUser as $userId => $points) {
            $totalToExpire = $points->sum('points');

            DB::transaction(function () use ($userId, $totalToExpire, $points) {
                // Decrement user balance (not below zero)
                $user = User::find($userId);
                if ($user) {
                    $newBalance = max(0, $user->loyalty_points_balance - $totalToExpire);
                    $user->update(['loyalty_points_balance' => $newBalance]);
                }

                // Create expired transaction records for each batch
                foreach ($points as $point) {
                    $point->update(['type' => 'expired']);

                    LoyaltyPoint::create([
                        'user_id'     => $userId,
                        'business_id' => $point->business_id,
                        'points'      => -$point->points,
                        'type'        => 'expired',
                        'description' => "انتهت صلاحية النقاط — {$point->points} نقطة",
                        'reference'   => "expired:{$point->id}",
                        'expires_at'  => null,
                    ]);
                }
            });

            $expired += $points->count();
        }

        return $expired;
    }
}
