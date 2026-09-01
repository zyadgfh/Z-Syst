<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\GRN;
use App\Models\Income;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\OnboardingTemplate;
use App\Models\TenantOnboardingInstance;
use App\Models\LoyaltyProgram;
use App\Models\Manufacturer;
use App\Models\MedicineType;
use App\Models\Party;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\StockAudit;
use App\Models\StockTransfer;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\FinancialAuditLog;
use App\Models\StockMovement;
use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Barcode;
use App\Models\BatchLot;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Notification;
use App\Models\Plan;
use App\Policies\AuditLogPolicy;
use App\Policies\BannerPolicy;
use App\Policies\BarcodePolicy;
use App\Policies\BusinessCategoryPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\PlanPolicy;
use App\Policies\RolePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\GRNPolicy;
use App\Policies\IncomePolicy;
use App\Policies\InsuranceClaimPolicy;
use App\Policies\InsuranceCompanyPolicy;
use App\Policies\InsurancePolicyPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\TenantOnboardingPolicy;
use App\Policies\LoyaltyProgramPolicy;
use App\Policies\ManufacturerPolicy;
use App\Policies\MedicineTypePolicy;
use App\Policies\PartyPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseReturnPolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\SalePolicy;
use App\Policies\SaleReturnPolicy;
use App\Policies\StockPolicy;
use App\Policies\StockAuditPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TaxPolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\FinancialAuditLogPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\BatchLotPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierInvoicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Stock::class => StockPolicy::class,
        Sale::class => SalePolicy::class,
        Purchase::class => PurchasePolicy::class,
        Party::class => PartyPolicy::class,
        Category::class => CategoryPolicy::class,
        Unit::class => UnitPolicy::class,
        Manufacturer::class => ManufacturerPolicy::class,
        MedicineType::class => MedicineTypePolicy::class,
        Tax::class => TaxPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        InsuranceCompany::class => InsuranceCompanyPolicy::class,
        InsurancePolicy::class => InsurancePolicyPolicy::class,
        InsuranceClaim::class => InsuranceClaimPolicy::class,
        Campaign::class => CampaignPolicy::class,
        Invoice::class => InvoicePolicy::class,
        TenantOnboardingInstance::class => TenantOnboardingPolicy::class,
        OnboardingTemplate::class => TenantOnboardingPolicy::class,
        Expense::class => ExpensePolicy::class,
        Income::class => IncomePolicy::class,
        Supplier::class => SupplierPolicy::class,
        Receipt::class => ReceiptPolicy::class,
        LoyaltyProgram::class => LoyaltyProgramPolicy::class,
        Prescription::class => PrescriptionPolicy::class,
        SaleReturn::class => SaleReturnPolicy::class,
        PurchaseReturn::class => PurchaseReturnPolicy::class,
        StockAudit::class => StockAuditPolicy::class,
        FinancialAuditLog::class => FinancialAuditLogPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        GRN::class => GRNPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        BatchLot::class => BatchLotPolicy::class,
        Notification::class => NotificationPolicy::class,
        SupplierInvoice::class => SupplierInvoicePolicy::class,
        StockTransfer::class => StockTransferPolicy::class,
        Barcode::class => BarcodePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Banner::class => BannerPolicy::class,
        Business::class => BusinessPolicy::class,
        BusinessCategory::class => BusinessCategoryPolicy::class,
        Currency::class => CurrencyPolicy::class,
        Plan::class => PlanPolicy::class,
        Coupon::class => CouponPolicy::class,
        User::class => UserPolicy::class,
        \Spatie\Permission\Models\Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Optional: Define gates here if needed
        Gate::define('view-dashboard', function ($user) {
            return in_array($user->role, ['admin', 'superadmin']);
        });
    }
}
