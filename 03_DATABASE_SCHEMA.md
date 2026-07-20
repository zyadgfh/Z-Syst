# 🗄️ مخطط قاعدة البيانات الكامل


> ⚠️ **هذا هو المصدر الوحيد الصحيح (Single Source of Truth) للجداول**
> أي جدول غير موجود هنا = غير مطلوب إنشاؤه

## 📋 الاتفاقيات العامة

### الأعمدة المشتركة (كل جدول يجب أن يحتويها)
```php
$table->id();
$table->foreignId('company_id')->constrained()->cascadeOnDelete();
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamps();
$table->softDeletes();

النوع
الاستخدام
id
Primary keys
foreignId
Foreign keys
string
نص قصير (<255)
text
نص طويل
decimal(15,2)
الأموال
integer
الأعداد الصحيحة
boolean
القيم المنطقية
date
التواريخ فقط
datetime
التاريخ والوقت
json
البيانات المهيكلة
enum
القيم المحددة


📊 الجداول الأساسية (Foundation)

1. companies (موجود - يحتاج للتأكد)

Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('trade_name')->nullable();
    $table->string('tax_number')->nullable()->unique();
    $table->string('logo')->nullable();
    $table->text('address')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('website')->nullable();
    $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
    $table->json('settings')->nullable();
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

2. branches (موجود - يحتاج للتأكد)

Schema::create('branches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('code')->nullable();
    $table->text('address')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('manager_name')->nullable();
    $table->json('working_hours')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('settings')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

3. departments

Schema::create('departments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('code')->nullable();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

4. users (موجود - يحتاج للتأكد من الحقول)


Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
    $table->boolean('two_factor_enabled')->default(false);
    $table->text('two_factor_secret')->nullable();
    $table->json('two_factor_recovery_codes')->nullable();
    $table->rememberToken();
    $table->timestamp('last_login_at')->nullable();
    $table->string('last_login_ip')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

💊 جداول مجال الصيدلية (Pharmacy Domain)

5. categories

Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->string('name');
    $table->string('slug');
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['company_id', 'slug']);
});

6. manufacturers

Schema::create('manufacturers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->text('address')->nullable();
    $table->string('website')->nullable();
    $table->string('logo')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

7. products ⭐ (الأهم)


Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
    
    // المعلومات الأساسية
    $table->string('name');
    $table->string('generic_name')->nullable();
    $table->string('brand_name')->nullable();
    $table->string('barcode')->nullable();
    $table->string('sku')->nullable();
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    
    // المعلومات الصيدلانية
    $table->enum('dosage_form', [
        'tablet', 'capsule', 'syrup', 'injection', 
        'cream', 'ointment', 'drops', 'inhaler',
        'suppository', 'powder', 'other'
    ])->nullable();
    $table->string('strength')->nullable(); // مثل: 500mg
    $table->string('unit_of_measure')->default('piece');
    $table->boolean('prescription_required')->default(false);
    $table->enum('controlled_schedule', ['none', 'schedule_2', 'schedule_3', 'schedule_4', 'schedule_5'])->default('none');
    $table->enum('storage_condition', ['room_temp', 'refrigerated', 'frozen'])->default('room_temp');
    
    // المخزون
    $table->integer('min_stock_level')->default(0);
    $table->integer('reorder_point')->default(0);
    $table->integer('max_stock_level')->default(0);
    
    // التسعير
    $table->decimal('cost_price', 15, 2)->default(0);
    $table->decimal('selling_price', 15, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('discount_percentage', 5, 2)->default(0);
    
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['company_id', 'barcode']);
    $table->index(['company_id', 'name']);
    $table->index(['company_id', 'generic_name']);
});

8. product_variants

Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('pack_size'); // "strip of 10", "bottle 100ml"
    $table->integer('units_per_pack')->default(1);
    $table->decimal('price_override', 15, 2)->nullable();
    $table->string('barcode')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});

9. inventory (Stock per batch)

Schema::create('inventory', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('batch_number');
    $table->integer('quantity')->default(0);
    $table->date('expiry_date');
    $table->date('manufacturing_date')->nullable();
    $table->decimal('cost_price', 15, 2);
    $table->decimal('selling_price', 15, 2);
    $table->string('rack_location')->nullable();
    
    $table->timestamps();
    
    $table->index(['product_id', 'branch_id']);
    $table->index(['branch_id', 'expiry_date']);
    $table->index(['company_id', 'batch_number']);
});

10. stock_movements

Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
    
    $table->enum('movement_type', [
        'in', 'out', 'transfer', 'adjustment', 
        'return', 'damage', 'expired'
    ]);
    $table->integer('quantity');
    $table->integer('quantity_before');
    $table->integer('quantity_after');
    
    $table->nullableMorphs('reference'); // sale_id, purchase_id, etc.
    $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
    
    $table->text('notes')->nullable();
    $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('performed_at')->useCurrent();
    
    $table->timestamps();
    
    $table->index(['product_id', 'branch_id', 'created_at']);
});

11. stock_transfers

Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('transfer_number')->unique();
    $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
    $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
    
    $table->enum('status', ['pending', 'approved', 'in_transit', 'received', 'cancelled'])->default('pending');
    $table->foreignId('requested_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->text('notes')->nullable();
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('received_at')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
});

12. stock_transfer_items

Schema::create('stock_transfer_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
    $table->integer('quantity_requested');
    $table->integer('quantity_sent')->default(0);
    $table->integer('quantity_received')->default(0);
    $table->timestamps();
});

13. suppliers

Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->text('address')->nullable();
    $table->string('tax_id')->nullable();
    $table->integer('payment_terms')->default(0); // days
    $table->decimal('credit_limit', 15, 2)->default(0);
    $table->decimal('balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

14. purchase_orders

Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('po_number')->unique();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    
    $table->enum('status', ['draft', 'sent', 'partial', 'received', 'cancelled'])->default('draft');
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    
    $table->date('expected_delivery_date')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->timestamps();
    $table->softDeletes();
});

15. purchase_order_items

Schema::create('purchase_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->integer('quantity_ordered');
    $table->integer('quantity_received')->default(0);
    $table->decimal('unit_cost', 15, 2);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('total', 15, 2);
    $table->timestamps();
});

16. goods_received_notes

Schema::create('goods_received_notes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('grn_number')->unique();
    $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('received_by')->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
});

17. sales

Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('invoice_number');
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users'); // cashier
    
    $table->decimal('subtotal', 15, 2);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->enum('discount_type', ['percentage', 'fixed', 'coupon'])->default('fixed');
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('total_amount', 15, 2);
    $table->decimal('amount_paid', 15, 2);
    $table->decimal('change_amount', 15, 2)->default(0);
    
    $table->enum('payment_method', ['cash', 'card', 'insurance', 'mixed'])->default('cash');
    $table->enum('payment_status', ['paid', 'partial', 'pending', 'refunded'])->default('paid');
    $table->enum('sale_type', ['walk_in', 'prescription', 'insurance'])->default('walk_in');
    
    $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('insurance_claim_id')->nullable()->constrained()->nullOnDelete();
    
    $table->text('notes')->nullable();
    $table->boolean('is_voided')->default(false);
    $table->text('void_reason')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['branch_id', 'invoice_number']);
    $table->index(['company_id', 'created_at']);
    $table->index(['customer_id', 'created_at']);
});

18. sale_items

Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
    $table->string('batch_number')->nullable();
    $table->integer('quantity');
    $table->decimal('unit_price', 15, 2);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('tax', 15, 2)->default(0);
    $table->decimal('total', 15, 2);
    $table->text('instructions')->nullable();
    $table->timestamps();
});

19. sale_returns

Schema::create('sale_returns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->string('return_number')->unique();
    $table->text('reason');
    $table->decimal('total_amount', 15, 2);
    $table->foreignId('returned_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
    $table->timestamps();
});

20. customers (patients)

Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->date('date_of_birth')->nullable();
    $table->enum('gender', ['male', 'female', 'other'])->nullable();
    $table->string('blood_group')->nullable();
    $table->json('allergies')->nullable();
    $table->json('medical_history')->nullable();
    $table->string('national_id')->nullable();
    $table->integer('loyalty_points')->default(0);
    $table->decimal('credit_limit', 15, 2)->default(0);
    $table->decimal('credit_balance', 15, 2)->default(0);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['company_id', 'phone']);
    $table->index(['company_id', 'name']);
});

21. doctors

Schema::create('doctors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('specialization')->nullable();
    $table->string('license_number')->nullable();
    $table->string('clinic_name')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

22. prescriptions

Schema::create('prescriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('prescription_number')->unique();
    $table->foreignId('patient_id')->constrained('customers')->cascadeOnDelete();
    $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    
    $table->enum('status', ['pending', 'dispensed', 'partially_dispensed', 'cancelled', 'expired'])->default('pending');
    $table->date('prescribed_date');
    $table->date('expiry_date')->nullable();
    $table->text('notes')->nullable();
    $table->string('image_path')->nullable();
    $table->integer('refill_count')->default(0);
    $table->integer('max_refills')->default(0);
    
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

23. prescription_items

Schema::create('prescription_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('dosage')->nullable();
    $table->string('frequency')->nullable();
    $table->string('duration')->nullable();
    $table->integer('quantity');
    $table->integer('dispensed_quantity')->default(0);
    $table->text('instructions')->nullable();
    $table->boolean('substitution_allowed')->default(true);
    $table->timestamps();
});

24. insurance_companies

Schema::create('insurance_companies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('contract_terms')->nullable();
    $table->decimal('discount_percentage', 5, 2)->default(0);
    $table->integer('payment_terms')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

25. insurance_claims

Schema::create('insurance_claims', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('claim_number')->unique();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('patient_id')->constrained('customers')->cascadeOnDelete();
    $table->decimal('amount_claimed', 15, 2);
    $table->decimal('amount_approved', 15, 2)->nullable();
    $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
    $table->text('rejection_reason')->nullable();
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
});

26. expenses

Schema::create('expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
    $table->decimal('amount', 15, 2);
    $table->text('description');
    $table->string('receipt_path')->nullable();
    $table->date('expense_date');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->timestamps();
});

27. expense_categories

Schema::create('expense_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});

28. cash_registers

Schema::create('cash_registers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users');
    $table->decimal('opening_balance', 15, 2);
    $table->decimal('closing_balance', 15, 2)->nullable();
    $table->decimal('total_sales', 15, 2)->default(0);
    $table->decimal('total_returns', 15, 2)->default(0);
    $table->decimal('total_expenses', 15, 2)->default(0);
    $table->enum('status', ['open', 'closed'])->default('open');
    $table->timestamp('opened_at')->useCurrent();
    $table->timestamp('closed_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

29. settings

Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->string('group')->default('general');
    $table->string('key');
    $table->text('value')->nullable();
    $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
    $table->timestamps();
    
    $table->unique(['company_id', 'branch_id', 'key']);
});

30. notifications

Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->morphs('notifiable');
    $table->string('type');
    $table->text('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});

🔗 العلاقات (Relationships)

كل نموذج يجب أن يُعرّف علاقاته بوضوح:

// Product.php
public function company() { return $this->belongsTo(Company::class); }
public function category() { return $this->belongsTo(Category::class); }
public function manufacturer() { return $this->belongsTo(Manufacturer::class); }
public function inventory() { return $this->hasMany(Inventory::class); }
public function stockMovements() { return $this->hasMany(StockMovement::class); }
public function variants() { return $this->hasMany(ProductVariant::class); }

⚠️ قواعد هامة
كل جدول له company_id (عدا الجداول الأساسية مثل companies نفسها)
Soft deletes على كل الجداول المهمة
Foreign keys مع cascadeOnDelete أو nullOnDelete حسب الحاجة
Indexes على الأعمدة المستخدمة في البحث والـ where
Unique constraints حيث يلزم
