<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'gateway_id',
        'gateway_type',
        'transaction_type',
        'reference_id',
        'internal_reference',
        'amount',
        'currency',
        'status',
        'payment_data',
        'metadata',
        'customer_phone',
        'customer_email',
        'completed_at',
        'failed_at',
        'failure_reason',
        'processed_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'json',
        'metadata' => 'json',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'payment_data',
        'metadata',
    ];

    /**
     * Get the company that owns the transaction.
     */
    public function company()
    {
        return $this->belongsTo(Business::class, 'company_id');
    }

    /**
     * Get the branch that owns the transaction.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the payment gateway used for the transaction.
     */
    public function gateway()
    {
        return $this->belongsTo(CompanyPaymentGateway::class, 'gateway_id');
    }

    /**
     * Get the user who processed the transaction.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to filter by gateway type.
     */
    public function scopeByGatewayType($query, $gatewayType)
    {
        return $query->where('gateway_type', $gatewayType);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include refunded transactions.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Mark transaction as completed.
     */
    public function markAsCompleted($referenceId = null, $paymentData = null)
    {
        $this->status = 'completed';
        $this->completed_at = now();
        if ($referenceId) {
            $this->reference_id = $referenceId;
        }
        if ($paymentData) {
            $this->payment_data = $paymentData;
        }
        $this->save();
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed($reason, $paymentData = null)
    {
        $this->status = 'failed';
        $this->failed_at = now();
        $this->failure_reason = $reason;
        if ($paymentData) {
            $this->payment_data = $paymentData;
        }
        $this->save();
    }

    /**
     * Mark transaction as refunded.
     */
    public function markAsRefunded($refundData = null)
    {
        $this->status = 'refunded';
        if ($refundData) {
            $this->metadata = array_merge($this->metadata ?? [], $refundData);
        }
        $this->save();
    }

    /**
     * Transaction type constants.
     */
    const TYPE_SUBSCRIPTION = 'subscription';

    const TYPE_SALE = 'sale';

    const TYPE_REFUND = 'refund';

    /**
     * Transaction status constants.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_REFUNDED = 'refunded';

    /**
     * Get available transaction types.
     */
    public static function getTransactionTypes()
    {
        return [
            self::TYPE_SUBSCRIPTION => 'Subscription',
            self::TYPE_SALE => 'Sale',
            self::TYPE_REFUND => 'Refund',
        ];
    }

    /**
     * Get available transaction statuses.
     */
    public static function getTransactionStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Get transaction type label.
     */
    public function getTransactionTypeLabelAttribute()
    {
        return self::getTransactionTypes()[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Get transaction status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::getTransactionStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Boot method to auto-generate internal reference.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->internal_reference)) {
                $transaction->internal_reference = 'TXN-'.strtoupper(uniqid()).'-'.time();
            }
        });
    }
}
