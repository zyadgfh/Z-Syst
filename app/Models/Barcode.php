<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Barcode - table `barcodes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Barcode extends Model
{
    use SoftDeletes;

    protected $table = 'barcodes';

    protected $fillable = [
        'product_id',
        'batch_id',
        'barcode_number',
        'barcode_type',
        'barcode_image',
        'print_status',
        'printed_at',
        'printed_by',
        'print_count',
        'size',
        'print_settings',
        'is_active',
        'business_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'batch_id' => 'integer',
        'printed_at' => 'datetime',
        'print_count' => 'integer',
        'print_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
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
