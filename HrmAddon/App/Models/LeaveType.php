<?php

namespace Modules\HrmAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status', 'business_id'];
}
