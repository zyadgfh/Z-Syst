<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAttentionSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'branch_id',
        'referral_drop_threshold',
        'referral_drop_period_days',
        'inactivity_threshold_days',
        'critical_inactivity_days',
        'attention_score_warning',
        'attention_score_critical',
        'enable_push_notifications',
        'enable_email_notifications',
        'enable_sms_notifications',
        'notify_roles',
        'notify_users',
        'alert_frequency_hours',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'referral_drop_threshold' => 'decimal:2',
        'attention_score_warning' => 'decimal:2',
        'attention_score_critical' => 'decimal:2',
        'enable_push_notifications' => 'boolean',
        'enable_email_notifications' => 'boolean',
        'enable_sms_notifications' => 'boolean',
        'notify_roles' => 'array',
        'notify_users' => 'array',
    ];

    /**
     * Get the business that owns the settings.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the settings.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get default settings for business.
     */
    public static function getDefaults(int $businessId, ?int $branchId = null): self
    {
        $settings = self::forBusiness($businessId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->forBranch($branchId);
            })
            ->first();

        if (! $settings) {
            $settings = self::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'referral_drop_threshold' => 30,
                'referral_drop_period_days' => 30,
                'inactivity_threshold_days' => 14,
                'critical_inactivity_days' => 30,
                'attention_score_warning' => 60,
                'attention_score_critical' => 30,
                'enable_push_notifications' => true,
                'enable_email_notifications' => false,
                'enable_sms_notifications' => false,
                'notify_roles' => ['owner', 'medical_rep'],
                'alert_frequency_hours' => 24,
            ]);
        }

        return $settings;
    }
}
