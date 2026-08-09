<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_PARTIALLY_PAID = 'partially_paid';

    const STATUS_PAID = 'paid';

    const STATUS_OVERDUE = 'overdue';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REJECTED = 'rejected';

    /**
     * Get the supplier that owns the invoice.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    /**
     * Get the business that owns the invoice.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the invoice.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the purchase that owns the invoice.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the purchase order that owns the invoice.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the user who created the invoice.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the invoice.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierInvoicePayment::class);
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
     * Scope to filter by supplier.
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active invoices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->where('due_date', '<', now());
    }

    /**
     * Scope to filter unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Check if invoice is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invoice is approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID]);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE ||
               ($this->status !== self::STATUS_PAID && $this->due_date < now());
    }

    /**
     * Approve invoice.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_amount' => $this->total_amount,
            'balance' => 0,
        ]);
    }

    /**
     * Mark invoice as partially paid.
     */
    public function markAsPartiallyPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PARTIALLY_PAID,
        ]);
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update([
            'status' => self::STATUS_OVERDUE,
        ]);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Reject invoice.
     */
    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Add payment to invoice.
     */
    public function addPayment(float $amount): void
    {
        $this->increment('paid_amount', $amount);
        $this->decrement('balance', $amount);

        // Update status based on balance
        if ($this->balance <= 0) {
            $this->markAsPaid();
        } elseif ($this->paid_amount > 0) {
            $this->markAsPartiallyPaid();
        }
    }

    /**
     * Calculate total amount.
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    /**
     * Get payment percentage.
     */
    public function getPaymentPercentage(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->paid_amount / $this->total_amount) * 100;
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Check if invoice is due soon (within 7 days).
     */
    public function isDueSoon(): bool
    {
        return $this->getDaysUntilDue() <= 7 && $this->getDaysUntilDue() >= 0;
    }

    /**
     * Check if invoice is critically overdue (30+ days).
     */
    public function isCriticallyOverdue(): bool
    {
        return $this->getDaysUntilDue() < -30;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (auth()->check()) {
                $invoice->created_by = auth()->id();
            }
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->business_id);
            }
            if (empty($invoice->invoice_date)) {
                $invoice->invoice_date = now();
            }
            if (empty($invoice->due_date)) {
                $invoice->due_date = now()->addDays(30); // Default 30 days
            }
        });

        static::updating(function ($invoice) {
            if (auth()->check()) {
                // Handle approval logic
                if ($invoice->isDirty('status') && $invoice->status === self::STATUS_APPROVED) {
                    $invoice->approved_by = auth()->id();
                    $invoice->approved_at = now();
                }
            }
        });
    }

    /**
     * Generate invoice number.
     */
    private static function generateInvoiceNumber(int $businessId): string
    {
        $count = self::where('business_id', $businessId)->count() + 1;

        return 'INV-'.date('Y').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
