<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorAttentionScore extends Model
{
    use HasFactory, BelongsToBusiness;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'business_id',
        'branch_id',
        'calculated_date',
        'attention_score',
        'decline_percentage',
        'days_inactive',
        'last_referral_date',
        'baseline_referrals',
        'current_period_referrals',
        'previous_period_referrals',
        'status',
        'alert_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calculated_date' => 'date',
        'last_referral_date' => 'date',
        'attention_score' => 'decimal:2',
        'decline_percentage' => 'decimal:2',
        'days_inactive' => 'integer',
        'baseline_referrals' => 'decimal:2',
        'current_period_referrals' => 'integer',
        'previous_period_referrals' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_NEEDS_ATTENTION = 'needs_attention';
    const STATUS_CRITICAL = 'critical';

    /**
     * Get the doctor that owns the score.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'doctor_id');
    }

    /**
     * Get the business that owns the score.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the score.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the alerts for the score.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(DoctorAttentionAlert::class);
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
     * Scope to filter by doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter doctors needing attention.
     */
    public function scopeNeedingAttention($query)
    {
        return $query->whereIn('status', [self::STATUS_NEEDS_ATTENTION, self::STATUS_CRITICAL]);
    }

    /**
     * Scope to filter critical doctors.
     */
    public function scopeCritical($query)
    {
        return $query->where('status', self::STATUS_CRITICAL);
    }

    /**
     * Scope to get latest scores.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_date', 'desc');
    }

    /**
     * Check if doctor needs attention.
     */
    public function needsAttention(): bool
    {
        return in_array($this->status, [self::STATUS_NEEDS_ATTENTION, self::STATUS_CRITICAL]);
    }

    /**
     * Check if doctor is critical.
     */
    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * Check if attention score is low.
     */
    public function hasLowScore(): bool
    {
        return $this->attention_score < 60;
    }

    /**
     * Check if decline is significant.
     */
    public function hasSignificantDecline(): bool
    {
        return $this->decline_percentage > 30;
    }

    /**
     * Check if inactive for too long.
     */
    public function isInactiveTooLong(): bool
    {
        return $this->days_inactive > 14;
    }

    /**
     * Get urgency level.
     */
    public function getUrgencyLevel(): string
    {
        if ($this->isCritical()) {
            return 'critical';
        } elseif ($this->needsAttention()) {
            return 'high';
        } elseif ($this->hasLowScore()) {
            return 'medium';
        }
        return 'low';
    }
}
