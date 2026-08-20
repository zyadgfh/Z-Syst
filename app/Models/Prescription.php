<?php

namespace App\Models;

use App\Models\PrescriptionItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'sale_id',
        'party_id',
        'doctor_id',
        'patient_id',
        'image',
        'notes',
        'status',
        'prescription_number',
        'review_status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
        'patient_name',
        'patient_phone',
        'doctor_name',
        'doctor_license',
        'used_at',
        'meta',
        'max_refills',
        'refill_count',
        'refill_expiry_date',
        'is_controlled_substance',
        'schedule',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
        'reviewed_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'date',
    ];

    /**
     * Get the business that owns the prescription.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the doctor associated with the prescription.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient associated with the prescription.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the sale associated with the prescription.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the party (customer) associated with the prescription.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Get the items for this prescription.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Determine if the prescription is eligible for use.
     */
    public function canBeUsed(): bool
    {
        if ($this->status === 'used') {
            return false;
        }

        if (($this->review_status ?? 'pending') !== 'approved') {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the prescription has already expired.
     */
    public function isExpired(): bool
    {
        if (empty($this->expires_at)) {
            return false;
        }

        return Carbon::parse($this->expires_at)->startOfDay()->lt(Carbon::today());
    }

    /**
     * Mark the prescription as used and optionally attach compliance metadata.
     */
    public function markAsUsed(array $meta = []): self
    {
        $mergedMeta = array_merge((array) $this->meta, $meta, [
            'used_at' => now()->toDateTimeString(),
        ]);

        $this->forceFill([
            'status' => 'used',
            'used_at' => now(),
            'meta' => $mergedMeta,
        ])->save();

        return $this;
    }

    /**
     * Determine the urgency of the expiry date.
     */
    public function getExpiryStatus(): string
    {
        if (empty($this->expires_at)) {
            return 'none';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        $days = Carbon::parse($this->expires_at)->startOfDay()->diffInDays(Carbon::today(), false);

        if ($days <= 0) {
            return 'expired';
        }

        if ($days <= 7) {
            return 'critical';
        }

        if ($days <= 30) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Check if prescription can be refilled.
     */
    public function canBeRefilled(): bool
    {
        if ($this->status === 'used' && $this->refill_count >= $this->max_refills) {
            return false;
        }

        if ($this->refill_expiry_date && Carbon::parse($this->refill_expiry_date)->lt(now())) {
            return false;
        }

        return $this->refill_count < $this->max_refills;
    }

    /**
     * Get remaining refills count.
     */
    public function getRemainingRefills(): int
    {
        return max(0, $this->max_refills - $this->refill_count);
    }

    /**
     * Increment refill count.
     */
    public function incrementRefill(): self
    {
        $this->increment('refill_count');
        return $this->fresh();
    }

    /**
     * Check if this is a controlled substance.
     */
    public function isControlledSubstance(): bool
    {
        return $this->is_controlled_substance ?? false;
    }

    /**
     * Get controlled substance schedule.
     */
    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    /**
     * Scope to filter by controlled substances.
     */
    public function scopeControlledSubstances($query)
    {
        return $query->where('is_controlled_substance', true);
    }

    /**
     * Scope to filter by schedule.
     */
    public function scopeBySchedule($query, $schedule)
    {
        return $query->where('schedule', $schedule);
    }
}
