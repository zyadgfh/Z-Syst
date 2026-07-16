<?php

namespace App\Services\Payment\Models;

use App\Models\Company;
use App\Models\User;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'company_id',
        'user_id',
        'payment_method_type',
        'transaction_type',
        'reference_id',
        'reference_type',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'status',
        'external_transaction_id',
        'external_reference',
        'payment_url',
        'qr_code_url',
        'qr_code_data',
        'mobile_number',
        'wallet_provider',
        'callback_url',
        'webhook_received',
        'webhook_payload',
        'metadata',
        'description',
        'notes',
        'initiated_at',
        'processed_at',
        'completed_at',
        'failed_at',
        'failure_reason',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'json',
        'webhook_payload' => 'json',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'webhook_received' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING->value;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED->value;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED->value;
    }

    public function isRefunded(): bool
    {
        return $this->status === TransactionStatus::REFUNDED->value;
    }

    public function markAsCompleted(?string $externalId = null): void
    {
        $this->update([
            'status' => TransactionStatus::COMPLETED->value,
            'external_transaction_id' => $externalId ?? $this->external_transaction_id,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => TransactionStatus::FAILED->value,
            'failed_at' => Carbon::now(),
            'failure_reason' => $reason,
        ]);
    }

    public function markAsRefunded(?float $amount = null, ?string $reason = null): void
    {
        $this->update([
            'status' => TransactionStatus::REFUNDED->value,
            'refunded_at' => Carbon::now(),
            'refund_amount' => $amount ?? $this->amount,
            'refund_reason' => $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::PENDING->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', TransactionStatus::FAILED->value);
    }

    public function scopeByMethod($query, PaymentMethodType $method)
    {
        return $query->where('payment_method_type', $method->value);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}