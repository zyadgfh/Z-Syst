<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebitItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'parent_type', 'product_id', 'quantity',
        'unit_price', 'amount', 'reason', 'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function parent(): BelongsTo { return $this->morphTo(); }
}
