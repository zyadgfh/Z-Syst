<?php

namespace App\Models;

use App\Events\InventoryAlertCreated;
use App\Services\FirebasePushService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAlert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'business_id',
        'type',
        'severity',
        'message',
        'current_stock',
        'threshold',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'suggested_reorder_date',
        'suggested_reorder_qty',
    ];

    protected $casts = [
        'acknowledged'           => 'boolean',
        'acknowledged_at'        => 'datetime',
        'suggested_reorder_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (InventoryAlert $alert) {
            if ($alert->severity === 'critical') {
                event(new InventoryAlertCreated($alert));

                // Send FCM push notification for critical alerts
                try {
                    $pushService = new FirebasePushService();
                    $pushService->sendInventoryAlert($alert);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FCM push failed: ' . $e->getMessage());
                }
            }
        });
    }

    // ── Relationships ──

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // ── Scopes ──

    public function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    public function scopeActive($query)
    {
        return $query->where('acknowledged', false)->orderByDesc('severity')->orderByDesc('created_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical')->where('acknowledged', false);
    }

    // ── Static Methods ──

    /**
     * Scan all tracked products and generate alerts for low/out-of-stock + expiring items.
     * Returns the number of new alerts created.
     */
    public static function runInventoryScan(?int $businessId = null): int
    {
        $created = 0;

        // Clean up old unacknowledged alerts for products that are now fine
        // (we recreate fresh ones each scan)

        $query = Product::where('active', true)
            ->where('track_inventory', true)
            ->where('archived', false);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $totalStock = $product->getTotalStockAttribute();

            // ── Low Stock ──
            if ($product->alert_qty > 0 && $totalStock > 0 && $totalStock <= $product->alert_qty) {
                $reorderQty = max(
                    ($product->reorder_quantity ?? $product->alert_qty * 2) - $totalStock,
                    1
                );

                $existing = self::where('product_id', $product->id)
                    ->where('type', 'low_stock')
                    ->where('acknowledged', false)
                    ->exists();

                if (!$existing) {
                    self::create([
                        'product_id'              => $product->id,
                        'business_id'             => $product->business_id,
                        'type'                    => 'low_stock',
                        'severity'                => 'warning',
                        'message'                 => "المخزون منخفض: {$product->productName} ({$totalStock} متبقي)",
                        'current_stock'           => $totalStock,
                        'threshold'               => $product->alert_qty,
                        'suggested_reorder_qty'   => $reorderQty,
                        'suggested_reorder_date'  => now()->addDays(3),
                    ]);
                    $created++;
                }
            }

            // ── Out of Stock ──
            if ($totalStock <= 0) {
                $existing = self::where('product_id', $product->id)
                    ->where('type', 'out_of_stock')
                    ->where('acknowledged', false)
                    ->exists();

                if (!$existing) {
                    self::create([
                        'product_id'              => $product->id,
                        'business_id'             => $product->business_id,
                        'type'                    => 'out_of_stock',
                        'severity'                => 'critical',
                        'message'                 => "نفذ من المخزون: {$product->productName}",
                        'current_stock'           => 0,
                        'threshold'               => $product->reorder_point ?? $product->alert_qty,
                        'suggested_reorder_qty'   => $product->reorder_quantity ?? ($product->alert_qty * 3),
                        'suggested_reorder_date'  => now(),
                    ]);
                    $created++;
                }
            }

            // ── Expiring Soon (within 30 days) ──
            if ($product->track_expiration) {
                $expiringStocks = $product->expiringStocks(30)->get();

                if ($expiringStocks->isNotEmpty()) {
                    foreach ($expiringStocks as $stock) {
                        $existing = self::where('product_id', $product->id)
                            ->where('type', 'expiring_soon')
                            ->where('acknowledged', false)
                            ->whereDate('suggested_reorder_date', $stock->expire_date)
                            ->exists();

                        if (!$existing) {
                            $daysUntilExpiry = now()->diffInDays($stock->expire_date, false);
                            $severity = $daysUntilExpiry <= 7 ? 'critical' : 'warning';

                            self::create([
                                'product_id'              => $product->id,
                                'business_id'             => $product->business_id,
                                'type'                    => 'expiring_soon',
                                'severity'                => $severity,
                                'message'                 => "قرب انتهاء صلاحية: {$product->productName} ({$stock->productStock} وحدة — ينتهي {$stock->expire_date})",
                                'current_stock'           => $stock->productStock,
                                'threshold'               => $product->expiration_warning_days ?? 30,
                                'suggested_reorder_date'  => $stock->expire_date,
                            ]);
                            $created++;
                        }
                    }
                }

                // ── Expired ──
                $expiredStocks = $product->allStocks()
                    ->whereNotNull('expire_date')
                    ->where('expire_date', '<', now()->startOfDay())
                    ->where('productStock', '>', 0)
                    ->get();

                if ($expiredStocks->isNotEmpty()) {
                    $existing = self::where('product_id', $product->id)
                        ->where('type', 'expired')
                        ->where('acknowledged', false)
                        ->exists();

                    if (!$existing) {
                        $expiredQty = $expiredStocks->sum('productStock');
                        self::create([
                            'product_id'  => $product->id,
                            'business_id' => $product->business_id,
                            'type'        => 'expired',
                            'severity'    => 'critical',
                            'message'     => "منتهي الصلاحية: {$product->productName} ({$expiredQty} وحدة منتهية)",
                            'current_stock' => $expiredQty,
                        ]);
                        $created++;
                    }
                }
            }
        }

        return $created;
    }

    public function acknowledge(?int $userId): void
    {
        $this->update([
            'acknowledged'    => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }
}
