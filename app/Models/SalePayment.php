<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SalePayment Model
 *
 * Represents a payment made towards a sale/POS transaction.
 * Supports multiple payment methods per sale (split payments).
 *
 * @property string $id (UUID)
 * @property string $sale_id
 * @property string $payment_method
 * @property float $amount
 * @property string|null $reference_number
 * @property string|null $card_last_four
 * @property string|null $transaction_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SalePayment extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_payments';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'sale_id',
        'payment_method',
        'amount',
        'reference_number',
        'card_last_four',
        'transaction_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'sale_id' => 'string',
        'amount' => 'decimal:3',
    ];

    // ────────────────────────────── Relationships ──────────────────────────────

    /**
     * The sale this payment belongs to.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }
}

