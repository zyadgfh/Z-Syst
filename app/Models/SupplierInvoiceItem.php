<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierInvoiceItem - table `supplier_invoice_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierInvoiceItem extends Model
{
    protected $table = 'supplier_invoice_items';

    protected $fillable = [
        'supplier_invoice_id',
        'product_id',
        'purchase_detail_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'total',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'supplier_invoice_id' => 'integer',
        'product_id' => 'integer',
        'purchase_detail_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class, 'purchase_detail_id');
    }
}
