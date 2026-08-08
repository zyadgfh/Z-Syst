<?php

namespace App\Services;

use App\Models\DoctorActivity;
use App\Models\DoctorAttentionAlert;
use App\Models\DoctorAttentionScore;
use App\Models\DoctorAttentionSettings;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoctorAttentionService
{
    /**
     * Calculate attention scores for all doctors in a business.
     */
    public function calculateScoresForBusiness(int $businessId, int $branchId = null): void
    {
        $settings = DoctorAttentionSettings::getDefaults($businessId, $branchId);
        $doctors = Party::where('business_id', $businessId)
            ->where('type', 'doctor')
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->get();

        foreach ($doctors as $doctor) {
            $this->calculateScoreForDoctor($doctor, $settings);
        }
    }

    /**
     * Calculate attention score for a specific doctor.
     */
    public function calculateScoreForDoctor(Party $doctor, DoctorAttentionSettings $settings): DoctorAttentionScore
    {
        $today = now();
        $periodDays = $settings->referral_drop_period_days;
        
        // Get current period activity
        $currentPeriodStart = $today->copy()->subDays($periodDays);
        $currentPeriodActivity = DoctorActivity::forDoctor($doctor->id)
            ->inPeriod($currentPeriodStart, $today)
            ->sum('referral_count');
        
        // Get previous period activity
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($periodDays);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
        $previousPeriodActivity = DoctorActivity::forDoctor($doctor->id)
            ->inPeriod($previousPeriodStart, $previousPeriodEnd)
            ->sum('referral_count');
        
        // Calculate baseline (average of current + previous)
        $baselineReferrals = ($currentPeriodActivity + $previousPeriodActivity) / 2;
        
        // Calculate decline percentage
        $declinePercentage = 0;
        if ($baselineReferrals > 0) {
            $declinePercentage = (($baselineReferrals - $currentPeriodActivity) / $baselineReferrals) * 100;
        }
        
        // Calculate days inactive
        $lastActivity = DoctorActivity::forDoctor($doctor->id)
            ->where('referral_count', '>', 0)
            ->latest('activity_date')
            ->first();
        
        $daysInactive = 0;
        $lastReferralDate = null;
        if ($lastActivity) {
            $lastReferralDate = $lastActivity->activity_date;
            $daysInactive = $today->diffInDays($lastReferralDate);
        }
        
        // Calculate attention score (0-100)
        $attentionScore = 100;
        
        // Deduct for decline
        $attentionScore -= min($declinePercentage * 2, 50); // Max 50 points deduction
        
        // Deduct for inactivity
        $attentionScore -= min($daysInactive * 2, 50); // Max 50 points deduction
        
        // Ensure score is between 0 and 100
        $attentionScore = max(0, min(100, $attentionScore));
        
        // Determine status
        $status = DoctorAttentionScore::STATUS_ACTIVE;
        $alertReason = null;
        
        if ($attentionScore <= $settings->attention_score_critical || 
            $daysInactive >= $settings->critical_inactivity_days) {
            $status = DoctorAttentionScore::STATUS_CRITICAL;
            $alertReason = $daysInactive >= $settings->critical_inactivity_days 
                ? 'Critical inactivity: ' . $daysInactive . ' days without referrals'
                : 'Critical attention score: ' . $attentionScore;
        } elseif ($attentionScore <= $settings->attention_score_warning || 
                   $declinePercentage >= $settings->referral_drop_threshold ||
                   $daysInactive >= $settings->inactivity_threshold_days) {
            $status = DoctorAttentionScore::STATUS_NEEDS_ATTENTION;
            $alertReason = $declinePercentage >= $settings->referral_drop_threshold
                ? 'Referral drop: ' . round($declinePercentage, 1) . '%'
                : 'Inactivity: ' . $daysInactive . ' days';
        }
        
        // Create or update score
        $score = DoctorAttentionScore::updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'business_id' => $doctor->business_id,
                'branch_id' => $doctor->branch_id,
                'calculated_date' => $today->toDateString(),
            ],
            [
                'attention_score' => $attentionScore,
                'decline_percentage' => $declinePercentage,
                'days_inactive' => $daysInactive,
                'last_referral_date' => $lastReferralDate,
                'baseline_referrals' => $baselineReferrals,
                'current_period_referrals' => $currentPeriodActivity,
                'previous_period_referrals' => $previousPeriodActivity,
                'status' => $status,
                'alert_reason' => $alertReason,
            ]
        );
        
        // Generate alert if needed
        if ($status !== DoctorAttentionScore::STATUS_ACTIVE) {
            $this->generateAlert($doctor, $score, $settings);
        }
        
        return $score;
    }

    /**
     * Generate alert for doctor needing attention.
     */
    public function generateAlert(Party $doctor, DoctorAttentionScore $score, DoctorAttentionSettings $settings): ?DoctorAttentionAlert
    {
        // Check if alert was already sent recently
        $recentAlert = DoctorAttentionAlert::forDoctor($doctor->id)
            ->where('created_at', '>=', now()->subHours($settings->alert_frequency_hours))
            ->where('is_sent', true)
            ->first();
        
        if ($recentAlert) {
            return null; // Don't spam alerts
        }
        
        // Determine alert type and severity
        $alertType = DoctorAttentionAlert::TYPE_INACTIVITY;
        $severity = DoctorAttentionAlert::SEVERITY_MEDIUM;
        
        if ($score->isCritical()) {
            $alertType = DoctorAttentionAlert::TYPE_CRITICAL;
            $severity = DoctorAttentionAlert::SEVERITY_CRITICAL;
        } elseif ($score->hasSignificantDecline()) {
            $alertType = DoctorAttentionAlert::TYPE_REFERRAL_DROP;
            $severity = DoctorAttentionAlert::SEVERITY_HIGH;
        }
        
        // Create alert message
        $message = $this->generateAlertMessage($doctor, $score);
        
        // Create alert
        $alert = DoctorAttentionAlert::create([
            'doctor_id' => $doctor->id,
            'business_id' => $doctor->business_id,
            'branch_id' => $doctor->branch_id,
            'medical_rep_id' => $doctor->medical_rep_id ?? null,
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $message,
            'details' => [
                'attention_score' => $score->attention_score,
                'decline_percentage' => $score->decline_percentage,
                'days_inactive' => $score->days_inactive,
                'last_referral_date' => $score->last_referral_date,
                'current_referrals' => $score->current_period_referrals,
                'baseline_referrals' => $score->baseline_referrals,
            ],
        ]);
        
        // Send notification if enabled
        if ($settings->enable_push_notifications) {
            $this->sendPushNotification($alert, $settings);
        }
        
        if ($settings->enable_email_notifications) {
            $this->sendEmailNotification($alert, $settings);
        }
        
        if ($settings->enable_sms_notifications) {
            $this->sendSmsNotification($alert, $settings);
        }
        
        return $alert;
    }

    /**
     * Generate alert message.
     */
    private function generateAlertMessage(Party $doctor, DoctorAttentionScore $score): string
    {
        if ($score->isCritical()) {
            return "⚠️ CRITICAL: Dr. {$doctor->name} needs immediate attention. {$score->alert_reason}";
        }
        
        return "🔔 Attention: Dr. {$doctor->name} needs attention. {$score->alert_reason}";
    }

    /**
     * Send push notification.
     */
    private function sendPushNotification(DoctorAttentionAlert $alert, DoctorAttentionSettings $settings): void
    {
        // Get notification recipients
        $recipients = $this->getNotificationRecipients($alert, $settings);
        
        // TODO: Implement push notification logic
        // This would integrate with your notification system
        // For now, we'll just mark as sent
        $alert->markAsSent();
        
        Log::info('Push notification sent for doctor attention alert', [
            'alert_id' => $alert->id,
            'doctor_id' => $alert->doctor_id,
            'recipients_count' => $recipients->count(),
        ]);
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(DoctorAttentionAlert $alert, DoctorAttentionSettings $settings): void
    {
        $recipients = $this->getNotificationRecipients($alert, $settings);
        
        // TODO: Implement email notification logic
        // This would send emails to recipients
        
        Log::info('Email notification sent for doctor attention alert', [
            'alert_id' => $alert->id,
            'doctor_id' => $alert->doctor_id,
        ]);
    }

    /**
     * Send SMS notification.
     */
    private function sendSmsNotification(DoctorAttentionAlert $alert, DoctorAttentionSettings $settings): void
    {
        $recipients = $this->getNotificationRecipients($alert, $settings);
        
        // TODO: Implement SMS notification logic
        // This would send SMS to recipients
        
        Log::info('SMS notification sent for doctor attention alert', [
            'alert_id' => $alert->id,
            'doctor_id' => $alert->doctor_id,
        ]);
    }

    /**
     * Get notification recipients.
     */
    private function getNotificationRecipients(DoctorAttentionAlert $alert, DoctorAttentionSettings $settings)
    {
        $query = User::where('business_id', $alert->business_id);
        
        // Filter by roles if specified
        if ($settings->notify_roles && is_array($settings->notify_roles)) {
            $query->whereHas('roles', function ($q) use ($settings) {
                $q->whereIn('name', $settings->notify_roles);
            });
        }
        
        // Filter by specific users if specified
        if ($settings->notify_users && is_array($settings->notify_users)) {
            $query->whereIn('id', $settings->notify_users);
        }
        
        return $query->get();
    }

    /**
     * Get doctors needing attention.
     */
    public function getDoctorsNeedingAttention(int $businessId, int $branchId = null)
    {
        return DoctorAttentionScore::forBusiness($businessId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->forBranch($branchId);
            })
            ->needingAttention()
            ->with('doctor')
            ->latest('calculated_date')
            ->get()
            ->unique('doctor_id');
    }

    /**
     * Get critical doctors.
     */
    public function getCriticalDoctors(int $businessId, int $branchId = null)
    {
        return DoctorAttentionScore::forBusiness($businessId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->forBranch($branchId);
            })
            ->critical()
            ->with('doctor')
            ->latest('calculated_date')
            ->get()
            ->unique('doctor_id');
    }

    /**
     * Get unread alerts for user.
     */
    public function getUnreadAlerts(int $userId, int $businessId)
    {
        return DoctorAttentionAlert::forBusiness($businessId)
            ->unread()
            ->whereHas('medicalRep', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->with('doctor')
            ->latest()
            ->get();
    }

    /**
     * Record doctor activity.
     */
    public function recordActivity(array $data): DoctorActivity
    {
        return DoctorActivity::create([
            'doctor_id' => $data['doctor_id'],
            'business_id' => $data['business_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'medical_rep_id' => $data['medical_rep_id'] ?? null,
            'activity_date' => $data['activity_date'] ?? now(),
            'referral_count' => $data['referral_count'] ?? 0,
            'referral_amount' => $data['referral_amount'] ?? 0,
            'prescription_count' => $data['prescription_count'] ?? 0,
            'details' => $data['details'] ?? null,
        ]);
    }

    /**
     * Mark alert as action taken.
     */
    public function markAlertAsActionTaken(int $alertId, string $actionDetails): void
    {
        $alert = DoctorAttentionAlert::findOrFail($alertId);
        $alert->markAsActionTaken($actionDetails);
    }

    /**
     * Get attention statistics.
     */
    public function getStatistics(int $businessId, int $branchId = null): array
    {
        $query = DoctorAttentionScore::forBusiness($businessId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->forBranch($branchId);
            })
            ->where('calculated_date', now()->toDateString());
        
        $total = $query->count();
        $active = $query->clone()->byStatus(DoctorAttentionScore::STATUS_ACTIVE)->count();
        $needingAttention = $query->clone()->needingAttention()->count();
        $critical = $query->clone()->critical()->count();
        
        $alertsToday = DoctorAttentionAlert::forBusiness($businessId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->forBranch($branchId);
            })
            ->whereDate('created_at', today())
            ->count();
        
        return [
            'total_doctors' => $total,
            'active' => $active,
            'needing_attention' => $needingAttention,
            'critical' => $critical,
            'alerts_today' => $alertsToday,
        ];
    }
}
