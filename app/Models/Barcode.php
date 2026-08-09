<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barcode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'print_settings' => 'array',
        'printed_at' => 'datetime',
        'is_active' => 'boolean',
        'print_count' => 'integer',
    ];

    /**
     * Barcode types
     */
    const TYPE_CODE128 = 'CODE128';

    const TYPE_EAN13 = 'EAN13';

    const TYPE_UPC = 'UPC';

    const TYPE_QR = 'QR';

    /**
     * Print statuses
     */
    const STATUS_NOT_PRINTED = 'not_printed';

    const STATUS_PRINTED = 'printed';

    const STATUS_REPRINTED = 'reprinted';

    /**
     * Barcode sizes
     */
    const SIZE_SMALL = 'small';

    const SIZE_STANDARD = 'standard';

    const SIZE_LARGE = 'large';

    /**
     * Get the product that owns the barcode.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the batch that owns the barcode.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'batch_id');
    }

    /**
     * Get the business that owns the barcode.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the barcode.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who printed the barcode.
     */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Get the user who created the barcode.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the barcode.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to filter by business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to filter by batch.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by print status.
     */
    public function scopeByPrintStatus($query, $status)
    {
        return $query->where('print_status', $status);
    }

    /**
     * Scope to filter by barcode type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('barcode_type', $type);
    }

    /**
     * Scope to filter active barcodes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter not printed barcodes.
     */
    public function scopeNotPrinted($query)
    {
        return $query->where('print_status', self::STATUS_NOT_PRINTED);
    }

    /**
     * Scope to filter printed barcodes.
     */
    public function scopePrinted($query)
    {
        return $query->where('print_status', self::STATUS_PRINTED);
    }

    /**
     * Check if barcode is printed.
     */
    public function isPrinted(): bool
    {
        return $this->print_status !== self::STATUS_NOT_PRINTED;
    }

    /**
     * Mark barcode as printed.
     */
    public function markAsPrinted(int $userId): void
    {
        $this->update([
            'print_status' => $this->print_status === self::STATUS_NOT_PRINTED
                ? self::STATUS_PRINTED
                : self::STATUS_REPRINTED,
            'printed_at' => now(),
            'printed_by' => $userId,
            'print_count' => $this->print_count + 1,
        ]);
    }

    /**
     * Generate barcode number.
     */
    public static function generateBarcodeNumber(string $type = self::TYPE_CODE128): string
    {
        return match ($type) {
            self::TYPE_EAN13 => self::generateEAN13(),
            self::TYPE_UPC => self::generateUPC(),
            self::TYPE_QR => self::generateQRData(),
            default => self::generateCODE128(),
        };
    }

    /**
     * Generate CODE128 barcode number.
     */
    private static function generateCODE128(): string
    {
        return 'BC'.str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Generate EAN13 barcode number.
     */
    private static function generateEAN13(): string
    {
        $prefix = '600'; // Country code for pharmacy
        $random = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $base = $prefix.$random;
        $checksum = self::calculateEAN13Checksum($base);

        return $base.$checksum;
    }

    /**
     * Calculate EAN13 checksum.
     */
    private static function calculateEAN13Checksum(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum;
    }

    /**
     * Generate UPC barcode number.
     */
    private static function generateUPC(): string
    {
        $random = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $checksum = self::calculateUPCChecksum($random);

        return $random.$checksum;
    }

    /**
     * Calculate UPC checksum.
     */
    private static function calculateUPCChecksum(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum;
    }

    /**
     * Generate QR data.
     */
    private static function generateQRData(): string
    {
        return 'QR-'.strtoupper(uniqid()).'-'.time();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($barcode) {
            if (auth()->check()) {
                $barcode->created_by = auth()->id();
            }
            if (empty($barcode->barcode_number)) {
                $barcode->barcode_number = self::generateBarcodeNumber($barcode->barcode_type);
            }
        });

        static::updating(function ($barcode) {
            if (auth()->check()) {
                $barcode->updated_by = auth()->id();
            }
        });
    }
}
