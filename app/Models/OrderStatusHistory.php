<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'customer_order_id',
        'status',
        'note',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Record a status change
     */
    public static function record(CustomerOrder $order, string $status, ?string $note = null, ?int $changedBy = null): self
    {
        return static::create([
            'customer_order_id' => $order->id,
            'status' => $status,
            'note' => $note,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
