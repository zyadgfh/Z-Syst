<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['business_id', 'code', 'name', 'type', 'parent_id', 'is_system'];
}
