<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScoped;

class DueCollect extends Model
{
    use TenantScoped;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'party_id',
        'user_id',
        'sale_id',
        'purchase_id',
        'invoice_number',
        'amount',
        'paid_amount',
        'due_amount',
        'payment_type',
        'payment_date',
        'note',
    ];

    /**
     * Compatibility aliases for the installed Flutter app, which still reads
     * camelCase keys (`totalDue`, `payDueAmount`, `dueAmountAfterPay`,
     * `paymentDate`, `invoiceNumber`, `paymentType`). The database and every
     * backend caller use the canonical snake_case columns; this only adds the
     * legacy names to the JSON payload so already-installed builds keep working.
     *
     * Done in toArray() rather than with accessors + $appends on purpose:
     * `getPaymentDateAttribute()` would also shadow the real `payment_date`
     * column (Laravel resolves studly('payment_date') to the same mutator name)
     * and silently drop its datetime cast.
     *
     * Remove once the mobile release that reads snake_case is fully rolled out.
     */
    public function toArray()
    {
        return parent::toArray() + [
            'totalDue' => $this->amount,
            'payDueAmount' => $this->paid_amount,
            'dueAmountAfterPay' => $this->due_amount,
            'paymentDate' => $this->payment_date,
            'invoiceNumber' => $this->invoice_number,
            'paymentType' => $this->payment_type,
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Was `auth()->user()->business_id` unconditionally: creating a due
            // collection from a queue job, seeder or console command has no
            // authenticated user, which is a fatal "read property on null".
            $businessId = $model->business_id ?? auth()->user()?->business_id;

            if ($businessId && empty($model->invoice_number)) {
                $next = static::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->count() + 1;
                $model->invoice_number = 'D-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
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
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];
}
