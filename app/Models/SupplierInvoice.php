<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierInvoice - table `supplier_invoices` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_invoices';

    protected $fillable = [
        'supplier_id',
        'business_id',
        'branch_id',
        'purchase_id',
        'purchase_order_id',
        'created_by',
        'approved_by',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'paid_amount',
        'balance',
        'currency',
        'payment_terms',
        'notes',
        'internal_notes',
        'file_path',
        'file_name',
        'file_mime_type',
        'approved_at',
        'sent_at',
        'cancelled_at',
        'cancellation_reason',
        'is_active',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'purchase_id' => 'integer',
        'purchase_order_id' => 'integer',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
