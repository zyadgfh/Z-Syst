<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class PurchaseOrder extends Model
{
    use HasCompany;

    protected $fillable = ['company_id','branch_id','uuid','status','total','currency'];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
