<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class ShiftCashMovement extends Model
{
    
    use TenantScoped;
protected $fillable = ['shift_id', 'business_id', 'user_id', 'type', 'amount', 'reason', 'sale_id'];

    protected $casts = ['amount' => 'float'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
