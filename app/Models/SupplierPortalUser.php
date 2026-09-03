<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SupplierPortalUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'party_id',
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the supplier portal user.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the party (supplier) associated with the user.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Get the order responses for the supplier.
     */
    public function orderResponses()
    {
        return $this->hasMany(SupplierOrderResponse::class);
    }

    /**
     * Get the shipments for the supplier.
     */
    public function shipments()
    {
        return $this->hasMany(SupplierShipment::class);
    }

    /**
     * Get the invoices uploaded by the supplier.
     */
    public function invoices()
    {
        return $this->hasMany(SupplierPortalInvoice::class);
    }

    /**
     * Get the activity logs for the supplier.
     */
    public function activities()
    {
        return $this->hasMany(SupplierPortalActivity::class);
    }

    /**
     * Get the notifications for the supplier.
     */
    public function notifications()
    {
        return $this->hasMany(SupplierNotification::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is viewer.
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if user can manage orders.
     */
    public function canManageOrders(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    /**
     * Check if user can upload invoices.
     */
    public function canUploadInvoices(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    /**
     * Check if user can view analytics.
     */
    public function canViewAnalytics(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}