# 🎭 الدور (Role)

أنت **مهندس قواعد بيانات أول (Senior Database Architect)** متخصص في:
- **MySQL / MariaDB** (بيئة Laragon على Windows)
- **Laravel 11+ Migrations** مع أفضل الممارسات
- **أنظمة SaaS متعددة المستأجرين (Multi-Tenant)**
- **أنظمة إدارة الصيدليات (Pharmacy Management Systems)**
- **تحسين الأداء (Query Optimization, Indexing, Partitioning)**
- **تصميم قواعد بيانات قابلة للتوسع (Scalable Schema Design)**

---

# 📋 سياق المشروع (Context)

**اسم المشروع**: Z-Syst Pharmacy Management SaaS
**اسم قاعدة البيانات**: `pharmacy_db`
**بيئة العمل**: Laragon (Windows) — MySQL 8.x / MariaDB 10.x
**الإطار**: Laravel 11+ (PHP 8.3+)
**الحالة الحالية**: قاعدة البيانات فارغة تقريباً — فقط بنية تحتية أساسية (RBAC, Multi-tenant scaffolding)
**الهدف**: بناء قاعدة بيانات كاملة وجاهزة للإنتاج لنظام SaaS لإدارة الصيدليات يدعم السوق المصري/الشرق أوسطي (MENA)

---

# 🖥️ البيئة التقنية (Technical Environment)

## Laragon specifics:
- **MySQL / MariaDB** (الافتراضي في Laragon)
- **Character Set**: `utf8mb4` + Collation: `utf8mb4_unicode_ci` (دعم كامل للعربية)
- **Engine**: InnoDB (للدعم Foreign Keys + Transactions)
- **Port**: 3306 (افتراضي)
- **User**: `root` بدون كلمة مرور (بيئة تطوير محلية)
- **phpMyAdmin**: متاح على `http://localhost/phpmyadmin`

## Laravel specifics:
- **Migrations** لكل جدول (ملف منفصل)
- **Models** مع العلاقات (Eloquent)
- **Factories + Seeders** للبيانات التجريبية
- **Database Transactions** للعمليات المعقدة
- **Soft Deletes** حيث يلزم
- **Timestamps** (`created_at`, `updated_at`) على كل الجداول
- **`company_id`** على كل الجداول الخاصة بالمستأجر (Tenant Isolation)

---

# 🎯 المهمة (Mission)

صمم و**نفّذ** قاعدة البيانات الكاملة `pharmacy_db` عبر **Laravel Migrations** تغطي:

1. ✅ كل الجداول الأساسية (Core Tables)
2. ✅ كل جداول مجال الصيدلية (Pharmacy Domain)
3. ✅ العلاقات (Foreign Keys) مع `ON DELETE CASCADE/RESTRICT` المناسبة
4. ✅ الفهارس (Indexes) للأداء
5. ✅ Constraints (Unique, Check, Default values)
6. ✅ Seeders لبيانات تجريبية واقعية
7. ✅ Views للتقارير (اختياري لكن مفضل)
8. ✅ Stored Procedures للعمليات المعقدة (اختياري)

---

# 📦 قائمة الجداول المطلوبة (بالترتيب)

## 🔴 المجموعة 1 — الأساس (Foundation) — يجب بناؤها أولاً

### 1.1 `companies` (إصلاح/إنشاء)
```
- id (bigint, PK, auto-increment)
- name (varchar 255)
- trade_name (varchar 255, nullable)
- tax_number (varchar 50, nullable, unique)
- commercial_register (varchar 100, nullable)
- email (varchar 255, nullable)
- phone (varchar 50, nullable)
- address (text, nullable)
- logo (varchar 500, nullable)
- subscription_plan_id (FK → subscription_plans.id, nullable)
- subscription_status (enum: trial, active, suspended, cancelled, default: trial)
- subscription_expires_at (timestamp, nullable)
- max_branches (int, default: 1)
- max_users (int, default: 5)
- max_products (int, default: 1000)
- is_active (boolean, default: true)
- settings (JSON, nullable) — إعدادات خاصة بالشركة
- created_at, updated_at, deleted_at (soft delete)
```

### 1.2 `branches`
```
- id (bigint, PK)
- company_id (FK → companies.id, index)
- name (varchar 255)
- code (varchar 50, unique per company)
- phone (varchar 50, nullable)
- email (varchar 255, nullable)
- address (text, nullable)
- city (varchar 100, nullable)
- country (varchar 100, default: 'Egypt')
- latitude (decimal 10,8, nullable)
- longitude (decimal 11,8, nullable)
- working_hours (JSON, nullable) — {"sat":"9-22", ...}
- is_main (boolean, default: false)
- is_active (boolean, default: true)
- created_at, updated_at, deleted_at
```

### 1.3 `departments`
```
- id (bigint, PK)
- company_id (FK)
- branch_id (FK, nullable — null = على مستوى الشركة)
- name (varchar 255)
- code (varchar 50)
- description (text, nullable)
- parent_id (FK → departments.id, nullable — hierarchical)
- is_active (boolean, default: true)
- timestamps
```

### 1.4 `users` (إصلاح/إكمال)
```
- id (bigint, PK)
- company_id (FK → companies.id, index)
- branch_id (FK → branches.id, nullable)
- department_id (FK → departments.id, nullable)
- name (varchar 255)
- email (varchar 255, unique per company)
- phone (varchar 50, nullable)
- password (varchar 255)
- avatar (varchar 500, nullable)
- national_id (varchar 50, nullable)
- pharmacy_license_number (varchar 100, nullable) — للصيادلة
- role (enum: super_admin, company_admin, branch_manager, pharmacist, cashier, accountant, stock_keeper)
- is_active (boolean, default: true)
- email_verified_at (timestamp, nullable)
- two_factor_secret (text, nullable)
- two_factor_recovery_codes (text, nullable)
- last_login_at (timestamp, nullable)
- last_login_ip (varchar 45, nullable)
- preferences (JSON, nullable) — {language: 'ar', theme: 'dark'}
- remember_token
- timestamps, soft_deletes
```

---

## 🔴 المجموعة 2 — مجال الصيدلية الأساسي (Core Pharmacy Domain)

### 2.1 `categories`
```
- id, company_id (FK)
- name (varchar 255)
- slug (varchar 255, unique per company)
- parent_id (FK → categories.id, nullable — hierarchical)
- description (text, nullable)
- image (varchar 500, nullable)
- icon (varchar 100, nullable)
- sort_order (int, default: 0)
- is_active (boolean, default: true)
- timestamps, soft_deletes
- Index: [company_id, parent_id]
```

### 2.2 `manufacturers`
```
- id, company_id (FK)
- name (varchar 255)
- generic_name (varchar 255, nullable) — الاسم العلمي للشركة
- contact_person (varchar 255, nullable)
- email (varchar 255, nullable)
- phone (varchar 50, nullable)
- address (text, nullable)
- website (varchar 255, nullable)
- logo (varchar 500, nullable)
- tax_id (varchar 100, nullable)
- country (varchar 100, nullable)
- is_active (boolean, default: true)
- timestamps, soft_deletes
```

### 2.3 `products` ⭐ (الأهم)
```
- id (bigint, PK)
- company_id (FK, index)
- category_id (FK → categories.id, nullable)
- manufacturer_id (FK → manufacturers.id, nullable)
- name (varchar 255) — الاسم التجاري
- generic_name (varchar 255, nullable) — الاسم العلمي
- brand_name (varchar 255, nullable)
- barcode (varchar 100, nullable, index) — EAN-13, UPC, Code128
- sku (varchar 100, nullable, unique per company)
- description (text, nullable)
- dosage_form (enum: tablet, capsule, syrup, injection, cream, ointment, drops, inhaler, suppository, powder, spray, patch, other)
- strength (varchar 100, nullable) — مثل "500mg"
- strength_unit (varchar 50, nullable) — mg, ml, g, mcg
- unit_of_measure (enum: piece, strip, box, bottle, tube, vial, ampoule, sachet)
- pack_size (int, default: 1) — عدد الوحدات في العبوة
- prescription_required (boolean, default: false)
- controlled_substance (boolean, default: false)
- controlled_schedule (enum: table_1, table_2, table_3, table_4, not_controlled, default: not_controlled)
- narcotic (boolean, default: false)
- psychotropic (boolean, default: false)
- antibiotic (boolean, default: false)
- storage_conditions (enum: room_temperature, refrigerated, frozen, protected_from_light, other, default: room_temperature)
- storage_notes (text, nullable)
- min_stock_level (int, default: 0)
- reorder_point (int, default: 0)
- max_stock_level (int, default: 0)
- cost_price (decimal 12,3, default: 0)
- selling_price (decimal 12,3, default: 0)
- wholesale_price (decimal 12,3, nullable)
- tax_rate (decimal 5,2, default: 14.00) — نسبة الضريبة
- discount_percentage (decimal 5,2, default: 0)
- image (varchar 500, nullable)
- is_active (boolean, default: true)
- is_featured (boolean, default: false)
- created_by (FK → users.id, nullable)
- updated_by (FK → users.id, nullable)
- timestamps, soft_deletes
- Indexes: [company_id, barcode], [company_id, generic_name], [company_id, category_id], [company_id, name]
- Full-text index on [name, generic_name, brand_name]
```

### 2.4 `product_variants`
```
- id, product_id (FK)
- variant_name (varchar 255) — "Strip of 10 tablets"
- barcode (varchar 100, nullable, unique)
- sku (varchar 100, nullable)
- pack_size (int, default: 1)
- cost_price (decimal 12,3)
- selling_price (decimal 12,3)
- is_default (boolean, default: false)
- is_active (boolean, default: true)
- timestamps
```

### 2.5 `product_alternatives` (للبدائل الدوائية)
```
- id
- product_id (FK)
- alternative_product_id (FK → products.id)
- company_id (FK)
- substitution_type (enum: generic, therapeutic, brand)
- is_active (boolean, default: true)
- timestamps
- Unique: [product_id, alternative_product_id]
```

### 2.6 `drug_interactions` (تفاعلات الأدوية)
```
- id, company_id (FK)
- drug_a_id (FK → products.id)
- drug_b_id (FK → products.id)
- severity (enum: mild, moderate, severe, contraindicated)
- description (text)
- clinical_management (text, nullable)
- source (varchar 255, nullable)
- is_active (boolean, default: true)
- timestamps
- Unique: [drug_a_id, drug_b_id] (مع check drug_a_id < drug_b_id لمنع التكرار)
```

### 2.7 `suppliers`
```
- id, company_id (FK)
- name (varchar 255)
- code (varchar 50, unique per company)
- contact_person (varchar 255, nullable)
- email (varchar 255, nullable)
- phone (varchar 50, nullable)
- phone_secondary (varchar 50, nullable)
- address (text, nullable)
- tax_id (varchar 100, nullable)
- payment_terms (int, default: 0) — أيام الائتمان
- credit_limit (decimal 12,3, default: 0)
- current_balance (decimal 12,3, default: 0)
- default_payment_method (enum: cash, check, bank_transfer, credit, default: cash)
- bank_name (varchar 255, nullable)
- bank_account (varchar 100, nullable)
- notes (text, nullable)
- is_active (boolean, default: true)
- timestamps, soft_deletes
```

### 2.8 `customers` / `patients`
```
- id, company_id (FK)
- code (varchar 50, unique per company)
- name (varchar 255)
- national_id (varchar 50, nullable) — الرقم القومي
- phone (varchar 50, index)
- phone_secondary (varchar 50, nullable)
- email (varchar 255, nullable)
- date_of_birth (date, nullable)
- gender (enum: male, female, unknown, default: unknown)
- blood_group (enum: A+, A-, B+, B-, AB+, AB-, O+, O-, unknown, default: unknown)
- address (text, nullable)
- city (varchar 100, nullable)
- allergies (JSON, nullable) — [{allergen: "Penicillin", severity: "severe"}]
- chronic_diseases (JSON, nullable) — ["Diabetes", "Hypertension"]
- medical_history (text, nullable)
- insurance_id (FK → insurance_companies.id, nullable)
- insurance_number (varchar 100, nullable)
- insurance_category (varchar 100, nullable)
- loyalty_points (int, default: 0)
- total_purchases (decimal 12,3, default: 0)
- credit_limit (decimal 12,3, default: 0)
- credit_balance (decimal 12,3, default: 0)
- notes (text, nullable)
- is_active (boolean, default: true)
- is_vip (boolean, default: false)
- created_by (FK → users.id, nullable)
- timestamps, soft_deletes
- Index: [phone], [national_id], [insurance_number]
```

### 2.9 `doctors`
```
- id, company_id (FK)
- name (varchar 255)
- specialization (varchar 255, nullable)
- license_number (varchar 100, nullable) — نقابة الأطباء
- clinic_name (varchar 255, nullable)
- phone (varchar 50, nullable)
- email (varchar 255, nullable)
- address (text, nullable)
- signature_image (varchar 500, nullable)
- stamp_image (varchar 500, nullable)
- is_active (boolean, default: true)
- timestamps, soft_deletes
```

---

## 🔴 المجموعة 3 — المخزون والمشتريات (Inventory & Purchasing)

### 3.1 `inventory` / `stock` ⭐
```
- id (bigint, PK)
- company_id (FK, index)
- branch_id (FK, index)
- product_id (FK → products.id, index)
- product_variant_id (FK → product_variants.id, nullable)
- batch_number (varchar 100, index)
- expiry_date (date, index) — مهم جداً للصيدليات
- manufacturing_date (date, nullable)
- quantity (decimal 12,3, default: 0)
- quantity_reserved (decimal 12,3, default: 0) — محجوز لطلبات
- quantity_damaged (decimal 12,3, default: 0)
- cost_price (decimal 12,3)
- selling_price (decimal 12,3)
- supplier_id (FK → suppliers.id, nullable)
- purchase_order_id (FK, nullable)
- rack_location (varchar 100, nullable) — موقع الرف
- shelf_number (varchar 50, nullable)
- is_quarantined (boolean, default: false) — معزول للفحص
- notes (text, nullable)
- timestamps
- Unique: [branch_id, product_id, batch_number]
- Index: [company_id, expiry_date] — لتنبيهات الانتهاء
```

### 3.2 `stock_movements` ⭐ (مسار تدقيق المخزون)
```
- id (bigint, PK)
- company_id (FK)
- branch_id (FK)
- product_id (FK)
- batch_id (FK → inventory.id, nullable)
- movement_type (enum: purchase_in, sale_out, return_in, return_out, transfer_in, transfer_out, adjustment_in, adjustment_out, damage, expired, opening_balance, stock_take)
- quantity (decimal 12,3) — موجب أو سالب حسب النوع
- quantity_before (decimal 12,3)
- quantity_after (decimal 12,3)
- cost_price (decimal 12,3, nullable)
- reference_type (varchar 100, nullable) — "App\Models\Sale"
- reference_id (bigint, nullable)
- from_branch_id (FK → branches.id, nullable)
- to_branch_id (FK → branches.id, nullable)
- performed_by (FK → users.id)
- approved_by (FK → users.id, nullable)
- notes (text, nullable)
- performed_at (timestamp)
- timestamps
- Index: [company_id, product_id, movement_type], [company_id, performed_at]
```

### 3.3 `stock_adjustments`
```
- id, company_id, branch_id
- adjustment_number (varchar 50, unique per branch)
- adjustment_type (enum: increase, decrease)
- status (enum: draft, pending, approved, rejected, completed, default: draft)
- reason (text)
- total_items (int, default: 0)
- requested_by (FK → users.id)
- approved_by (FK → users.id, nullable)
- approved_at (timestamp, nullable)
- notes (text, nullable)
- timestamps, soft_deletes
```

### 3.4 `stock_adjustment_items`
```
- id, stock_adjustment_id (FK)
- product_id (FK)
- batch_id (FK → inventory.id, nullable)
- quantity_before (decimal 12,3)
- quantity_after (decimal 12,3)
- difference (decimal 12,3) — computed
- reason (text, nullable)
- timestamps
```

### 3.5 `stock_transfers`
```
- id, company_id
- transfer_number (varchar 50, unique)
- from_branch_id (FK → branches.id)
- to_branch_id (FK → branches.id)
- status (enum: draft, pending, approved, in_transit, received, completed, cancelled, default: draft)
- requested_by (FK → users.id)
- approved_by (FK → users.id, nullable)
- shipped_at (timestamp, nullable)
- received_at (timestamp, nullable)
- notes (text, nullable)
- timestamps, soft_deletes
```

### 3.6 `stock_transfer_items`
```
- id, stock_transfer_id (FK)
- product_id (FK)
- batch_id (FK → inventory.id, nullable)
- quantity_requested (decimal 12,3)
- quantity_sent (decimal 12,3, default: 0)
- quantity_received (decimal 12,3, default: 0)
- cost_price (decimal 12,3)
- notes (text, nullable)
- timestamps
```

### 3.7 `purchase_orders`
```
- id, company_id, branch_id, supplier_id (FK)
- po_number (varchar 50, unique per company)
- status (enum: draft, pending_approval, approved, sent, partially_received, received, cancelled, default: draft)
- order_date (date)
- expected_delivery_date (date, nullable)
- subtotal (decimal 12,3, default: 0)
- discount_amount (decimal 12,3, default: 0)
- discount_type (enum: percentage, fixed, default: fixed)
- tax_amount (decimal 12,3, default: 0)
- shipping_cost (decimal 12,3, default: 0)
- total_amount (decimal 12,3, default: 0)
- paid_amount (decimal 12,3, default: 0)
- payment_status (enum: unpaid, partial, paid, default: unpaid)
- payment_method (enum: cash, check, bank_transfer, credit, default: cash)
- notes (text, nullable)
- created_by (FK → users.id)
- approved_by (FK → users.id, nullable)
- approved_at (timestamp, nullable)
- timestamps, soft_deletes
- Index: [company_id, status], [supplier_id, order_date]
```

### 3.8 `purchase_order_items`
```
- id, purchase_order_id (FK)
- product_id (FK)
- product_variant_id (FK, nullable)
- quantity_ordered (decimal 12,3)
- quantity_received (decimal 12,3, default: 0)
- unit_cost (decimal 12,3)
- discount (decimal 12,3, default: 0)
- tax_rate (decimal 5,2, default: 0)
- tax_amount (decimal 12,3, default: 0)
- total (decimal 12,3, default: 0)
- notes (text, nullable)
- timestamps
```

### 3.9 `goods_received_notes` (GRN)
```
- id, company_id, branch_id
- grn_number (varchar 50, unique)
- purchase_order_id (FK)
- supplier_id (FK)
- received_date (date)
- received_by (FK → users.id)
- invoice_number (varchar 100, nullable) — فاتورة المورد
- invoice_date (date, nullable)
- subtotal, discount, tax, total (decimal 12,3)
- notes (text, nullable)
- timestamps, soft_deletes
```

### 3.10 `grn_items`
```
- id, grn_id (FK)
- purchase_order_item_id (FK, nullable)
- product_id (FK)
- batch_number (varchar 100)
- expiry_date (date)
- manufacturing_date (date, nullable)
- quantity_received (decimal 12,3)
- cost_price (decimal 12,3)
- selling_price (decimal 12,3)
- rack_location (varchar 100, nullable)
- notes (text, nullable)
- timestamps
```

---

## 🔴 المجموعة 4 — المبيعات ونقطة البيع (Sales & POS)

### 4.1 `sales` ⭐
```
- id (bigint, PK)
- company_id (FK, index)
- branch_id (FK, index)
- invoice_number (varchar 50, unique per branch)
- customer_id (FK → customers.id, nullable) — walk-in = null
- doctor_id (FK → doctors.id, nullable)
- prescription_id (FK → prescriptions.id, nullable)
- insurance_claim_id (FK, nullable)
- cashier_id (FK → users.id)
- sale_type (enum: walk_in, prescription, insurance, wholesale, default: walk_in)
- sale_date (timestamp)
- subtotal (decimal 12,3, default: 0)
- discount_amount (decimal 12,3, default: 0)
- discount_type (enum: percentage, fixed, default: fixed)
- discount_reason (varchar 255, nullable)
- tax_amount (decimal 12,3, default: 0)
- total_amount (decimal 12,3, default: 0)
- amount_paid (decimal 12,3, default: 0)
- change_amount (decimal 12,3, default: 0)
- payment_method (enum: cash, card, insurance, mixed, credit, mobile_wallet, default: cash)
- payment_status (enum: paid, partial, pending, refunded, default: paid)
- insurance_amount (decimal 12,3, default: 0)
- patient_share (decimal 12,3, default: 0)
- items_count (int, default: 0)
- notes (text, nullable)
- is_voided (boolean, default: false)
- void_reason (text, nullable)
- voided_by (FK → users.id, nullable)
- voided_at (timestamp, nullable)
- created_at, updated_at
- Index: [company_id, sale_date], [branch_id, invoice_number], [customer_id], [cashier_id]
```

### 4.2 `sale_items`
```
- id, sale_id (FK)
- product_id (FK)
- product_variant_id (FK, nullable)
- batch_id (FK → inventory.id)
- batch_number (varchar 100)
- expiry_date (date)
- quantity (decimal 12,3)
- unit_price (decimal 12,3)
- cost_price (decimal 12,3) — لحساب الربح
- discount (decimal 12,3, default: 0)
- discount_type (enum: percentage, fixed, default: fixed)
- tax_rate (decimal 5,2, default: 14)
- tax_amount (decimal 12,3, default: 0)
- total (decimal 12,3, default: 0)
- prescription_item_id (FK, nullable)
- notes (text, nullable)
- timestamps
```

### 4.3 `sale_payments` (للدفع المختلط)
```
- id, sale_id (FK)
- payment_method (enum: cash, card, insurance, credit, mobile_wallet)
- amount (decimal 12,3)
- reference_number (varchar 100, nullable) — رقم العملية/البطاقة
- card_last_four (varchar 4, nullable)
- notes (text, nullable)
- timestamps
```

### 4.4 `sale_returns`
```
- id, company_id, branch_id
- return_number (varchar 50, unique)
- sale_id (FK)
- customer_id (FK, nullable)
- return_type (enum: customer_return, supplier_return, damaged, expired, default: customer_return)
- return_date (timestamp)
- reason (text)
- subtotal, discount, tax, total (decimal 12,3)
- refund_method (enum: cash, credit_note, original_payment, default: original_payment)
- refund_status (enum: pending, processed, rejected, default: pending)
- approved_by (FK → users.id, nullable)
- processed_by (FK → users.id)
- notes (text, nullable)
- timestamps, soft_deletes
```

### 4.5 `sale_return_items`
```
- id, sale_return_id (FK)
- sale_item_id (FK, nullable)
- product_id (FK)
- batch_id (FK → inventory.id, nullable)
- quantity (decimal 12,3)
- unit_price (decimal 12,3)
- total (decimal 12,3)
- condition (enum: good, damaged, expired, default: good)
- restocked (boolean, default: false)
- notes (text, nullable)
- timestamps
```

### 4.6 `cash_registers`
```
- id, company_id, branch_id
- user_id (FK → users.id) — الكاشير
- register_number (varchar 50)
- shift_number (int)
- opening_balance (decimal 12,3, default: 0)
- closing_balance (decimal 12,3, nullable)
- total_sales (decimal 12,3, default: 0)
- total_returns (decimal 12,3, default: 0)
- total_expenses (decimal 12,3, default: 0)
- total_cash_in (decimal 12,3, default: 0)
- total_cash_out (decimal 12,3, default: 0)
- expected_balance (decimal 12,3, default: 0)
- actual_balance (decimal 12,3, nullable)
- difference (decimal 12,3, nullable)
- status (enum: open, closed, suspended, default: open)
- opened_at (timestamp)
- closed_at (timestamp, nullable)
- notes (text, nullable)
- timestamps
```

### 4.7 `held_sales` (المبيعات المعلقة)
```
- id, company_id, branch_id
- hold_number (varchar 50)
- user_id (FK → users.id)
- customer_id (FK, nullable)
- items (JSON) — تفاصيل السلة
- subtotal, total (decimal 12,3)
- notes (text, nullable)
- expires_at (timestamp)
- timestamps
```

---

## 🔴 المجموعة 5 — الوصفات الطبية (Prescriptions)

### 5.1 `prescriptions` ⭐
```
- id, company_id, branch_id
- prescription_number (varchar 50, unique per branch)
- patient_id (FK → customers.id)
- doctor_id (FK → doctors.id)
- status (enum: pending, partially_dispensed, dispensed, cancelled, expired, default: pending)
- prescription_date (date)
- expiry_date (date) — صلاحية الوصفة
- priority (enum: normal, urgent, default: normal)
- diagnosis (text, nullable)
- notes (text, nullable)
- image_path (varchar 500, nullable) — صورة الوصفة
- digital_signature (text, nullable)
- received_by (FK → users.id, nullable)
- dispensed_by (FK → users.id, nullable)
- dispensed_at (timestamp, nullable)
- timestamps, soft_deletes
- Index: [patient_id, prescription_date], [doctor_id]
```

### 5.2 `prescription_items`
```
- id, prescription_id (FK)
- product_id (FK)
- product_variant_id (FK, nullable)
- dosage (varchar 100) — "1 tablet"
- frequency (varchar 100) — "3 times daily"
- duration (varchar 100) — "7 days"
- instructions (text, nullable) — "after meals"
- quantity_prescribed (decimal 12,3)
- quantity_dispensed (decimal 12,3, default: 0)
- substitution_allowed (boolean, default: true)
- substitution_made (boolean, default: false)
- substituted_product_id (FK → products.id, nullable)
- refill_number (int, default: 0)
- max_refills (int, default: 0)
- status (enum: pending, partially_dispensed, dispensed, cancelled, default: pending)
- notes (text, nullable)
- timestamps
```

### 5.3 `controlled_substance_log` ⭐ (سجل المواد الخاضعة للرقابة — حرج للامتثال)
```
- id, company_id, branch_id
- log_number (varchar 50, unique)
- product_id (FK)
- patient_id (FK)
- doctor_id (FK)
- prescription_id (FK, nullable)
- transaction_type (enum: received, dispensed, returned, damaged, destroyed, transferred)
- quantity (decimal 12,3)
- batch_number (varchar 100)
- performed_by (FK → users.id)
- authorized_by (FK → users.id, nullable)
- patient_national_id (varchar 50) — مطلوب للرقابة
- doctor_license_number (varchar 100)
- notes (text, nullable)
- performed_at (timestamp)
- timestamps
- Index: [product_id, performed_at], [patient_id], [doctor_id]
```

---

## 🔴 المجموعة 6 — التأمين (Insurance)

### 6.1 `insurance_companies`
```
- id, company_id
- name (varchar 255)
- code (varchar 50)
- contact_person (varchar 255, nullable)
- phone (varchar 50, nullable)
- email (varchar 255, nullable)
- address (text, nullable)
- contract_number (varchar 100, nullable)
- contract_start (date, nullable)
- contract_end (date, nullable)
- discount_percentage (decimal 5,2, default: 0)
- payment_terms (int, default: 30)
- is_active (boolean, default: true)
- timestamps, soft_deletes
```

### 6.2 `insurance_plans`
```
- id, insurance_company_id (FK)
- company_id (FK)
- plan_name (varchar 255)
- plan_code (varchar 50)
- coverage_percentage (decimal 5,2) — نسبة التغطية
- max_annual_coverage (decimal 12,3, nullable)
- deductible (decimal 12,3, default: 0)
- copay_percentage (decimal 5,2, default: 0)
- covers_generic_only (boolean, default: false)
- excluded_categories (JSON, nullable)
- is_active (boolean, default: true)
- timestamps
```

### 6.3 `insurance_claims`
```
- id, company_id
- claim_number (varchar 50, unique)
- sale_id (FK)
- insurance_company_id (FK)
- insurance_plan_id (FK, nullable)
- patient_id (FK)
- claim_date (date)
- amount_claimed (decimal 12,3)
- amount_approved (decimal 12,3, nullable)
- amount_paid (decimal 12,3, default: 0)
- rejection_reason (text, nullable)
- status (enum: draft, submitted, under_review, approved, partially_approved, rejected, paid, cancelled, default: draft)
- submitted_at (timestamp, nullable)
- resolved_at (timestamp, nullable)
- paid_at (timestamp, nullable)
- notes (text, nullable)
- timestamps, soft_deletes
```

---

## 🔴 المجموعة 7 — المالية (Financial)

### 7.1 `expenses`
```
- id, company_id, branch_id
- expense_number (varchar 50, unique)
- category_id (FK → expense_categories.id)
- amount (decimal 12,3)
- description (text)
- receipt_path (varchar 500, nullable)
- expense_date (date)
- paid_by (FK → users.id)
- approved_by (FK → users.id, nullable)
- payment_method (enum: cash, bank_transfer, credit_card, default: cash)
- status (enum: pending, approved, paid, rejected, default: pending)
- notes (text, nullable)
- timestamps, soft_deletes
```

### 7.2 `expense_categories`
```
- id, company_id
- name (varchar 255)
- description (text, nullable)
- is_active (boolean, default: true)
- timestamps
```

### 7.3 `payment_records` (Polymorphic)
```
- id, company_id
- payable_type (varchar 100) — "App\Models\PurchaseOrder"
- payable_id (bigint)
- payment_type (enum: payment, receipt)
- amount (decimal 12,3)
- payment_method (enum: cash, check, bank_transfer, credit_card)
- reference_number (varchar 100, nullable)
- payment_date (date)
- notes (text, nullable)
- recorded_by (FK → users.id)
- timestamps
- Index: [payable_type, payable_id]
```

### 7.4 `accounts` (لل محاسبة بسيطة)
```
- id, company_id
- account_code (varchar 50, unique per company)
- name (varchar 255)
- account_type (enum: asset, liability, equity, revenue, expense)
- parent_id (FK → accounts.id, nullable)
- balance (decimal 12,3, default: 0)
- is_active (boolean, default: true)
- timestamps
```

### 7.5 `journal_entries` (القيد المحاسبي)
```
- id, company_id
- entry_number (varchar 50, unique)
- entry_date (date)
- description (text)
- reference_type (varchar 100, nullable)
- reference_id (bigint, nullable)
- total_debit (decimal 12,3, default: 0)
- total_credit (decimal 12,3, default: 0)
- status (enum: draft, posted, cancelled, default: draft)
- created_by (FK → users.id)
- posted_by (FK → users.id, nullable)
- timestamps
```

### 7.6 `journal_entry_lines`
```
- id, journal_entry_id (FK)
- account_id (FK → accounts.id)
- description (varchar 255, nullable)
- debit (decimal 12,3, default: 0)
- credit (decimal 12,3, default: 0)
- timestamps
```

---

## 🔴 المجموعة 8 — الإعدادات (Settings)

### 8.1 `settings`
```
- id, company_id (nullable — null = system-wide)
- branch_id (FK, nullable)
- group (varchar 100) — "company", "branch", "pos", "invoice", "tax"
- key (varchar 100)
- value (text)
- type (enum: string, integer, boolean, json, default: string)
- timestamps
- Unique: [company_id, branch_id, key]
```

### 8.2 `tax_rates`
```
- id, company_id
- name (varchar 255)
- rate (decimal 5,2) — 14.00
- is_default (boolean, default: false)
- is_active (boolean, default: true)
- timestamps
```

### 8.3 `payment_methods`
```
- id, company_id
- name (varchar 255)
- code (varchar 50)
- type (enum: cash, card, bank_transfer, mobile_wallet, credit, insurance)
- is_active (boolean, default: true)
- settings (JSON, nullable)
- timestamps
```

---

## 🔴 المجموعة 9 — SaaS والاشتراكات

### 9.1 `subscription_plans`
```
- id
- name (varchar 255)
- slug (varchar 255, unique)
- description (text, nullable)
- price_monthly (decimal 10,2)
- price_yearly (decimal 10,2, nullable)
- currency (varchar 3, default: 'EGP')
- max_branches (int)
- max_users (int)
- max_products (int)
- max_invoices_per_month (int, nullable)
- features (JSON) — ["pos", "inventory", "reports", "insurance"]
- is_active (boolean, default: true)
- sort_order (int, default: 0)
- timestamps
```

### 9.2 `subscriptions`
```
- id, company_id (FK)
- plan_id (FK → subscription_plans.id)
- status (enum: trial, active, past_due, cancelled, expired, default: trial)
- trial_ends_at (timestamp, nullable)
- starts_at (timestamp)
- ends_at (timestamp, nullable)
- cancelled_at (timestamp, nullable)
- billing_cycle (enum: monthly, yearly, default: monthly)
- amount (decimal 10,2)
- currency (varchar 3, default: 'EGP')
- payment_method (varchar 50, nullable)
- gateway_id (varchar 255, nullable) — Stripe/PayPal ID
- auto_renew (boolean, default: true)
- timestamps
```

### 9.3 `invoices` (فواتير SaaS)
```
- id, company_id
- invoice_number (varchar 50, unique)
- subscription_id (FK, nullable)
- amount (decimal 10,2)
- currency (varchar 3)
- status (enum: draft, open, paid, void, refunded, default: draft)
- due_date (date)
- paid_at (timestamp, nullable)
- pdf_path (varchar 500, nullable)
- notes (text, nullable)
- timestamps
```

---

## 🔴 المجموعة 10 — الإشعارات والسجلات

### 10.1 `notifications`
```
- id (uuid)
- type (varchar 255)
- notifiable_type (varchar 255)
- notifiable_id (bigint)
- data (text)
- read_at (timestamp, nullable)
- channels (JSON) — ["database", "email", "sms"]
- timestamps
- Index: [notifiable_type, notifiable_id]
```

### 10.2 `activity_logs` (موجود — تأكد من وجوده)
```
- id
- log_name (varchar 255, nullable)
- description (text)
- subject_type (varchar 255, nullable)
- subject_id (bigint, nullable)
- causer_type (varchar 255, nullable)
- causer_id (bigint, nullable)
- properties (JSON, nullable)
- timestamps
- Index: [subject_type, subject_id], [causer_type, causer_id]
```

### 10.3 `system_logs`
```
- id, company_id, branch_id
- user_id (FK, nullable)
- level (enum: info, warning, error, critical)
- action (varchar 255)
- description (text)
- ip_address (varchar 45, nullable)
- user_agent (text, nullable)
- metadata (JSON, nullable)
- timestamps
- Index: [company_id, level], [user_id]
```

---

# ⚡ قواعد صارمة (Strict Rules)

## 📐 Naming Conventions:
1. **جداول**: snake_case, plural (`purchase_orders`, NOT `purchaseOrders`)
2. **أعمدة**: snake_case (`created_at`, `expiry_date`)
3. **Foreign Keys**: `{table_singular}_id` (`company_id`, `product_id`)
4. **Pivot Tables**: `{table1}_{table2}_alphabetical` (إذا لزم)
5. **Enums**: lowercase with underscores (`partially_dispensed`)

## 🔗 Relationships:
1. كل FK يجب أن يكون له **Index**
2. كل FK يجب أن يكون له **Foreign Key Constraint**
3. `ON DELETE`:
   - `CASCADE` للبيانات التابعة (مثل `sale_items` عند حذف `sale`)
   - `RESTRICT` للبيانات المهمة (مثل `products` إذا كانت مرتبطة بـ `sales`)
   - `SET NULL` للبيانات الاختيارية
4. استخدم **Soft Deletes** للجداول التجارية (لا تحذف بيانات حقيقية)

## 🚀 Performance:
1. **Index** على كل FK
2. **Composite Index** للاستعلامات الشائعة:
   - `[company_id, product_id]` على inventory
   - `[branch_id, sale_date]` على sales
   - `[company_id, expiry_date]` على inventory
3. **Full-text Index** على `products.name`, `products.generic_name`
4. **Decimal** للمال (NOT float) — `decimal(12,3)`
5. **Enum** بدلاً من varchar للحالات المحددة

## 🔒 Security:
1. `company_id` على كل جدول خاص بالمستأجر
2. لا تحذف بيانات — استخدم Soft Deletes
3. `audit trails` عبر `stock_movements`, `activity_logs`
4. `controlled_substance_log` منفصل للمواد الخطرة

## 🌍 Localization:
1. كل النصوص `utf8mb4_unicode_ci`
2. حقول `name` و `description` تدعم العربية
3. العملة: `decimal(12,3)` — 3 خانات للكسور (جنيه مصري)
4. التواريخ: `date` أو `timestamp` (Laravel يتولى التنسيق)

---

# 📦 المخرجات المطلوبة (Deliverables)

## 1. Laravel Migrations (بالترتيب):
- كل جدول في ملف migration منفصل
- اسم الملف: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- استخدم `$table->foreignId()->constrained()->cascadeOnDelete()` حيث يلزم

## 2. Models (Eloquent):
- Model لكل جدول
- العلاقات (`belongsTo`, `hasMany`, `belongsToMany`)
- `$fillable`, `$casts`, `$dates`
- Scopes للاستعلامات الشائعة (`scopeActive()`, `scopeForCompany($companyId)`)
- Accessors/Mutators عند الحاجة

## 3. Factories + Seeders:
- Factory لكل model ببيانات واقعية (Faker بالعربية)
- Seeder رئيسي `DatabaseSeeder` يستدعي كل الـ seeders
- بيانات تجريبية:
  - 1 شركة + 3 فروع
  - 10 مستخدمين بأدوار مختلفة
  - 5 موردين
  - 20 عميل/مريض
  - 10 أطباء
  - 100 منتج (أدوية حقيقية بأسماء مصرية)
  - 50 وصفة طبية
  - 200 عملية بيع
  - 50 أمر شراء

## 4. SQL Views (اختياري لكن مفضل):
- `v_current_stock` — المخزون الحالي لكل فرع
- `v_near_expiry_products` — المنتجات قريبة الانتهاء (90 يوم)
- `v_daily_sales_summary` — ملخص المبيعات اليومية
- `v_low_stock_alerts` — تنبيهات المخزون المنخفض
- `v_profit_loss` — الربح/الخسارة

## 5. Stored Procedures (اختياري):
- `sp_calculate_stock_value(branch_id)` — قيمة المخزون
- `sp_get_sales_report(start_date, end_date, branch_id)` — تقرير المبيعات
- `sp_reorder_suggestions()` — اقتراحات إعادة الطلب

## 6. ملف `database/structure.md`:
- توثيق كامل لكل جدول
- العلاقات (ERD diagram بـ Mermaid)
- أمثلة استعلامات

---

# 🚀 خطة التنفيذ (Implementation Order)

## المرحلة 1 — الأساس (Foundation)
1. إصلاح/إنشاء `companies`, `branches`, `departments`, `users`
2. `roles`, `permissions`, `model_has_roles`, `role_has_permissions` (Spatie)
3. `subscription_plans`, `subscriptions`

## المرحلة 2 — مجال الصيدلية
4. `categories`, `manufacturers`, `products`, `product_variants`
5. `drug_interactions`, `product_alternatives`
6. `suppliers`, `customers`, `doctors`

## المرحلة 3 — المخزون
7. `inventory`, `stock_movements`
8. `stock_adjustments`, `stock_adjustment_items`
9. `stock_transfers`, `stock_transfer_items`

## المرحلة 4 — المشتريات
10. `purchase_orders`, `purchase_order_items`
11. `goods_received_notes`, `grn_items`

## المرحلة 5 — المبيعات
12. `sales`, `sale_items`, `sale_payments`
13. `sale_returns`, `sale_return_items`
14. `cash_registers`, `held_sales`

## المرحلة 6 — الوصفات الطبية
15. `prescriptions`, `prescription_items`
16. `controlled_substance_log`

## المرحلة 7 — التأمين
17. `insurance_companies`, `insurance_plans`, `insurance_claims`

## المرحلة 8 — المالية
18. `expenses`, `expense_categories`
19. `payment_records`
20. `accounts`, `journal_entries`, `journal_entry_lines`

## المرحلة 9 — الإعدادات
21. `settings`, `tax_rates`, `payment_methods`

## المرحلة 10 — النظام
22. `notifications`, `activity_logs`, `system_logs`

## المرحلة 11 — Seeders + Views + Procedures

---

# 🎯 الإجراء الأول (First Action)

**ابدأ بـ:**

1. ✅ **أكد فهمك للسياق** — اذكر لي باختصار ما فهمته
2. ✅ **أنشئ ملف `DATABASE_ARCHITECTURE.md`** يحتوي على:
   - ERD Diagram (Mermaid format)
   - قائمة الجداول مع العلاقات
   - قرارات التصميم (ADRs)
3. ✅ **ابدأ بالمرحلة 1** — أنشئ migrations الأساس:
   - `companies` (CREATE أو ALTER)
   - `branches`
   - `departments`
   - `users` (CREATE أو ALTER)
4. ✅ **بعد كل migration**، شغّل:
   ```bash
   php artisan migrate
   ```
   للتأكد من عدم وجود أخطاء

---

# ⚠️ Laragon-specific Notes

1. **Character Set**: تأكد أن `config/database.php` يستخدم:
   ```php
   'charset' => 'utf8mb4',
   'collation' => 'utf8mb4_unicode_ci',
   ```

2. **MySQL Version**: Laragon يستخدم MySQL 8.x — استخدم الميزات الحديثة:
   - `JSON` columns
   - `Generated columns` (عند الحاجة)
   - `Window functions` (في الـ Views)

3. **phpMyAdmin**: بعد كل migration، تحقق من البنية عبر:
   ```
   http://localhost/phpmyadmin
   ```
   اختر `pharmacy_db` → راجع الجداول

4. **Backup**: قبل أي تغيير جذري:
   ```bash
   # من Laragon Terminal
   cd C:\laragon\bin\mysql\mysql-8.x.x\bin
   mysqldump -u root pharmacy_db > backup.sql
   ```

5. **Reset**: لإعادة تعيين قاعدة البيانات:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

# 💬 ملاحظات إضافية

- **اللغة المفضلة**: العربية في التواصل، الإنجليزية في الكود
- **البيانات التجريبية**: استخدم أسماء أدوية حقيقية في السوق المصري (Panadol, Cataflam, Augmentin, Concor, Glucophage...)
- **الأسعار**: بالجنيه المصري (EGP)
- **لا تتردد في طرح أسئلة** إذا كان هناك غموض

---

**ابدأ الآن. أظهر لي أنك فهمت كل شيء، ثم ابدأ بـ DATABASE_ARCHITECTURE.md.**