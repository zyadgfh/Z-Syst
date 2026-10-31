<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DoctorAttentionSetting - table `doctor_attention_settings` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class DoctorAttentionSetting extends Model
{
    protected $table = 'doctor_attention_settings';

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

    protected $casts = [
        'referral_drop_threshold' => 'decimal:2',
        'referral_drop_period_days' => 'integer',
        'inactivity_threshold_days' => 'integer',
        'critical_inactivity_days' => 'integer',
        'attention_score_warning' => 'decimal:2',
        'attention_score_critical' => 'decimal:2',
        'enable_push_notifications' => 'boolean',
        'enable_email_notifications' => 'boolean',
        'enable_sms_notifications' => 'boolean',
        'notify_roles' => 'array',
        'notify_users' => 'array',
        'alert_frequency_hours' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
