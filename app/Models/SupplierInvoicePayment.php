<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierInvoicePayment - table `supplier_invoice_payments` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierInvoicePayment extends Model
{
    use SoftDeletes;

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
}
