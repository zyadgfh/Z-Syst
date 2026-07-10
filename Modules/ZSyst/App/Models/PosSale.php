<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSale extends Model
{
    protected $fillable = [
        'customer_name',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'payment_method',
        'notes',
    ];
}
