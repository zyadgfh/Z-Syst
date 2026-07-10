<?php

namespace Modules\HrmAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'employee_id', 'leave_type_id', 'department_id', 'start_date', 'end_date','leave_duration', 'month', 'status', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leave_type()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
