<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Account extends Model
{
    
    use TenantScoped;
protected $fillable = ['business_id', 'code', 'name', 'type', 'parent_id', 'is_system'];
}
