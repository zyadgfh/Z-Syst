<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Gateway Log Model
 * 
 * سجل جميع طلبات API لبوابات الدفع لأغرض التدقيق والتنقيب
 */
class PaymentGatewayLog extends Model
{
    protected $fillable = [
        'payment_id',
        'gateway',
        'endpoint',
        'method',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'response_status',
        'response_time_ms',
        'success',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'request_headers' => 'json',
        'request_body' => 'json',
        'response_headers' => 'json',
        'response_body' => 'json',
        'response_time_ms' => 'float',
        'success' => 'boolean',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_id');
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccess($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope by gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}