<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Barcode - table `barcodes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Barcode extends Model
{
    use HasFactory;

    use SoftDeletes;

    public const TYPE_CODE128 = 'CODE128';
    public const TYPE_EAN13 = 'EAN13';
    public const TYPE_UPC = 'UPC';
    public const TYPE_QR = 'QR';

    public const SIZE_SMALL = 'small';
    public const SIZE_STANDARD = 'standard';
    public const SIZE_LARGE = 'large';

    public const STATUS_NOT_PRINTED = 'not_printed';
    public const STATUS_PRINTED = 'printed';
    public const STATUS_REPRINTED = 'reprinted';

    protected $table = 'barcodes';

    protected $fillable = [
        'product_id',
        'batch_id',
        'barcode_number',
        'barcode_type',
        'barcode_image',
        'print_status',
        'printed_at',
        'printed_by',
        'print_count',
        'size',
        'print_settings',
        'is_active',
        'business_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'batch_id' => 'integer',
        'printed_at' => 'datetime',
        'print_count' => 'integer',
        'print_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Batch (stock row) the barcode was minted for, when applicable. */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'batch_id');
    }

    /** AppUser that last printed this label. */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'printed_by');
    }

    /*
     * Query scopes used by BarcodeService and both controllers.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeForProduct($query, $productId)
    {
        return $productId ? $query->where('product_id', $productId) : $query;
    }

    public function scopeForBatch($query, $batchId)
    {
        return $batchId ? $query->where('batch_id', $batchId) : $query;
    }

    public function scopeByPrintStatus($query, $status)
    {
        return $status ? $query->where('print_status', $status) : $query;
    }

    public function scopeByType($query, $type)
    {
        return $type ? $query->where('barcode_type', $type) : $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotPrinted($query)
    {
        return $query->where('print_status', self::STATUS_NOT_PRINTED);
    }

    /**
     * Mint a new, unique barcode number for the given symbology.
     *
     * - EAN13/UPC: GS1-style — company-ish prefix + random digits + valid
     *   check digit, so scanners accept the label out of the box.
     * - CODE128/QR: compact alphanumeric token (internal code).
     */
    public static function generateBarcodeNumber(string $type): string
    {
        return match ($type) {
            self::TYPE_CODE128 => self::uniqueNumber(fn () => 'BC'.strtoupper(Str::random(9))),
            self::TYPE_EAN13 => self::uniqueNumber(fn () => self::ean13(
                '200'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT)
            )),
            self::TYPE_UPC => self::uniqueNumber(fn () => self::upcA(
                '0'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT)
            )),
            self::TYPE_QR => self::uniqueNumber(fn () => 'ZS-QR-'.strtoupper(Str::random(10))),
            default => self::uniqueNumber(fn () => 'ZS'.strtoupper(Str::random(9))),
        };
    }

    /** Mark the label printed (first time) or reprinted afterwards.
     *
     * printed_by has a legacy FK to the retired integer `users` table while
     * real principals live in app_users (UUIDs) — store the id only when a
     * matching legacy row exists, otherwise null (print_count/printed_at
     * still record the event; the actor rides in print_settings.audit).
     */
    public function markAsPrinted(int|string|null $userId): void
    {
        $stored = null;
        if (is_numeric($userId)
            && \Illuminate\Support\Facades\DB::table('users')->where('id', (int) $userId)->exists()) {
            $stored = (int) $userId;
        }

        $settings = $this->print_settings ?? [];
        if ($userId !== null && ! $stored) {
            $settings['printed_by_actor'] = (string) $userId;
        }

        $this->forceFill([
            'print_status' => $this->print_count > 0 ? self::STATUS_REPRINTED : self::STATUS_PRINTED,
            'printed_at' => now(),
            'printed_by' => $stored,
            'print_count' => $this->print_count + 1,
            'print_settings' => $settings,
        ])->save();
    }

    /** EAN-13 check digit over the first 12 digits. */
    public static function ean13Checksum(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $twelveDigits[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    /** Complete a 12-digit EAN-13 body into a valid 13-digit number. */
    public static function ean13(string $twelveDigits): string
    {
        return $twelveDigits.self::ean13Checksum($twelveDigits);
    }

    /** UPC-A: 11 digits + check digit (same math as EAN-13 with leading 0). */
    public static function upcA(string $elevenDigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int) $elevenDigits[$i] * ($i % 2 === 0 ? 3 : 1);
        }

        return $elevenDigits.((10 - ($sum % 10)) % 10);
    }

    private static function uniqueNumber(callable $factory, int $attempts = 5): string
    {
        foreach (range(1, $attempts) as $attempt) {
            $number = $factory();
            if (! self::withTrashed()->where('barcode_number', $number)->exists()) {
                return $number;
            }
        }

        return $factory(); // last resort: uniqueness constraint guards
    }


    /** Test/label contract: public checksum helpers over the GS1 internals. */
    public static function calculateEAN13Checksum(string $digits12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $digits12[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    public static function calculateUPCChecksum(string $digits11): int
    {
        $odd = 0;
        $even = 0;
        for ($i = 0; $i < 11; $i++) {
            if ($i % 2 === 0) {
                $odd += (int) $digits11[$i];
            } else {
                $even += (int) $digits11[$i];
            }
        }

        return (10 - (($odd * 3 + $even) % 10)) % 10;
    }
}
  