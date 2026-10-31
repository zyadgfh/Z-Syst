<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Business - table `businesses` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Business extends Model
{
    protected $table = 'businesses';

    protected $fillable = [
        'company_name',
        'address',
        'phone_number',
        'picture_url',
        'will_expire',
        'subscription_date',
        'remaining_shop_balance',
        'shop_opening_balance',
    ];

    protected $casts = [
        'will_expire' => 'date',
        'subscription_date' => 'datetime',
        'remaining_shop_balance' => 'decimal:2',
        'shop_opening_balance' => 'decimal:2',
    ];

    public function agingReports(): HasMany
    {
        return $this->hasMany(AgingReport::class, 'business_id');
    }

    public function appUsers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'business_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'business_id');
    }

    public function autoOrderRules(): HasMany
    {
        return $this->hasMany(AutoOrderRule::class, 'business_id');
    }

    public function autoOrderSuggestions(): HasMany
    {
        return $this->hasMany(AutoOrderSuggestion::class, 'business_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'business_id');
    }

    public function batchLots(): HasMany
    {
        return $this->hasMany(BatchLot::class, 'business_id');
    }

    public function boxSizes(): HasMany
    {
        return $this->hasMany(BoxSize::class, 'business_id');
    }

    public function branchs(): HasMany
    {
        return $this->hasMany(Branch::class, 'business_id');
    }

    public function budgetAlerts(): HasMany
    {
        return $this->hasMany(BudgetAlert::class, 'business_id');
    }

    public function cashRegisterTransactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class, 'business_id');
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'business_id');
    }

    public function categorys(): HasMany
    {
        return $this->hasMany(Category::class, 'business_id');
    }

    public function controlledSubstancesLogs(): HasMany
    {
        return $this->hasMany(ControlledSubstancesLog::class, 'business_id');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'business_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'business_id');
    }

    public function customerInteractions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'business_id');
    }

    public function doctorActivitys(): HasMany
    {
        return $this->hasMany(DoctorActivity::class, 'business_id');
    }

    public function doctorAttentionAlerts(): HasMany
    {
        return $this->hasMany(DoctorAttentionAlert::class, 'business_id');
    }

    public function doctorAttentionScores(): HasMany
    {
        return $this->hasMany(DoctorAttentionScore::class, 'business_id');
    }

    public function doctorAttentionSettings(): HasMany
    {
        return $this->hasMany(DoctorAttentionSetting::class, 'business_id');
    }

    public function drugInteractions(): HasMany
    {
        return $this->hasMany(DrugInteraction::class, 'business_id');
    }

    public function expenseCategorys(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'business_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'business_id');
    }

    public function fefoLogs(): HasMany
    {
        return $this->hasMany(FefoLog::class, 'business_id');
    }

    public function fefoSettings(): HasMany
    {
        return $this->hasMany(FefoSetting::class, 'business_id');
    }

    public function financialAuditLogs(): HasMany
    {
        return $this->hasMany(FinancialAuditLog::class, 'business_id');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'business_id');
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class, 'business_id');
    }

    public function grnItems(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'business_id');
    }

    public function incomeCategorys(): HasMany
    {
        return $this->hasMany(IncomeCategory::class, 'business_id');
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'business_id');
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'business_id');
    }

    public function insuranceCompanys(): HasMany
    {
        return $this->hasMany(InsuranceCompany::class, 'business_id');
    }

    public function insurancePolicys(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'business_id');
    }

    public function loyaltyPrograms(): HasMany
    {
        return $this->hasMany(LoyaltyProgram::class, 'business_id');
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'business_id');
    }

    public function manufacturers(): HasMany
    {
        return $this->hasMany(Manufacturer::class, 'business_id');
    }

    public function medicineTypes(): HasMany
    {
        return $this->hasMany(MedicineType::class, 'business_id');
    }

    public function partys(): HasMany
    {
        return $this->hasMany(Party::class, 'business_id');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class, 'business_id');
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'business_id');
    }

    public function prescriptionRefills(): HasMany
    {
        return $this->hasMany(PrescriptionRefill::class, 'business_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'business_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'business_id');
    }

    public function purchaseBudgets(): HasMany
    {
        return $this->hasMany(PurchaseBudget::class, 'business_id');
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'business_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'business_id');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'business_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'business_id');
    }

    public function purchaseReturnDetails(): HasMany
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'business_id');
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'business_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'business_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'business_id');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'business_id');
    }

    public function qualityReports(): HasMany
    {
        return $this->hasMany(QualityReport::class, 'business_id');
    }

    public function qualityStandards(): HasMany
    {
        return $this->hasMany(QualityStandard::class, 'business_id');
    }

    public function recallEvents(): HasMany
    {
        return $this->hasMany(RecallEvent::class, 'business_id');
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'business_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'business_id');
    }

    public function saleReturnDetails(): HasMany
    {
        return $this->hasMany(SaleReturnDetail::class, 'business_id');
    }

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'business_id');
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'business_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'business_id');
    }

    public function stockAudits(): HasMany
    {
        return $this->hasMany(StockAudit::class, 'business_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'business_id');
    }

    public function stockTakeItems(): HasMany
    {
        return $this->hasMany(StockTakeItem::class, 'business_id');
    }

    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class, 'business_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'business_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'business_id');
    }

    public function subscriptionInvoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class, 'business_id');
    }

    public function subscriptionLogs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class, 'business_id');
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'business_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'business_id');
    }

    public function supplierInvoicePayments(): HasMany
    {
        return $this->hasMany(SupplierInvoicePayment::class, 'business_id');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class, 'business_id');
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'business_id');
    }

    public function taxs(): HasMany
    {
        return $this->hasMany(Tax::class, 'business_id');
    }

    public function traceabilityLogs(): HasMany
    {
        return $this->hasMany(TraceabilityLog::class, 'business_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'business_id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class, 'business_id');
    }

    public function warehouseStockBatchs(): HasMany
    {
        return $this->hasMany(WarehouseStockBatch::class, 'business_id');
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'business_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'business_id');
    }
}
