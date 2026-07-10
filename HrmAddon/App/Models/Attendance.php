<?php

namespace Modules\HrmAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'employee_id',
        'shift_id',
        'time_in',
        'time_out',
        'date',
        'duration',
        'month',
        'note',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
