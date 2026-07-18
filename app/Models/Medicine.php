<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'strength',
        'dosage_form',
        'purchase_price',
        'sale_price',
        'stock',
        'minimum_stock',
        'expiry_date',
        'batch_number',
    ];
}
