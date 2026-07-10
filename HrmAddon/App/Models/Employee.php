<?php

namespace Modules\HrmAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_id',
        'designation_id',
        'department_id',
        'shift_id',
        'phone',
        'amount',
        'email',
        'gender',
        'country',
        'birth_date',
        'join_date',
        'image',
        'status',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
