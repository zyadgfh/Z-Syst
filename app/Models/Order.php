<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class Order extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = ['company_id','branch_id','uuid','status','total','currency','customer_name','customer_phone','notes','created_by'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
