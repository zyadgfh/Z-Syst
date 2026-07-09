<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasCompany, HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'uuid', 'status', 'total', 'currency', 'customer_name', 'customer_phone', 'notes', 'created_by'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
