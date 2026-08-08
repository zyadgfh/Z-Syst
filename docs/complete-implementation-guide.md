# Z-Syst Pharmacy - Complete Implementation Guide

## 📋 Executive Summary

This guide provides complete implementation instructions for all remaining systems. Each system includes database schema, models, services, controllers, routes, tests, and integration points.

---

## ✅ Systems Completed (3.5)

### 1. Barcode Printing System - ✅ 100%
- Database, Models, Service, Controllers, Resources, Routes, Tests, Documentation

### 2. Supplier Invoices System - ✅ 100%
- Database, Models, Service, Controllers, Resources, Routes, Tests, Documentation

### 3. Doctor Attention Alerts - ✅ 95%
- Database, Models, Service, Controllers, Resources, Routes, Scheduled Task
- ⏳ WhatsApp integration (optional)
- ⏳ Calling integration (optional)
- ⏳ Tests

### 4. Purchase Orders - ✅ 95%
- Database, Models, Service, Requests, Resources, Controllers, Routes
- ⏳ Views
- ⏳ Tests

---

## ⏳ Remaining Systems (8.5)

### Phase 1 Complete: Purchase Orders (5% remaining)

#### Remaining Tasks:
```bash
# 1. Create Views
# resources/views/admin/purchase-orders/index.blade.php
# resources/views/admin/purchase-orders/show.blade.php
# resources/views/admin/purchase-orders/create.blade.php
# resources/views/admin/purchase-orders/edit.blade.php

# 2. Create Tests
php artisan make:test Feature/PurchaseOrderTest

# 3. Run migration
php artisan migrate
```

---

### Phase 2: GRN (Goods Received Note) System

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000008_create_grn_tables.php
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
    $table->string('status')->default('pending');
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
    $table->string('quality_status')->default('pending');
    $table->text('defects')->nullable();
    $table->integer('damage_quantity')->default(0);
    $table->decimal('temperature')->nullable();
    $table->decimal('humidity')->nullable();
    $table->text('notes')->nullable();
    $table->json('photos')->nullable();
    $table->timestamps();
});
```

#### Models to Create:
```bash
php artisan make:model GoodsReceivedNote
php artisan make:model GRNItem
php artisan make:model QualityCheck
```

#### Service to Create:
```bash
# app/Services/GRNService.php
# Methods: create, verify, updateStock, generateGRNNumber
```

#### Controllers to Create:
```bash
php artisan make:controller Admin/GRNController
php artisan make:controller Api/GRNController
```

#### Implementation Steps:
1. Create migration
2. Create models with relationships
3. Create service with business logic
4. Create controllers with CRUD operations
5. Create resources
6. Add routes to admin.php and api.php
7. Create views
8. Create tests
9. Run migration

---

### Phase 3: Advanced Supplier Management

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000009_create_supplier_management_tables.php
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
    
    $table->decimal('rating', 2, 1)->default(0);
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
    
    $table->decimal('rating', 2, 1);
    $table->string('category');
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
    $table->string('status')->default('active');
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

#### Implementation Steps:
1. Create migration
2. Create models: Supplier, SupplierRating, SupplierContract, SupplierPerformance
3. Create service: SupplierService
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 4: Supplier Payment Tracking

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000010_create_payment_tracking_tables.php
Schema::create('supplier_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('payment_number')->unique();
    $table->date('payment_date');
    $table->string('payment_method');
    $table->string('reference')->nullable();
    $table->string('bank_reference')->nullable();
    
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending');
    $table->text('notes')->nullable();
    
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->string('file_path')->nullable();
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
    
    $table->string('status')->default('pending');
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
    $table->string('status')->default('pending');
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

#### Implementation Steps:
1. Create migration
2. Create models
3. Create service
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 5: Credit/Debit Notes

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000011_create_credit_debit_tables.php
Schema::create('supplier_credits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('credit_number')->unique();
    $table->date('credit_date');
    $table->string('type');
    $table->decimal('amount', 10, 2);
    $table->string('reason');
    $table->string('reference')->nullable();
    $table->string('status')->default('pending');
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
    $table->string('type');
    $table->decimal('amount', 10, 2);
    $table->string('reason');
    $table->string('reference')->nullable();
    $table->string('status')->default('pending');
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
    $table->string('parent_type');
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    
    $table->integer('quantity')->default(0);
    $table->decimal('unit_price', 10, 2)->default(0);
    $table->decimal('amount', 10, 2)->default(0);
    $table->string('reason')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### Implementation Steps:
1. Create migration
2. Create models
3. Create service
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 6: Approval Workflow

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000012_create_approval_workflow_tables.php
Schema::create('approval_workflows', function (Blueprint $table) {
    $table->id();
    $table->string('type');
    $table->unsignedBigInteger('entity_id');
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->integer('current_step')->default(1);
    $table->string('status')->default('pending');
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
    $table->string('status')->default('pending');
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
    $table->json('steps_config');
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

#### Implementation Steps:
1. Create migration
2. Create models
3. Create service
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 7: Budget Management

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000013_create_budget_tables.php
Schema::create('purchase_budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('period');
    $table->decimal('budget_amount', 10, 2);
    $table->decimal('spent_amount', 10, 2)->default(0);
    $table->decimal('remaining_amount', 10, 2);
    
    $table->date('start_date');
    $table->date('end_date');
    $table->string('status')->default('active');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('budget_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('purchase_budgets')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    
    $table->string('alert_type');
    $table->decimal('threshold', 5, 2);
    $table->timestamp('alert_sent_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('budget_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('purchase_budgets')->cascadeOnDelete();
    $table->foreignId('purchase_id')->nullable()->constrained()->purchases()->nullOnDelete();
    
    $table->decimal('amount', 10, 2);
    $table->date('transaction_date');
    $table->string('reference')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### Implementation Steps:
1. Create migration
2. Create models
3. Create service
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 8: Quality Checks

#### Database Schema:
```sql
-- File: database/migrations/2026_08_07_000014_create_quality_standards_tables.php
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

#### Implementation Steps:
1. Create migration
2. Create models
3. Create service
4. Create controllers
5. Create resources
6. Add routes
7. Create views
8. Create tests

---

### Phase 9: Advanced Reports

#### Report Service to Create:
```bash
# app/Services/PurchaseReportService.php
# Methods:
# - getAnalyticsDashboard()
# - getSupplierPerformance()
# - getPriceComparison()
# - getPurchaseTrends()
# - getCostVariance()
# - getAgingReport()
# - getForecastReport()
```

#### Implementation Steps:
1. Create report service
2. Create report controllers
3. Create dashboard components
4. Add routes
5. Create views
6. Add export functionality
7. Create tests

---

### Phase 10: Integration & Documentation

#### Integration Tasks:
1. PO ↔ Purchase integration
2. PO ↔ GRN integration
3. GRN ↔ Stock integration
4. Supplier ↔ All systems
5. Payment ↔ Purchase integration
6. Quality ↔ Supplier performance

#### Documentation to Create:
1. API documentation
2. User guides
3. Admin guides
4. Integration guides
5. Troubleshooting guides

---

## 🚀 Quick Implementation Commands

### For Each System:
```bash
# 1. Create migration
php artisan make:migration create_<system>_tables

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

# 9. Run migration
php artisan migrate

# 10. Run tests
php artisan test
```

---

## 📊 Total Work Remaining

### Files to Create:
- 8 Migrations
- 30+ Models
- 8 Services
- 16 Controllers
- 16 Requests
- 16 Resources
- 40+ Test files
- 20+ Views
- 10+ Documentation files

### Total Lines of Code:
- ~40,000 lines of production code
- ~10,000 lines of test code
- ~5,000 lines of documentation

### Estimated Time:
- Sequential implementation: 4-6 weeks
- Parallel implementation: 2-3 weeks
- Priority implementation (4 systems): 1-2 weeks

---

## 🎯 Recommended Priority Order

### Week 1: Core Purchase Systems
1. ✅ Complete Purchase Orders (Views + Tests)
2. ⏳ GRN System
3. ⏳ Supplier Management

### Week 2: Financial Systems
4. ⏳ Payment Tracking
5. ⏳ Credit/Debit Notes

### Week 3: Process Systems
6. ⏳ Approval Workflow
7. ⏳ Budget Management

### Week 4: Quality & Reports
8. ⏳ Quality Checks
9. ⏳ Advanced Reports

### Week 5-6: Integration
10. ⏳ Integration & Documentation

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
**Total Systems:** 13  
**Completed:** 3.5  
**Remaining:** 9.5  
**Total Work:** ~55,000 lines
