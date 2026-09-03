<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'branch_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'transaction_date',
        'category',
        'payment_method',
        'user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the business that owns the transaction.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the transaction.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referenced model (polymorphic).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Check if transaction is revenue.
     */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Scope a query to only include revenue transactions.
     */
    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}