<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierDebit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id', 'business_id', 'debit_number', 'debit_date',
        'type', 'amount', 'reason', 'reference', 'status', 'approved_by',
        'approved_at', 'file_path', 'notes',
    ];

    protected $casts = [
        'debit_date' => 'datetime',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditDebitItem::class, 'parent_id')->where('parent_type', self::class);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
