<?php

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'branch_id',
        'uuid',
        'po_number',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'expected_delivery_date',
        'notes',
        'approved_by',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function purchaseOrderReturns(): HasMany
    {
        return $this->hasMany(PurchaseOrderReturn::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return self::withoutGlobalScope(TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
