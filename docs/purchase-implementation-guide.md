# Z-Syst Pharmacy - Complete Purchase Management Implementation Guide

## 📋 Executive Summary

This guide provides complete implementation instructions for all 10 purchase management systems. Each system includes database schema, models, services, controllers, routes, tests, and integration points.

---

## 🎯 Implementation Order & Timeline

### Phase 1: Purchase Orders (PO) ⭐⭐⭐⭐⭐
**Timeline:** 2-3 days  
**Status:** ✅ COMPLETED

#### Files Created:
- ✅ `database/migrations/2026_08_07_000005_create_purchase_orders_table.php`
- ✅ `app/Models/PurchaseOrder.php`
- ✅ `app/Models/PurchaseOrderItem.php`
- ✅ `app/Services/PurchaseOrderService.php`
- ✅ `app/Http/Requests/PurchaseOrderRequest.php`
- ✅ `app/Http/Resources/PurchaseOrderResource.php`
- ✅ `app/Http/Resources/PurchaseOrderItemResource.php`
- ✅ `app/Http/Controllers/Admin/PurchaseOrderController.php`

#### Remaining Tasks:
```bash
# Create API Controller
# File: app/Http/Controllers/Api/PurchaseOrderController.php
# Copy from Admin controller and adjust for API

# Add Routes
# File: routes/admin.php
Route::resource('purchase-orders', Admin\PurchaseOrderController::class)->except('show');
Route::get('purchase-orders/{purchaseOrder}', [Admin\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
Route::post('purchase-orders/{purchaseOrder}/send', [Admin\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
Route::post('purchase-orders/{purchaseOrder}/approve', [Admin\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
Route::post('purchase-orders/{purchaseOrder}/reject', [Admin\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
Route::post('purchase-orders/{purchaseOrder}/cancel', [Admin\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
Route::post('purchase-orders/{purchaseOrder}/convert', [Admin\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
Route::get('purchase-orders/pending', [Admin\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
Route::get('purchase-orders/overdue', [Admin\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
Route::get('purchase-orders/statistics', [Admin\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');

# File: routes/api.php
Route::prefix('purchase-orders')->group(function () {
    Route::apiResource('purchase-orders', Api\PurchaseOrderController::class)->except('show');
    Route::get('purchase-orders/{purchaseOrder}', [Api\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::post('purchase-orders/{purchaseOrder}/send', [Api\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/approve', [Api\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [Api\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [Api\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/convert', [Api\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
    Route::get('purchase-orders/pending', [Api\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::get('purchase-orders/overdue', [Api\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
    Route::get('purchase-orders/statistics', [Api\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');
});

# Run Migration
php artisan migrate

# Create Tests
# File: tests/Feature/PurchaseOrderTest.php
# Test all CRUD operations, status changes, conversions
```

---

### Phase 2: GRN (Goods Received Note) System ⭐⭐⭐⭐⭐
**Timeline:** 2-3 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000006_create_grn_tables.php
Schema::create('goods_received_notes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
    $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->string('grn_number')->unique();
    $table->date('received_date');
    $table->string('location')->nullable();
    $table->string('status')->default('pending'); // pending, verified, rejected
    $table->text('notes')->nullable();
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('grn_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    
    $table->integer('ordered_quantity')->default(0);
    $table->integer('received_quantity')->default(0);
    $table->integer('accepted_quantity')->default(0);
    $table->integer('rejected_quantity')->default(0);
    
    $table->string('batch_number')->nullable();
    $table->date('expiry_date')->nullable();
    $table->decimal('purchase_price', 10, 2)->default(0);
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('quality_checks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('grn_item_id')->constrained('grn_items')->cascadeOnDelete();
    $table->foreignId('checker_id')->nullable()->constrained('users')->nullOnDelete();
    
    $table->date('check_date');
    $table->string('quality_status')->default('pending'); // pending, passed, failed
    $table->text('defects')->nullable();
    $table->integer('damage_quantity')->default(0);
    $table->decimal('temperature')->nullable();
    $table->decimal('humidity')->nullable();
    $table->text('notes')->nullable();
    $table->json('photos')->nullable();
    $table->timestamps();
});
```

#### Models:
```php
// File: app/Models/GoodsReceivedNote.php
class GoodsReceivedNote extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'purchase_order_id', 'supplier_id', 'business_id', 'branch_id',
        'received_by', 'verified_by', 'grn_number', 'received_date',
        'location', 'status', 'notes', 'verified_at',
    ];
    
    protected $casts = [
        'received_date' => 'date',
        'verified_at' => 'datetime',
        'photos' => 'array',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    
    public function items() { return $this->hasMany(GRNItem::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier() { return $this->belongsTo(Party::class); }
    public function receivedBy() { return $this->belongsTo(User::class); }
    public function verifiedBy() { return $this->belongsTo(User::class); }
}

// File: app/Models/GRNItem.php
class GRNItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'grn_id', 'product_id', 'ordered_quantity', 'received_quantity',
        'accepted_quantity', 'rejected_quantity', 'batch_number',
        'expiry_date', 'purchase_price', 'notes',
    ];
    
    protected $casts = [
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];
    
    public function grn() { return $this->belongsTo(GoodsReceivedNote::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function qualityChecks() { return $this->hasMany(QualityCheck::class); }
}

// File: app/Models/QualityCheck.php
class QualityCheck extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'grn_item_id', 'checker_id', 'check_date', 'quality_status',
        'defects', 'damage_quantity', 'temperature', 'humidity',
        'notes', 'photos',
    ];
    
    protected $casts = [
        'check_date' => 'date',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'photos' => 'array',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    
    public function grnItem() { return $this->belongsTo(GRNItem::class); }
    public function checker() { return $this->belongsTo(User::class); }
}
```

#### Service:
```php
// File: app/Services/GRNService.php
class GRNService
{
    public function create(array $data): GoodsReceivedNote
    {
        return DB::transaction(function () use ($data) {
            $grn = GoodsReceivedNote::create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'business_id' => $data['business_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'received_by' => auth()->id(),
                'grn_number' => $this->generateGRNNumber($data['business_id']),
                'received_date' => $data['received_date'] ?? now(),
                'location' => $data['location'] ?? null,
                'status' => GoodsReceivedNote::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Add items
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addItem($grn, $item);
                }
            }
            
            return $grn;
        });
    }
    
    public function verify(GoodsReceivedNote $grn, int $userId): GoodsReceivedNote
    {
        $grn->update([
            'status' => GoodsReceivedNote::STATUS_VERIFIED,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
        
        // Update stock
        $this->updateStock($grn);
        
        return $grn;
    }
    
    private function updateStock(GoodsReceivedNote $grn): void
    {
        foreach ($grn->items as $item) {
            Stock::create([
                'business_id' => $grn->business_id,
                'product_id' => $item->product_id,
                'productStock' => $item->accepted_quantity,
                'batch_no' => $item->batch_number,
                'expire_date' => $item->expiry_date,
                'barcode' => null,
            ]);
        }
    }
    
    private function generateGRNNumber(int $businessId): string
    {
        $count = GoodsReceivedNote::where('business_id', $businessId)->count() + 1;
        return 'GRN-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
```

---

### Phase 3: Advanced Supplier Management ⭐⭐⭐⭐⭐
**Timeline:** 2-3 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000007_create_supplier_management_tables.php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('company_name');
    $table->string('contact_person');
    $table->string('email');
    $table->string('phone');
    $table->text('address')->nullable();
    $table->string('tax_id')->nullable();
    $table->string('license_number')->nullable();
    
    $table->decimal('rating', 2, 1)->default(0); // 1-5 stars
    $table->decimal('performance_score', 5, 2)->default(0);
    
    $table->string('payment_terms')->default('net_30');
    $table->decimal('credit_limit', 10, 2)->default(0);
    
    $table->date('contract_start')->nullable();
    $table->date('contract_end')->nullable();
    
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('supplier_ratings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->decimal('rating', 2, 1); // 1-5
    $table->string('category'); // delivery, quality, price
    $table->text('review')->nullable();
    $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('rated_at')->nullable();
    $table->timestamps();
});

Schema::create('supplier_contracts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('contract_number')->unique();
    $table->date('start_date');
    $table->date('end_date');
    $table->text('terms')->nullable();
    $table->text('conditions')->nullable();
    $table->string('file_path')->nullable();
    $table->string('status')->default('active'); // active, expired, cancelled
    $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('signed_at')->nullable();
    $table->timestamps();
});

Schema::create('supplier_performance', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
    $table->decimal('quality_score', 5, 2)->default(0);
    $table->decimal('price_competitiveness', 5, 2)->default(0);
    $table->decimal('responsiveness', 5, 2)->default(0);
    
    $table->integer('total_orders')->default(0);
    $table->integer('total_disputes')->default(0);
    
    $table->timestamp('calculated_at')->nullable();
    $table->timestamps();
});
```

#### Models:
```php
// File: app/Models/Supplier.php
class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'business_id', 'branch_id', 'company_name', 'contact_person',
        'email', 'phone', 'address', 'tax_id', 'license_number',
        'rating', 'performance_score', 'payment_terms', 'credit_limit',
        'contract_start', 'contract_end', 'is_active', 'notes',
    ];
    
    protected $casts = [
        'rating' => 'decimal:1',
        'performance_score' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'is_active' => 'boolean',
    ];
    
    public function ratings() { return $this->hasMany(SupplierRating::class); }
    public function contracts() { return $this->hasMany(SupplierContract::class); }
    public function performance() { return $this->hasOne(SupplierPerformance::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'supplier_id'); }
    
    public function calculateRating(): void
    {
        $ratings = $this->ratings;
        if ($ratings->count() > 0) {
            $this->rating = $ratings->avg('rating');
        }
        $this->save();
    }
}
```

---

### Phase 4: Supplier Payment Tracking ⭐⭐⭐⭐⭐
**Timeline:** 2-3 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000008_create_payment_tracking_tables.php
Schema::create('supplier_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('payment_number')->unique();
    $table->date('payment_date');
    $table->string('payment_method');
    $table->decimal('amount', 10, 2);
    $table->string('reference')->nullable();
    $table->string('bank_reference')->nullable();
    $table->string('status')->default('pending'); // pending, completed, cancelled
    $table->text('notes')->nullable();
    
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('supplier_invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('invoice_number')->unique();
    $table->date('invoice_date');
    $table->date('due_date');
    $table->decimal('amount', 10, 2);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    
    $table->string('status')->default('pending'); // pending, partial, paid, overdue
    $table->decimal('paid_amount', 10, 2)->default(0);
    $table->decimal('balance', 10, 2)->default(0);
    
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('payment_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->date('scheduled_date');
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending'); // pending, paid, overdue
    $table->date('paid_date')->nullable();
    $table->string('payment_method')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('reminders_sent')->default(false);
    $table->timestamps();
});

Schema::create('aging_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->date('report_date');
    $table->decimal('period_30', 10, 2)->default(0);
    $table->decimal('period_60', 10, 2)->default(0);
    $table->decimal('period_90', 10, 2)->default(0);
    $table->decimal('period_90_plus', 10, 2)->default(0);
    $table->decimal('total', 10, 2)->default(0);
    $table->timestamp('generated_at')->nullable();
    $table->timestamps();
});
```

---

### Phase 5: Credit/Debit Notes System ⭐⭐⭐⭐
**Timeline:** 2 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000009_create_credit_debit_tables.php
Schema::create('supplier_credits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('credit_number')->unique();
    $table->date('credit_date');
    $table->string('type'); // price_adjustment, quantity_adjustment, tax_adjustment, return
    $table->decimal('amount', 10, 2);
    $table->string('reason');
    $table->string('reference')->nullable();
    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('supplier_debits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('debit_number')->unique();
    $table->date('debit_date');
    $table->string('type'); // price_adjustment, quantity_adjustment, tax_adjustment, charge
    $table->decimal('amount', 10, 2);
    $table->string('reason');
    $table->string('reference')->nullable();
    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('credit_debit_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id');
    $table->string('parent_type'); // credit, debit
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    
    $table->integer('quantity')->default(0);
    $table->decimal('unit_price', 10, 2)->default(0);
    $table->decimal('amount', 10, 2)->default(0);
    $table->string('reason')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

---

### Phase 6: Approval Workflow System ⭐⭐⭐⭐
**Timeline:** 2 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000010_create_approval_workflow_tables.php
Schema::create('approval_workflows', function (Blueprint $table) {
    $table->id();
    $table->string('type'); // purchase_order, payment, credit, debit
    $table->unsignedBigInteger('entity_id');
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->integer('current_step')->default(1);
    $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('approval_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
    
    $table->integer('step_number');
    $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('approver_role')->nullable();
    $table->string('status')->default('pending'); // pending, approved, rejected, skipped
    $table->timestamp('approved_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('approval_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('type');
    $table->string('name');
    $table->text('description')->nullable();
    $table->json('steps_config'); // [{step: 1, approver_id: X, approver_role: Y}]
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

---

### Phase 7: Budget Management ⭐⭐⭐
**Timeline:** 2 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000011_create_budget_tables.php
Schema::create('purchase_budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('period'); // monthly, quarterly, annually
    $table->decimal('budget_amount', 10, 2);
    $table->decimal('spent_amount', 10, 2)->default(0);
    $table->decimal('remaining_amount', 10, 2);
    
    $table->date('start_date');
    $table->date('end_date');
    $table->string('status')->default('active'); // active, completed, exceeded
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('budget_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('purchase_budgets')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('alert_type'); // warning, critical
    $table->decimal('threshold', 5, 2); // percentage
    $table->timestamp('alert_sent_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('budget_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('purchase_budgets')->cascadeOnDelete();
    $table->foreignId('purchase_id')->nullable()->constrained()->purchases')->nullOnDelete();
    
    $table->decimal('amount', 10, 2);
    $table->date('transaction_date');
    $table->string('reference')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

---

### Phase 8: Quality Checks System ⭐⭐⭐⭐
**Timeline:** 2 days

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000012_create_quality_standards_tables.php
Schema::create('quality_standards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    
    $table->decimal('temperature_min', 5, 2)->nullable();
    $table->decimal('temperature_max', 5, 2)->nullable();
    $table->decimal('humidity_min', 5, 2)->nullable();
    $table->decimal('humidity_max', 5, 2)->nullable();
    $table->text('acceptable_defects')->nullable();
    $table->text('criteria')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('quality_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
    
    $table->date('report_date');
    $table->integer('total_checks')->default(0);
    $table->integer('passed')->default(0);
    $table->integer('failed')->default(0);
    $table->decimal('pass_rate', 5, 2)->default(0);
    $table->text('notes')->nullable();
    $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

---

### Phase 9: Advanced Purchase Reports ⭐⭐⭐⭐
**Timeline:** 3 days

#### Report Services:
```php
// File: app/Services/PurchaseReportService.php
class PurchaseReportService
{
    public function getAnalyticsDashboard(int $businessId): array
    {
        return [
            'total_purchases' => Purchase::where('business_id', $businessId)->count(),
            'total_amount' => Purchase::where('business_id', $businessId)->sum('totalAmount'),
            'pending_po' => PurchaseOrder::forBusiness($businessId)->pending()->count(),
            'overdue_po' => PurchaseOrder::forBusiness($businessId)->overdue()->count(),
            'suppliers' => Supplier::where('business_id', $businessId)->count(),
            'pending_payments' => SupplierPayment::where('business_id', $businessId)->where('status', 'pending')->sum('amount'),
        ];
    }
    
    public function getSupplierPerformance(int $businessId): array
    {
        return Supplier::where('business_id', $businessId)
            ->with('performance')
            ->get()
            ->map(function ($supplier) {
                return [
                    'supplier' => $supplier,
                    'rating' => $supplier->rating,
                    'performance' => $supplier->performance,
                    'total_orders' => $supplier->purchaseOrders()->count(),
                ];
            });
    }
    
    public function getPriceComparison(int $businessId, int $productId): array
    {
        return Purchase::where('business_id', $businessId)
            ->whereHas('details', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with('supplier')
            ->get()
            ->map(function ($purchase) {
                return [
                    'purchase' => $purchase,
                    'supplier' => $purchase->party,
                    'price' => $purchase->details->where('product_id', $productId)->first()->purchase_without_tax,
                    'date' => $purchase->purchaseDate,
                ];
            });
    }
}
```

---

### Phase 10: Integration & Documentation ⭐⭐⭐⭐⭐
**Timeline:** 2 days

#### Integration Tasks:
```php
// File: app/Services/PurchaseIntegrationService.php
class PurchaseIntegrationService
{
    // PO to Purchase conversion
    // GRN to Stock update
    // Payment to Purchase linking
    // Supplier data sync
    // Quality to supplier performance
}
```

#### Documentation Files:
```bash
# Create documentation
docs/purchase-management-guide.md
docs/purchase-api-documentation.md
docs/purchase-user-guide.md
docs/purchase-admin-guide.md
docs/purchase-troubleshooting.md
```

---

## 🚀 Quick Start Commands

### Phase 1 Completion:
```bash
# Create API Controller
# Copy from Admin controller and adjust

# Add routes to routes/admin.php and routes/api.php

# Run migration
php artisan migrate

# Create tests
php artisan make:test Feature/PurchaseOrderTest

# Run tests
php artisan test --filter PurchaseOrderTest
```

### Phase 2-10 Implementation:
```bash
# For each phase:
# 1. Create migration
php artisan make:migration create_[system]_tables

# 2. Create models
php artisan make:model ModelName

# 3. Create service
# Create app/Services/SystemService.php

# 4. Create controllers
php artisan make:controller Admin/ControllerName
php artisan make:controller Api/ControllerName

# 5. Create requests
php artisan make:request SystemRequest

# 6. Create resources
php artisan make:resource SystemResource

# 7. Add routes
# Edit routes/admin.php and routes/api.php

# 8. Create tests
php artisan make:test Feature/SystemTest

# 9. Run migrations
php artisan migrate

# 10. Run tests
php artisan test
```

---

## 📊 Implementation Checklist

### Phase 1: Purchase Orders ✅
- [x] Database schema
- [x] Models
- [x] Service
- [x] Request validation
- [x] API resources
- [x] Admin controller
- [ ] API controller
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 2: GRN System
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 3: Supplier Management
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 4: Payment Tracking
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 5: Credit/Debit Notes
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 6: Approval Workflow
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 7: Budget Management
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 8: Quality Checks
- [ ] Database schema
- [ ] Models
- [ ] Service
- [ ] Controllers
- [ ] Routes
- [ ] Views
- [ ] Tests

### Phase 9: Advanced Reports
- [ ] Report service
- [ ] Controllers
- [ ] Dashboard components
- [ ] Export functionality
- [ ] Tests

### Phase 10: Integration
- [ ] Integration service
- [ ] API documentation
- [ ] User guides
- [ ] Admin guides
- [ ] Troubleshooting

---

## 🎯 Implementation Strategy

### Option 1: Sequential Implementation (Recommended)
1. Complete Phase 1 fully (PO system)
2. Move to Phase 2 (GRN)
3. Continue sequentially through all 10 phases
4. Total time: ~14 days

### Option 2: Parallel Implementation
1. Implement multiple phases simultaneously
2. Requires more resources
3. Faster completion but higher risk
4. Total time: ~7-10 days

### Option 3: Priority Implementation
1. Implement Phases 1-4 only (Core systems)
2. Defer Phases 5-10 (Advanced features)
3. Faster ROI
4. Total time: ~8 days

---

## 📝 Notes

### Best Practices:
- Follow clean architecture (Service Layer Pattern)
- Use form requests for validation
- Use resources for API responses
- Write comprehensive tests
- Document all APIs
- Follow Laravel conventions

### Security:
- Business isolation via `business_id`
- RBAC for all operations
- Input validation
- SQL injection prevention
- XSS protection

### Performance:
- Database indexing
- Query optimization
- Caching strategies
- Pagination
- Lazy loading

---

**Guide Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Total Phases:** 10  
**Current Phase:** 1 (80% Complete)  
**Estimated Total Time:** 14 days
