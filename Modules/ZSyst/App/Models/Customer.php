<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'loyalty_points',
    ];
}
