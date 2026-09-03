<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'supplier_portal_user_id',
        'type',
        'title',
        'message',
        'data',
        'read',
        'read_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the business that owns the notification.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the supplier user who received the notification.
     */
    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(SupplierPortalUser::class, 'supplier_portal_user_id');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'read' => false,
            'read_at' => null,
        ]);
    }
}