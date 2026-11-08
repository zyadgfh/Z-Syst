<?php

namespace App\Models;

use App\Services\SupplierInvoiceService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierInvoice - table `supplier_invoices` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierInvoice extends Model
{
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'supplier_invoices';

    protected $fillable = [
        'supplier_id',
        'business_id',
        'branch_id',
        'purchase_id',
        'purchase_order_id',
        'created_by',
        'approved_by',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'paid_amount',
        'balance',
        'currency',
        'payment_terms',
        'notes',
        'internal_notes',
        'file_path',
        'file_name',
        'file_mime_type',
        'approved_at',
        'sent_at',
        'cancelled_at',
        'cancellation_reason',
        'is_active',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'purchase_id' => 'integer',
        'purchase_order_id' => 'integer',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID
            || (float) $this->balance <= 0.0;
    }

    public function getPaymentPercentage(): float
    {
        $total = (float) $this->total_amount;
        return $total > 0 ? round(((float) $this->paid_amount / $total) * 100, 2) : 0.0;
    }

    public function isDueSoon(int $days = 7): bool
    {
        return $this->due_date !== null
            && $this->status === self::STATUS_PENDING
            && $this->due_date->isFuture()
            && $this->due_date->lte(now()->addDays($days));
    }

    public function isCriticallyOverdue(int $days = 30): bool
    {
        return $this->due_date !== null
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_PAID], true)
            && $this->due_date->lt(now()->subDays($days));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getDaysUntilDue(): int
    {
        return $this->due_date ? max(0, now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false)) : 0;
    }

    public function approve($userId = null): static
    {
        app(SupplierInvoiceService::class)->approve($this, (int) $userId ?: auth()->id());

        return $this->refresh();
    }

    public function reject($userId = null, string $reason = null): static
    {
        app(SupplierInvoiceService::class)->reject($this, (int) $userId ?: auth()->id(), (string) $reason);

        return $this->refresh();
    }

    public function cancel(string $reason = null): static
    {
        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ])->save();

        return $this->refresh();
    }
}
