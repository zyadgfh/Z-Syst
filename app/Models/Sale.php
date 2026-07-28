<?php

namespace App\Models;

use App\Models\InvoiceNotification;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory, HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'business_id',
        'branch_id',
        'party_id',
        'user_id',
        'created_by',
        'tax_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'discountAmount',
        'dueAmount',
        'isPaid',
        'status',
        'tax_amount',
        'paidAmount',
        'totalAmount',
        'lossProfit',
        'paymentType',
        'payment_method',
        'invoiceNumber',
        'invoice_number',
        'saleDate',
        'subtotal',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'change_amount',
        'sale_data',
        'notes',
        'prescription_id',
        'void_reason',
        'voided_at',
        'meta',
    ];

    protected $casts = [
        'discountAmount' => 'double',
        'dueAmount' => 'double',
        'isPaid' => 'boolean',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'paidAmount' => 'double',
        'totalAmount' => 'double',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'meta' => 'json',
        'sale_data' => 'json',
        'voided_at' => 'datetime',
    ];

    // ── Legacy (old table) relationships ──

    public function details()
    {
        return $this->hasMany(SaleDetails::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    public function notifications()
    {
        return $this->hasMany(InvoiceNotification::class, 'sale_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // ── New module relationships ──

    /**
     * Items in this sale (from the new sale_items table).
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Payments recorded for this sale (split payments).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }

    /**
     * Customer (patient) this sale belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Cashier/user who created this sale.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cash Register associated with this sale (if any).
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->invoiceNumber) && empty($model->invoice_number)) {
                $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : auth()->user()->company_id;
                $id = Sale::where('company_id', $companyId)->count() + 1;
                $model->invoiceNumber = "S-" . str_pad($id, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get total items count from sale_items.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity') ?? $this->details->sum('quantities') ?? 0;
    }

    /**
     * Check if customer has WhatsApp preference.
     */
    public function customerWantsWhatsApp(): bool
    {
        $preferences = $this->party->notification_preferences ?? [];
        return $preferences['whatsapp'] ?? true;
    }

    /**
     * Get invoice PDF URL.
     */
    public function getPdfUrlAttribute(): string
    {
        $invNo = $this->invoice_number ?? $this->invoiceNumber;
        $pdfPath = "invoices/invoice-{$invNo}.pdf";
        return asset('storage/' . $pdfPath);
    }
}
