<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Loyalty Transaction Model
 * 
 * Tracks all loyalty points transactions for customers
 */
class LoyaltyTransaction extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loyalty_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'points',
        'type', // earned, redeemed, expired, adjusted
        'reason', // purchase, return, adjustment, birthday, signup
        'reference_id', // Link to sale, return, etc.
        'reference_type', // sale, sale_return, adjustment
        'balance_before',
        'balance_after',
        'expires_at',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer for this transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference(): BelongsTo
    {
        return $this->morphTo();
    }

    /**
     * Check if transaction is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get display type.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            'earned' => 'مر earned',
            'redeemed' => 'مستعملة',
            'expired' => 'منتهية',
            'adjusted' => 'معدلة',
            default => $this->type,
        };
    }
}