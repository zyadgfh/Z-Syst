<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'grn_item_id',
        'checker_id',
        'check_date',
        'quality_status',
        'defects',
        'damage_quantity',
        'temperature',
        'humidity',
        'notes',
        'photos',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_date' => 'datetime',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'photos' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_CONDITIONAL = 'conditional';

    /**
     * Get the GRN item for the quality check.
     */
    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GRNItem::class);
    }

    /**
     * Get the user who performed the check.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('quality_status', $status);
    }

    /**
     * Scope to filter passed checks.
     */
    public function scopePassed($query)
    {
        return $query->where('quality_status', self::STATUS_PASSED);
    }

    /**
     * Scope to filter failed checks.
     */
    public function scopeFailed($query)
    {
        return $query->where('quality_status', self::STATUS_FAILED);
    }

    /**
     * Check if quality check passed.
     */
    public function isPassed(): bool
    {
        return $this->quality_status === self::STATUS_PASSED;
    }

    /**
     * Check if quality check failed.
     */
    public function isFailed(): bool
    {
        return $this->quality_status === self::STATUS_FAILED;
    }

    /**
     * Mark as passed.
     */
    public function markAsPassed(): void
    {
        $this->update(['quality_status' => self::STATUS_PASSED]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['quality_status' => self::STATUS_FAILED]);
    }

    /**
     * Mark as conditional.
     */
    public function markAsConditional(): void
    {
        $this->update(['quality_status' => self::STATUS_CONDITIONAL]);
    }
}
