<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierInvoicePayment - table `supplier_invoice_payments` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierInvoicePayment extends Model
{
    
    use TenantScoped;
use HasFactory;

    use SoftDeletes;

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_CARD = 'card';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'supplier_invoice_payments';

    protected $fillable = [
        'supplier_invoice_id',
        'business_id',
        'branch_id',
        'created_by',
        'approved_by',
        'payment_number',
        'payment_date',
        'payment_method',
        'payment_reference',
        'bank_reference',
        'amount',
        'status',
        'notes',
        'approved_at',
        'file_path',
        'file_name',
        'file_mime_type',
    ];

    protected $casts = [
        'supplier_invoice_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve($userId = null): static
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId ?: auth()->id(),
            'approved_at' => now(),
        ])->save();

        return $this->refresh();
    }
}
