<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device — an enrolled client (POS terminal, tablet, PC, phone) that works
 * against this business, with its attached peripherals: barcode scanner and
 * the printers it prints to (or shared network printers).
 */
class Device extends Model
{
    public const TYPE_POS = 'pos';
    public const TYPE_TABLET = 'tablet';
    public const TYPE_PC = 'pc';
    public const TYPE_MOBILE = 'mobile';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'business_id', 'uuid', 'token', 'fingerprint', 'name', 'type', 'platform',
        'user_agent', 'ip_address', 'has_scanner', 'barcode_printer_id',
        'receipt_printer_id', 'peripherals', 'is_active', 'is_approved',
        'is_current', 'last_seen_at',
    ];

    protected $casts = [
        'peripherals' => 'array',
        'has_scanner' => 'boolean',
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function barcodePrinter(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'barcode_printer_id');
    }

    public function receiptPrinter(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'receipt_printer_id');
    }

    public function scopeForBusiness(Builder $query, ?int $businessId): Builder
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /** The effective barcode printer: device assignment else business default. */
    public function effectiveBarcodePrinter(): ?Printer
    {
        return $this->barcodePrinter
            ?? Printer::forBusiness($this->business_id)->ofType(Printer::TYPE_BARCODE)->active()->where('is_default_barcode', true)->first();
    }

    public function effectiveReceiptPrinter(): ?Printer
    {
        return $this->receiptPrinter
            ?? Printer::forBusiness($this->business_id)->ofType(Printer::TYPE_RECEIPT)->active()->where('is_default_receipt', true)->first();
    }

    /** Everything a POS client needs to render its UI. */
    public function clientPayload(): array
    {
        $barcode = $this->effectiveBarcodePrinter();
        $receipt = $this->effectiveReceiptPrinter();

        return [
            'device' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'type' => $this->type,
                'has_scanner' => (bool) $this->has_scanner,
                'is_approved' => (bool) $this->is_approved,
                'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            ],
            'printers' => [
                'barcode' => $barcode ? ['id' => $barcode->id, 'name' => $barcode->name, 'connection' => $barcode->connection] : null,
                'receipt' => $receipt ? ['id' => $receipt->id, 'name' => $receipt->name, 'connection' => $receipt->connection] : null,
            ],
        ];
    }
}
