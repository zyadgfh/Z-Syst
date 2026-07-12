<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DueCollect extends Model
{
    use HasFactory, HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'party_id',
        'user_id',
        'sale_id',
        'purchase_id',
        'invoiceNumber',
        'totalDue',
        'dueAmountAfterPay',
        'payDueAmount',
        'paymentType',
        'paymentDate',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : auth()->user()->company_id;
            $id = DueCollect::where('company_id', $companyId)->count() + 1;
            $model->invoiceNumber = "D-" . str_pad($id, 5, '0', STR_PAD_LEFT);
        });
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party() : BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function sale() : BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase() : BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'party_id' => 'integer',
        'payDueAmount' => 'double',
    ];
}
