<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GRN extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'goods_received_notes';

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'received_by',
        'verified_by',
        'grn_number',
        'received_date',
        'location',
        'status',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'received_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class, 'grn_id');
    }
}
