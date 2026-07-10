<?php

namespace Modules\HrmAddon\App\Models;

use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'employee_id',
        'payment_type_id',
        'month',
        'date',
        'note',
        'amount',
        'payemnt_year',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payment_type()
    {
        return $this->belongsTo(PaymentType::class);
    }

   public static function boot() {
        parent::boot();

        static::creating(function($model) {
            $id = Payroll::where('business_id', auth()->user()->business_id)->count() + 1;
            $model->puid ='Ps_' . str_pad($id, 4, '0', STR_PAD_LEFT);
        });
   }
}
