<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Printer — a printing endpoint (barcode/label/receipt) known to the system.
 * A printer may be a shared network device (host set) or physically attached
 * to one enrolled client device (devices.barcode_printer_id / receipt_printer_id).
 */
class Printer extends Model
{
    
    use TenantScoped;
public const TYPE_BARCODE = 'barcode';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_LABEL = 'label';

    public const CONNECTION_NETWORK = 'network';
    public const CONNECTION_USB = 'usb';
    public const CONNECTION_BLUETOOTH = 'bluetooth';
    public const CONNECTION_SYSTEM = 'system'; // OS-level driver, browser print dialog

    protected $fillable = [
        'business_id', 'name', 'type', 'connection', 'host', 'vendor', 'model',
        'is_default_barcode', 'is_default_receipt', 'settings', 'is_active', 'last_probe_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default_barcode' => 'boolean',
        'is_default_receipt' => 'boolean',
        'last_probe_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness(Builder $query, ?int $businessId): Builder
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Connection summary for UI ("شبكة 192.168.1.50:9100" / "نظام/USB"). */
    public function connectionLabel(): string
    {
        return match ($this->connection) {
            self::CONNECTION_NETWORK => 'network '.(string) $this->host,
            self::CONNECTION_USB => 'USB',
            self::CONNECTION_BLUETOOTH => 'Bluetooth',
            default => 'system',
        };
    }
}
