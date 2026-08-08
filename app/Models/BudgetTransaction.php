<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id', 'purchase_id', 'amount', 'transaction_date',
        'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function budget(): BelongsTo { return $this->belongsTo(PurchaseBudget::class); }
    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
}
