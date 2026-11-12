<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebitItem extends Model
{
    use HasFactory;

    /**
     * A supplier credit/debit line belongs to the same business as its parent
     * document, and the migration declares `business_id` NOT NULL with a foreign
     * key. CreditDebitService creates the parent SupplierCredit/SupplierDebit
     * (which get their tenant from this same trait) but created the items without
     * one, so each line either violated NOT NULL on a freshly migrated database or
     * was stored as NULL — invisible to every tenant under the `business_id =
     * tenant` filter, i.e. stock adjustments that silently disappear from the
     * supplier ledger. The service now passes it explicitly from the parent, and
     * the trait scopes reads so a line can never be reached across tenants.
     */
    use TenantScoped;

    protected $fillable = [
        'business_id', 'parent_id', 'parent_type', 'product_id', 'quantity',
        'unit_price', 'amount', 'reason', 'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function parent(): BelongsTo { return $this->morphTo(); }
}
