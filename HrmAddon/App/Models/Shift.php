<?php

namespace Modules\HrmAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'business_id', 'start_time', 'end_time', 'start_break_time','end_break_time', 'status', 'break_time', 'break_status'];
}
