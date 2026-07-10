<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'company_id',
        'business_id',
        'drug_id',
        'type',
        'quantity',
        'unit_cost',
        'notes',
    ];
}
