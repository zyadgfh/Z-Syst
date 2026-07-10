<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'drug_id',
        'batch_number',
        'expiry_date',
        'quantity_on_hand',
        'unit_cost',
        'location',
    ];
}
