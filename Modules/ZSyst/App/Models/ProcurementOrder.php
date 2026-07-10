<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementOrder extends Model
{
    protected $fillable = [
        'supplier_name',
        'status',
        'expected_delivery_at',
        'notes',
        'total_amount',
    ];
}
