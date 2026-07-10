<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'to_branch_id',
        'from_branch_id',
        'transfer_date',
        'invoice_no',
        'note',
        'shipping_charge',
        'sub_total',
        'total_discount',
        'total_tax',
        'grand_total',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            $lastNumber = (int) self::whereNotNull('invoice_no')
                ->orderByDesc('id')
                ->value(DB::raw("CAST(SUBSTRING(invoice_no, 4) AS UNSIGNED)")) ?? 0;

            $transfer->invoice_no = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function transferProducts()
    {
        return $this->hasMany(TransferProduct::class);
    }
}
