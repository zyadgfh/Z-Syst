<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorAttentionAlert extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'business_id',
        'branch_id',
        'medical_rep_id',
        'alert_type',
        'severity',
        'message',
        'details',
        'is_sent',
        'sent_at',
        'is_read',
        'read_at',
        'action_taken',
        'action_details',
        'action_taken_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'action_taken' => 'boolean',
        'action_taken_at' => 'datetime',
    ];

    /**
     * Alert type constants
     */
    const TYPE_REFERRAL_DROP = 'referral_drop';
    const TYPE_INACTIVITY = 'inactivity';
    const TYPE_CRITICAL = 'critical';

    /**
     * Severity constants
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the doctor that owns the alert.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'doctor_id');
    }

    /**
     * Get the business that owns the alert.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the alert.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the medical rep that owns the alert.
     */
    public function medicalRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medical_rep_id');
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
     * Scope to filter by medical rep.
     */
    public function scopeForMedicalRep($query, $repId)
    {
        return $query->where('medical_rep_id', $repId);
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter unsent alerts.
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope to filter sent alerts.
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Scope to filter unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to filter alerts requiring action.
     */
    public function scopeRequiresAction($query)
    {
        return $query->where('action_taken', false);
    }

    /**
     * Mark alert as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark alert as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark alert as action taken.
     */
    public function markAsActionTaken(string $details = null): void
    {
        $this->update([
            'action_taken' => true,
            'action_details' => $details,
            'action_taken_at' => now(),
        ]);
    }

    /**
     * Check if alert is sent.
     */
    public function isSent(): bool
    {
        return $this->is_sent;
    }

    /**
     * Check if alert is read.
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if action is taken.
     */
    public function hasActionTaken(): bool
    {
        return $this->action_taken;
    }
}
