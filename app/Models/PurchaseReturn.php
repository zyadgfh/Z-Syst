<?php

namespace App\Models;

use App\Services\InvoiceNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class PurchaseReturn extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'purchase_id',
        'party_id',
        'user_id',
        'invoice_no',
        'return_date',
        'total_amount',
        'credit_amount',
        'status',
        'reason',
        'notes',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {            if (! $model->invoice_no && auth()->check()) {
                $businessId = auth()->user()->business_id;

                $model->invoice_no = DB::transaction(function () use ($businessId) {
                    $lastNumber = DB::table('purchase_returns')
                        ->where('business_id', $businessId)
                        ->where('invoice_no', 'like', 'PR-%')
                        ->max('invoice_no');

                    if ($lastNumber) {
                        $lastId = (int) str_replace('PR-', '', $lastNumber);
                        $newId = $lastId + 1;
                    } else {
                        $newId = 1;
                    }

                    return 'PR-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
                });
            }
        });
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }
}
