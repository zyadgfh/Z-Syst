<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'description',
    ];
}
