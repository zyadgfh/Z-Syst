<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceNotification extends Model
{
    use HasFactory, HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sale_id',
        'customer_id',
        'branch_id',
        'user_id',
        'channel',
        'status',
        'recipient',
        'message_id',
        'message_content',
        'file_path',
        'file_type',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * The channels available for notifications.
     */
    public static array $channels = ['whatsapp', 'sms', 'email', 'print'];

    /**
     * The statuses available for notifications.
     */
    public static array $statuses = ['pending', 'sent', 'delivered', 'failed', 'read'];

    /**
     * العلاقة مع الفاتورة
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * العلاقة مع العميل
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    /**
     * العلاقة مع الفرع
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for delivered notifications
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Get channel badge color
     */
    public function getChannelBadgeAttribute(): string
    {
        return match ($this->channel) {
            'whatsapp' => 'bg-green-100 text-green-800',
            'sms' => 'bg-blue-100 text-blue-800',
            'email' => 'bg-purple-100 text-purple-800',
            'print' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'read' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get channel label in Arabic
     */
    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'whatsapp' => 'واتساب',
            'sms' => 'رسائل نصية',
            'email' => 'بريد إلكتروني',
            'print' => 'طباعة',
            default => $this->channel,
        };
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'معلق',
            'sent' => 'مرسل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل',
            'read' => 'مقروء',
            default => $this->status,
        };
    }

    /**
     * Get files sent as array
     */
    public function getFilesSentAttribute(): array
    {
        return $this->file_type ? explode(',', $this->file_type) : [];
    }

    /**
     * Check if retry is possible
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }
}