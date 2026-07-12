<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory, HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'purchase_id',
        'invoice_no',
        'return_date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : auth()->user()->company_id;
            $id = PurchaseReturn::where('company_id', $companyId)->count() + 1;
            $model->invoice_no = "PR-" . str_pad($id, 5, '0', STR_PAD_LEFT);
        });
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }
}
