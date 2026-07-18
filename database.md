# 🎭 الدور (Role / Persona)

أنت **مهندس قواعد بيانات أول (Principal Database Architect)** و**خبير Laravel Backend** متخصص في:
- تصميم قواعد بيانات **SaaS متعددة المستأجرين (Multi-Tenant)** على مستوى المؤسسات
- أنظمة **إدارة الصيدليات (Pharmacy Management Systems)** المتوافقة مع HIPAA, GPP, GDP
- **PostgreSQL 16** مع أحدث الممارسات (Partitioning, JSONB, Full-text Search, Materialized Views)
- **Laravel 11+** Eloquent ORM مع Advanced Relationships, Scopes, Observers
- **Performance Tuning** (Indexing Strategy, Query Optimization, Caching)
- **Data Integrity** (Foreign Keys, Constraints, Transactions, ACID Compliance)
- **Audit & Compliance** (Full Audit Trails, Soft Deletes, Data Retention)

أنت تعمل بمعايير **Stripe, Shopify, Veeva (Pharma)** من حيث جودة التصميم.

---

# 📋 سياق المشروع (Project Context)

اسم المشروع: **Z-Syst Pharmacy Management SaaS**
اسم قاعدة البيانات: **`pharmacy_db`**
التقنية: **Laravel 11 + PostgreSQL 16**
الحالة الحالية: **~5% مكتمل** — توجد هجرات جزئية تُعدّل جداول (`companies`, `branches`, `users`) لكن **الهجرات الأساسية (CREATE TABLE) مفقودة**.

## 🎯 المهمة:
بناء قاعدة بيانات **`pharmacy_db`** كاملة من الصفر، تشمل:
1. **30+ جدول** موزعة على 12 مجموعة وظيفية
2. **كل العلاقات (Foreign Keys)** مع قواعد CASCADE الصحيحة
3. **Indexes محسّنة** لكل حقول البحث والفلترة
4. **Multi-tenancy** صارم (`company_id` على كل جدول)
5. **Audit trails** (`created_by`, `updated_by`, `deleted_by`)
6. **Soft deletes** لكل الجداول التجارية
7. **Migrations مرتبة** حسب التبعيات
8. **Models كاملة** مع كل العلاقات والـ Scopes
9. **Factories + Seeders** لبيانات تجريبية واقعية
10. **ربط كامل بـ Laravel** (config, .env, connections)

---

# 🏗️ البنية التقنية (Technical Architecture)

## قاعدة البيانات:
- **Engine**: PostgreSQL 16 (ليس MySQL — نحتاج JSONB, Full-text Search, Partitioning)
- **Collation**: `en_US.UTF-8` + `ar_SA.UTF-8` (للعربية)
- **Timezone**: UTC (تخزين) + تحويل للعرض
- **Character Set**: UTF-8

## Laravel:
- **Version**: 11.x
- **PHP**: 8.3+
- **ORM**: Eloquent مع Global Scopes للـ Multi-tenancy
- **Migrations**: مرتبة بـ timestamps صحيحة
- **SoftDeletes**: على كل الجداول التجارية
- **UUIDs**: للجداول العامة (customers, products) — **Auto-increment** للجداول الداخلية

## معايير التسمية (Naming Conventions):
- **جداول**: snake_case, plural (e.g., `products`, `sale_items`)
- **أعمدة**: snake_case, singular (e.g., `product_id`, `created_at`)
- **Foreign Keys**: `{singular_table}_id` (e.g., `company_id`, `branch_id`)
- **Pivot Tables**: `{table1}_{table2}` مرتبة أبجدياً (e.g., `permission_role`)
- **Indexes**: `idx_{table}_{column(s)}` (e.g., `idx_products_barcode`)
- **Unique Indexes**: `uniq_{table}_{column}` (e.g., `uniq_products_barcode_company`)
- **Constraints**: `fk_{table}_{column}` (e.g., `fk_sales_branch_id`)

---

# 📊 مخطط قاعدة البيانات الكامل (Database Schema)

## 🔵 المجموعة 1: Core Multi-Tenancy (الأساس)

### 1. `companies` (الشركات/الصيدليات)
```sql
id                  UUID PRIMARY KEY DEFAULT gen_random_uuid()
name                VARCHAR(255) NOT NULL
trade_name          VARCHAR(255)
legal_name          VARCHAR(255)
tax_number          VARCHAR(50) UNIQUE
pharmacy_license    VARCHAR(100)
license_expiry      DATE
logo_path           VARCHAR(500)
address             TEXT
city                VARCHAR(100)
country             VARCHAR(100) DEFAULT 'EG'
phone               VARCHAR(50)
email               VARCHAR(255)
website             VARCHAR(255)
subscription_plan   VARCHAR(50) DEFAULT 'starter'
subscription_status VARCHAR(50) DEFAULT 'active'
subscription_ends   TIMESTAMP
settings            JSONB DEFAULT '{}'
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULL
```
**Indexes**: `idx_companies_tax_number`, `idx_companies_is_active`

### 2. `branches` (الفروع)
```sql
id                  UUID PRIMARY KEY
company_id          UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE
name                VARCHAR(255) NOT NULL
code                VARCHAR(50) NOT NULL
type                VARCHAR(50) DEFAULT 'pharmacy' -- pharmacy, warehouse
address             TEXT
city                VARCHAR(100)
phone               VARCHAR(50)
email               VARCHAR(255)
working_hours       JSONB -- {"saturday":{"open":"09:00","close":"22:00"},...}
timezone            VARCHAR(50) DEFAULT 'Africa/Cairo'
currency            VARCHAR(3) DEFAULT 'EGP'
tax_rate            DECIMAL(5,2) DEFAULT 0.00
receipt_template    JSONB DEFAULT '{}'
is_active           BOOLEAN DEFAULT TRUE
created_by          UUID REFERENCES users(id)
updated_by          UUID REFERENCES users(id)
created_at, updated_at, deleted_at
```
**Unique**: `uniq_branches_code_company` (company_id, code)
**Indexes**: `idx_branches_company_id`, `idx_branches_is_active`

### 3. `departments` (الأقسام داخل الفرع)
```sql
id, branch_id (FK), name, code, description, manager_id (FK users), is_active, timestamps
```

### 4. `users` (المستخدمون)
```sql
id                  UUID PRIMARY KEY
company_id          UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE
branch_id           UUID NULL REFERENCES branches(id) ON DELETE SET NULL
department_id       UUID NULL REFERENCES departments(id)
first_name          VARCHAR(100) NOT NULL
last_name           VARCHAR(100) NOT NULL
email               VARCHAR(255) NOT NULL UNIQUE
phone               VARCHAR(50)
password            VARCHAR(255) NOT NULL -- bcrypt
avatar_path         VARCHAR(500)
role                VARCHAR(50) -- super_admin, company_admin, branch_manager, pharmacist, cashier
is_active           BOOLEAN DEFAULT TRUE
email_verified_at   TIMESTAMP
two_factor_secret   TEXT NULL
two_factor_recovery_codes TEXT NULL
last_login_at       TIMESTAMP
last_login_ip       VARCHAR(45)
locale              VARCHAR(10) DEFAULT 'ar'
timezone            VARCHAR(50) DEFAULT 'Africa/Cairo'
created_at, updated_at, deleted_at
```
**Indexes**: `idx_users_email`, `idx_users_company_id`, `idx_users_branch_id`

---

## 🔵 المجموعة 2: RBAC & Security

### 5. `roles`
```sql
id, company_id (FK), name, slug (UNIQUE per company), description, is_system (BOOLEAN), level (INT), timestamps, deleted_at
```

### 6. `permissions`
```sql
id, company_id (FK) NULL, name, slug (UNIQUE), group (VARCHAR), description, is_system (BOOLEAN), timestamps
```

### 7. `role_permission` (Pivot)
```sql
role_id (FK), permission_id (FK), PRIMARY KEY (role_id, permission_id)
```

### 8. `model_has_roles` (Spatie-style)
```sql
role_id, model_type, model_id, PRIMARY KEY (role_id, model_id, model_type)
```

### 9. `model_has_permissions`
```sql
permission_id, model_type, model_id, PRIMARY KEY (...)
```

### 10. `personal_access_tokens` (Sanctum)
```sql
id, tokenable_type, tokenable_id, name, token (UNIQUE), abilities (TEXT), last_used_at, expires_at, timestamps
```

### 11. `password_reset_tokens`
```sql
email (PK), token, created_at
```

### 12. `sessions`
```sql
id (VARCHAR PK), user_id (FK NULL), ip_address (VARCHAR 45), user_agent (TEXT), payload (LONGTEXT), last_activity (INT)
```

### 13. `failed_jobs`
```sql
id, uuid (UNIQUE), connection, queue, payload (LONGTEXT), exception (LONGTEXT), failed_at
```

### 14. `jobs` + `job_batches` (Laravel Queues)

---

## 🔵 المجموعة 3: Product Catalog (المنتجات)

### 15. `categories` (هرمي)
```sql
id                  UUID PRIMARY KEY
company_id          UUID NOT NULL REFERENCES companies(id)
parent_id           UUID NULL REFERENCES categories(id) ON DELETE SET NULL
name                VARCHAR(255) NOT NULL
slug                VARCHAR(255) NOT NULL
description         TEXT
image_path          VARCHAR(500)
sort_order          INT DEFAULT 0
level               INT DEFAULT 0 -- depth level
is_active           BOOLEAN DEFAULT TRUE
created_at, updated_at, deleted_at
```
**Unique**: `uniq_categories_slug_company` (company_id, slug)
**Indexes**: `idx_categories_parent_id`, `idx_categories_company_id`

### 16. `manufacturers` (المصنعون)
```sql
id, company_id, name, code (UNIQUE per company), contact_person, email, phone, address, country, website, logo_path, tax_id, payment_terms, credit_limit, notes, is_active, created_by, updated_by, timestamps, deleted_at
```

### 17. `products` (المنتجات/الأدوية) — **الأهم**
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL REFERENCES companies(id)
category_id             UUID NULL REFERENCES categories(id)
manufacturer_id         UUID NULL REFERENCES manufacturers(id)
name                    VARCHAR(255) NOT NULL
generic_name            VARCHAR(255) -- الاسم العلمي
brand_name              VARCHAR(255)
barcode                 VARCHAR(100) -- EAN-13, UPC, etc.
sku                     VARCHAR(100)
description             TEXT
dosage_form             VARCHAR(50) -- tablet, capsule, syrup, injection, cream, ointment, drops, inhaler, suppository
strength                VARCHAR(100) -- e.g., "500mg", "250mg/5ml"
unit_of_measure         VARCHAR(50) DEFAULT 'piece' -- piece, strip, box, bottle, tube
pack_size               INT DEFAULT 1 -- e.g., 10 tablets per strip
pack_unit               VARCHAR(50) -- strip, box, bottle
atc_code                VARCHAR(50) -- Anatomical Therapeutic Chemical code
requires_prescription   BOOLEAN DEFAULT FALSE
is_controlled           BOOLEAN DEFAULT FALSE -- المواد الخاضعة للرقابة
controlled_schedule     VARCHAR(20) NULL -- table_1, table_2, narcotic, psychotropic
storage_conditions      VARCHAR(100) -- room_temp, refrigerated, frozen, protected_from_light
min_stock_level         INT DEFAULT 0
reorder_point           INT DEFAULT 0
max_stock_level         INT DEFAULT 0
cost_price              DECIMAL(12,3) DEFAULT 0.000
selling_price           DECIMAL(12,3) DEFAULT 0.000
wholesale_price         DECIMAL(12,3) DEFAULT 0.000
tax_rate_id             UUID NULL REFERENCES tax_rates(id)
discount_percentage     DECIMAL(5,2) DEFAULT 0.00
is_active               BOOLEAN DEFAULT TRUE
image_path              VARCHAR(500)
metadata                JSONB DEFAULT '{}' -- extra attributes
created_by              UUID REFERENCES users(id)
updated_by              UUID REFERENCES users(id)
created_at, updated_at, deleted_at
```
**Unique**: `uniq_products_barcode_company` (company_id, barcode) WHERE barcode IS NOT NULL
**Unique**: `uniq_products_sku_company` (company_id, sku) WHERE sku IS NOT NULL
**Indexes**: 
- `idx_products_company_id`
- `idx_products_category_id`
- `idx_products_manufacturer_id`
- `idx_products_name` (للبحث)
- `idx_products_generic_name` (للبحث)
- `idx_products_requires_prescription`
- `idx_products_is_controlled`
- `idx_products_is_active`
- **Full-text Search Index**: `idx_products_search` (GIN on to_tsvector('arabic', name || ' ' || generic_name || ' ' || brand_name))

### 18. `product_variants` (أحجام العبوات المختلفة)
```sql
id, product_id (FK), variant_name, barcode (UNIQUE per product), pack_size, pack_unit, cost_price, selling_price, sku, is_active, timestamps
```

### 19. `product_images`
```sql
id, product_id (FK), path, alt_text, sort_order, is_primary (BOOLEAN), timestamps
```

### 20. `product_price_history` (تتبع تغيرات الأسعار)
```sql
id, product_id (FK), field_changed (VARCHAR: cost_price/selling_price), old_value (DECIMAL), new_value (DECIMAL), changed_by (FK users), changed_at, reason (TEXT)
```

### 21. `drug_interactions` (تفاعلات الأدوية)
```sql
id, drug_a_id (FK products), drug_b_id (FK products), severity (VARCHAR: mild/moderate/severe/contraindicated), description (TEXT), management (TEXT), references (TEXT), is_active, timestamps
```
**Unique**: `uniq_drug_interactions_pair` (drug_a_id, drug_b_id) — مع CHECK (drug_a_id < drug_b_id) لمنع التكرار

---

## 🔵 المجموعة 4: CRM (العملاء/المرضى/الأطباء)

### 22. `customers` / `patients` (العملاء/المرضى)
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL REFERENCES companies(id)
customer_code           VARCHAR(50) NOT NULL -- auto-generated
first_name              VARCHAR(100) NOT NULL
last_name               VARCHAR(100) NOT NULL
national_id             VARCHAR(50) -- رقم قومي
date_of_birth           DATE
gender                  VARCHAR(20) -- male, female, other
phone                   VARCHAR(50) NOT NULL
phone_secondary         VARCHAR(50)
email                   VARCHAR(255)
address                 TEXT
city                    VARCHAR(100)
blood_group             VARCHAR(10)
allergies               JSONB DEFAULT '[]' -- ["penicillin", "aspirin"]
chronic_conditions      JSONB DEFAULT '[]' -- ["diabetes", "hypertension"]
current_medications     JSONB DEFAULT '[]'
medical_history         JSONB DEFAULT '{}'
insurance_id            UUID NULL REFERENCES insurance_companies(id)
insurance_number        VARCHAR(100)
insurance_policy        VARCHAR(100)
loyalty_points          INT DEFAULT 0
total_purchases         DECIMAL(15,3) DEFAULT 0.000
credit_limit            DECIMAL(15,3) DEFAULT 0.000
current_balance         DECIMAL(15,3) DEFAULT 0.000
notes                   TEXT
is_vip                  BOOLEAN DEFAULT FALSE
is_active               BOOLEAN DEFAULT TRUE
created_by              UUID REFERENCES users(id)
updated_by              UUID REFERENCES users(id)
created_at, updated_at, deleted_at
```
**Unique**: `uniq_customers_code_company` (company_id, customer_code)
**Indexes**: `idx_customers_phone`, `idx_customers_national_id`, `idx_customers_company_id`, `idx_customers_insurance_id`

### 23. `doctors` (الأطباء)
```sql
id, company_id, doctor_code (UNIQUE per company), first_name, last_name, specialization, license_number, license_expiry, clinic_name, clinic_address, phone, phone_secondary, email, commission_percentage (DECIMAL 5,2), total_prescriptions (INT), is_active, created_by, updated_by, timestamps, deleted_at
```

---

## 🔵 المجموعة 5: Supply Chain (الموردون والمشتريات)

### 24. `suppliers` (الموردون)
```sql
id, company_id, supplier_code (UNIQUE per company), name, legal_name, tax_id, contact_person, email, phone, phone_secondary, address, city, country, website, logo_path, payment_terms (VARCHAR: cash, net_30, net_60), credit_limit (DECIMAL), current_balance (DECIMAL), total_purchases (DECIMAL), rating (INT 1-5), notes, is_active, created_by, updated_by, timestamps, deleted_at
```

### 25. `purchase_orders` (أوامر الشراء)
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL REFERENCES companies(id)
branch_id               UUID NOT NULL REFERENCES branches(id)
supplier_id             UUID NOT NULL REFERENCES suppliers(id)
po_number               VARCHAR(50) NOT NULL -- auto-generated: PO-2026-0001
status                  VARCHAR(50) DEFAULT 'draft' -- draft, pending_approval, approved, sent, partially_received, received, cancelled
order_date              DATE NOT NULL
expected_delivery_date  DATE
actual_delivery_date    DATE
subtotal                DECIMAL(15,3) DEFAULT 0.000
discount_amount         DECIMAL(15,3) DEFAULT 0.000
discount_type           VARCHAR(20) DEFAULT 'fixed' -- fixed, percentage
tax_amount              DECIMAL(15,3) DEFAULT 0.000
shipping_cost           DECIMAL(15,3) DEFAULT 0.000
total_amount            DECIMAL(15,3) DEFAULT 0.000
paid_amount             DECIMAL(15,3) DEFAULT 0.000
due_amount              DECIMAL(15,3) DEFAULT 0.000
payment_status          VARCHAR(50) DEFAULT 'unpaid' -- unpaid, partial, paid
notes                   TEXT
approved_by             UUID NULL REFERENCES users(id)
approved_at             TIMESTAMP
created_by              UUID REFERENCES users(id)
updated_by              UUID REFERENCES users(id)
created_at, updated_at, deleted_at
```
**Unique**: `uniq_purchase_orders_po_number_company` (company_id, po_number)
**Indexes**: `idx_purchase_orders_company_id`, `idx_purchase_orders_supplier_id`, `idx_purchase_orders_branch_id`, `idx_purchase_orders_status`, `idx_purchase_orders_order_date`

### 26. `purchase_order_items`
```sql
id, purchase_order_id (FK), product_id (FK), quantity_ordered (DECIMAL), quantity_received (DECIMAL) DEFAULT 0, unit_cost (DECIMAL), discount (DECIMAL), tax (DECIMAL), total (DECIMAL), notes, timestamps
```

### 27. `goods_received_notes` (إذونات الاستلام)
```sql
id, company_id, branch_id, grn_number (UNIQUE), purchase_order_id (FK), supplier_id (FK), received_date, received_by (FK users), verified_by (FK users NULL), status (VARCHAR: draft, verified, cancelled), notes, timestamps, deleted_at
```

### 28. `grn_items`
```sql
id, grn_id (FK), purchase_order_item_id (FK NULL), product_id (FK), batch_number (VARCHAR), expiry_date (DATE), manufacturing_date (DATE NULL), quantity_received (DECIMAL), unit_cost (DECIMAL), rack_location (VARCHAR), notes, timestamps
```

### 29. `purchase_returns` (مرتجعات المشتريات)
```sql
id, company_id, branch_id, return_number (UNIQUE), supplier_id (FK), purchase_order_id (FK NULL), grn_id (FK NULL), return_date, reason, subtotal, tax, total, status (draft, approved, sent, cancelled), approved_by, created_by, timestamps, deleted_at
```

### 30. `purchase_return_items`
```sql
id, purchase_return_id (FK), product_id (FK), batch_number, quantity, unit_cost, total, reason, timestamps
```

---

## 🔵 المجموعة 6: Inventory (المخزون)

### 31. `inventory` / `stock` (المخزون الحالي)
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL REFERENCES companies(id)
branch_id               UUID NOT NULL REFERENCES branches(id)
product_id              UUID NOT NULL REFERENCES products(id)
batch_number            VARCHAR(100) NOT NULL
expiry_date             DATE NOT NULL
manufacturing_date      DATE NULL
quantity                DECIMAL(15,3) DEFAULT 0.000
reserved_quantity       DECIMAL(15,3) DEFAULT 0.000 -- محجوز لطلبات معلقة
available_quantity      DECIMAL(15,3) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED
cost_price              DECIMAL(12,3) DEFAULT 0.000
selling_price           DECIMAL(12,3) DEFAULT 0.000
rack_location           VARCHAR(100)
supplier_id             UUID NULL REFERENCES suppliers(id)
purchase_order_id       UUID NULL REFERENCES purchase_orders(id)
grn_id                  UUID NULL REFERENCES goods_received_notes(id)
status                  VARCHAR(50) DEFAULT 'available' -- available, quarantined, expired, recalled
received_at             TIMESTAMP
last_moved_at           TIMESTAMP
created_at, updated_at
```
**Unique**: `uniq_inventory_batch_branch_product` (company_id, branch_id, product_id, batch_number)
**Indexes**: 
- `idx_inventory_company_branch_product`
- `idx_inventory_expiry_date` (حرج!)
- `idx_inventory_batch_number`
- `idx_inventory_status`
- `idx_inventory_quantity` (للـ low stock alerts)

### 32. `stock_movements` (حركات المخزون) — **Audit Trail**
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL
branch_id               UUID NOT NULL
product_id              UUID NOT NULL
batch_number            VARCHAR(100)
movement_type           VARCHAR(50) NOT NULL -- purchase_in, sale_out, return_in, return_out, transfer_in, transfer_out, adjustment_in, adjustment_out, damage, expired, recall
reference_type          VARCHAR(100) -- purchase_order, sale, sale_return, stock_transfer, stock_adjustment
reference_id            UUID
quantity                DECIMAL(15,3) NOT NULL
quantity_before         DECIMAL(15,3) NOT NULL
quantity_after          DECIMAL(15,3) NOT NULL
cost_price              DECIMAL(12,3)
unit_cost               DECIMAL(12,3)
total_cost              DECIMAL(15,3)
from_branch_id          UUID NULL
to_branch_id            UUID NULL
notes                   TEXT
performed_by            UUID NOT NULL REFERENCES users(id)
performed_at            TIMESTAMP NOT NULL
created_at              TIMESTAMP
```
**Indexes**: 
- `idx_stock_movements_company_branch`
- `idx_stock_movements_product`
- `idx_stock_movements_movement_type`
- `idx_stock_movements_performed_at`
- `idx_stock_movements_reference` (reference_type, reference_id)

### 33. `stock_adjustments` (تسويات المخزون)
```sql
id, company_id, branch_id, adjustment_number (UNIQUE), adjustment_type (addition, reduction, recount), reason (TEXT), status (draft, pending_approval, approved, rejected, applied), total_items (INT), approved_by (FK NULL), approved_at, applied_by (FK NULL), applied_at, created_by, timestamps, deleted_at
```

### 34. `stock_adjustment_items`
```sql
id, stock_adjustment_id (FK), product_id (FK), batch_number, quantity_before, quantity_after, difference, cost_price, notes, timestamps
```

### 35. `stock_transfers` (تحويلات بين الفروع)
```sql
id, company_id, transfer_number (UNIQUE), from_branch_id (FK), to_branch_id (FK), status (pending, approved, in_transit, received, cancelled), requested_by (FK), approved_by (FK NULL), shipped_at, received_at, notes, timestamps, deleted_at
```

### 36. `stock_transfer_items`
```sql
id, stock_transfer_id (FK), product_id (FK), batch_number, quantity_requested, quantity_approved, quantity_shipped, quantity_received, cost_price, notes, timestamps
```

### 37. `stock_takes` (الجرد الفعلي)
```sql
id, company_id, branch_id, stock_take_number (UNIQUE), status (draft, in_progress, completed, cancelled), start_date, end_date, completed_by (FK NULL), notes, timestamps, deleted_at
```

### 38. `stock_take_items`
```sql
id, stock_take_id (FK), product_id (FK), batch_number, system_quantity, counted_quantity, difference, cost_price, notes, timestamps
```

---

## 🔵 المجموعة 7: Sales & POS (المبيعات)

### 39. `sales` (عمليات البيع)
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL REFERENCES companies(id)
branch_id               UUID NOT NULL REFERENCES branches(id)
cash_register_id        UUID NULL REFERENCES cash_registers(id)
customer_id             UUID NULL REFERENCES customers(id)
prescription_id         UUID NULL REFERENCES prescriptions(id)
user_id                 UUID NOT NULL REFERENCES users(id) -- cashier
invoice_number          VARCHAR(50) NOT NULL -- auto: INV-2026-0001
invoice_date            TIMESTAMP NOT NULL
sale_type               VARCHAR(50) DEFAULT 'walk_in' -- walk_in, prescription, insurance, wholesale
subtotal                DECIMAL(15,3) DEFAULT 0.000
discount_amount         DECIMAL(15,3) DEFAULT 0.000
discount_type           VARCHAR(20) DEFAULT 'fixed' -- fixed, percentage
coupon_id               UUID NULL REFERENCES coupons(id)
tax_amount              DECIMAL(15,3) DEFAULT 0.000
total_amount            DECIMAL(15,3) DEFAULT 0.000
amount_paid             DECIMAL(15,3) DEFAULT 0.000
change_amount           DECIMAL(15,3) DEFAULT 0.000
due_amount              DECIMAL(15,3) DEFAULT 0.000
payment_method          VARCHAR(50) DEFAULT 'cash' -- cash, card, insurance, mixed, credit
payment_status          VARCHAR(50) DEFAULT 'paid' -- paid, partial, pending, refunded, cancelled
items_count             INT DEFAULT 0
notes                   TEXT
metadata                JSONB DEFAULT '{}'
status                  VARCHAR(50) DEFAULT 'completed' -- pending, completed, voided, refunded
voided_by               UUID NULL REFERENCES users(id)
voided_at               TIMESTAMP NULL
void_reason             TEXT NULL
created_at, updated_at, deleted_at
```
**Unique**: `uniq_sales_invoice_number_branch` (branch_id, invoice_number)
**Indexes**: 
- `idx_sales_company_branch`
- `idx_sales_customer_id`
- `idx_sales_user_id`
- `idx_sales_invoice_date`
- `idx_sales_status`
- `idx_sales_payment_status`

### 40. `sale_items`
```sql
id, sale_id (FK), product_id (FK), batch_number, prescription_item_id (FK NULL), name_snapshot (VARCHAR), quantity (DECIMAL), unit_price (DECIMAL), discount (DECIMAL), tax_rate (DECIMAL), tax_amount (DECIMAL), total (DECIMAL), is_gift (BOOLEAN), notes, timestamps
```

### 41. `sale_payments` (مدفوعات متعددة)
```sql
id, sale_id (FK), payment_method (VARCHAR), amount (DECIMAL), reference_number (VARCHAR), card_last_four (VARCHAR NULL), transaction_id (VARCHAR NULL), timestamps
```

### 42. `sale_returns` (المرتجعات)
```sql
id, company_id, branch_id, return_number (UNIQUE), sale_id (FK), customer_id (FK NULL), user_id (FK), return_date, reason (TEXT), subtotal, discount, tax, total, refund_method (cash, credit, original), refund_status (pending, approved, processed, rejected), approved_by (FK NULL), processed_by (FK NULL), notes, timestamps, deleted_at
```

### 43. `sale_return_items`
```sql
id, sale_return_id (FK), sale_item_id (FK NULL), product_id (FK), batch_number, quantity, unit_price, total, condition (sellable, damaged, expired), notes, timestamps
```

### 44. `cash_registers` (الصناديق)
```sql
id, company_id, branch_id, user_id (FK), register_number (VARCHAR), opening_balance (DECIMAL), closing_balance (DECIMAL), expected_balance (DECIMAL), actual_balance (DECIMAL), difference (DECIMAL), total_sales (DECIMAL), total_returns (DECIMAL), total_expenses (DECIMAL), total_cash_in (DECIMAL), total_cash_out (DECIMAL), status (open, closed), opened_at, closed_at, notes, timestamps
```

### 45. `cash_register_transactions` (حركات الصندوق)
```sql
id, cash_register_id (FK), transaction_type (sale, return, expense, cash_in, cash_out, payout, payout_in), reference_type, reference_id, amount, balance_after, description, performed_by (FK), timestamps
```

### 46. `coupons` (كوبونات الخصم)
```sql
id, company_id, code (UNIQUE per company), name, type (percentage, fixed), value (DECIMAL), min_order_amount (DECIMAL), max_discount (DECIMAL), usage_limit (INT), used_count (INT DEFAULT 0), per_user_limit (INT DEFAULT 1), valid_from (TIMESTAMP), valid_until (TIMESTAMP), is_active (BOOLEAN), applicable_products (JSONB), notes, timestamps, deleted_at
```

---

## 🔵 المجموعة 8: Prescriptions (الوصفات الطبية) — **حرج للامتثال**

### 47. `prescriptions`
```sql
id                      UUID PRIMARY KEY
company_id              UUID NOT NULL
branch_id               UUID NOT NULL
patient_id              UUID NOT NULL REFERENCES customers(id)
doctor_id               UUID NULL REFERENCES doctors(id)
prescription_number     VARCHAR(50) NOT NULL
prescribed_date         DATE NOT NULL
expiry_date             DATE NOT NULL
status                  VARCHAR(50) DEFAULT 'pending' -- pending, partially_dispensed, dispensed, cancelled, expired
priority                VARCHAR(20) DEFAULT 'normal' -- normal, urgent
image_path              VARCHAR(500)
diagnosis               TEXT
notes                   TEXT
refills_allowed         INT DEFAULT 0
refills_used            INT DEFAULT 0
total_amount            DECIMAL(15,3) DEFAULT 0.000
created_by              UUID REFERENCES users(id)
dispensed_by            UUID NULL REFERENCES users(id)
dispensed_at            TIMESTAMP NULL
created_at, updated_at, deleted_at
```
**Unique**: `uniq_prescriptions_number_company` (company_id, prescription_number)

### 48. `prescription_items`
```sql
id, prescription_id (FK), product_id (FK), drug_name (VARCHAR snapshot), dosage, frequency, duration, quantity_prescribed (DECIMAL), quantity_dispensed (DECIMAL) DEFAULT 0, instructions (TEXT), substitution_allowed (BOOLEAN) DEFAULT TRUE, status (pending, partially_dispensed, dispensed, cancelled, out_of_stock), notes, timestamps
```

### 49. `prescription_refills` (إعادة التعبئة)
```sql
id, prescription_id (FK), refill_number (INT), refill_date, dispensed_by (FK), total_amount (DECIMAL), notes, timestamps
```

### 50. `controlled_substances_log` (سجل المواد الخاضعة للرقابة) — **Compliance حرج**
```sql
id, company_id, branch_id, product_id (FK), batch_number, movement_type (received, dispensed, returned, damaged, destroyed, transferred), quantity (DECIMAL), prescription_id (FK NULL), patient_id (FK NULL), doctor_id (FK NULL), performed_by (FK), performed_at, witness_id (FK NULL), notes, regulatory_reference (VARCHAR), timestamps
```
**Indexes**: `idx_controlled_log_product`, `idx_controlled_log_patient`, `idx_controlled_log_performed_at`

---

## 🔵 المجموعة 9: Financial (المالية)

### 51. `expenses` (المصروفات)
```sql
id, company_id, branch_id, expense_category_id (FK), expense_number (UNIQUE), expense_date, amount (DECIMAL), description, receipt_path, payment_method, reference_number, approved_by (FK NULL), approved_at, created_by, timestamps, deleted_at
```

### 52. `expense_categories`
```sql
id, company_id, name, description, is_active, sort_order, timestamps
```

### 53. `payment_records` (سداد الموردين/العملاء)
```sql
id, company_id, branch_id, payable_type (supplier/customer), payable_id (FK), payment_type (receivable, payable), amount, payment_method, reference_number, payment_date, notes, recorded_by (FK), timestamps
```

### 54. `accounts` (للحسابات المحاسبية)
```sql
id, company_id, account_code (UNIQUE), name, type (asset, liability, equity, revenue, expense), parent_id (FK NULL self), balance (DECIMAL), is_active, timestamps
```

### 55. `journal_entries` (القيود المحاسبية)
```sql
id, company_id, entry_number (UNIQUE), entry_date, description, reference_type, reference_id, total_debit, total_credit, status (draft, posted), created_by, timestamps
```

### 56. `journal_entry_lines`
```sql
id, journal_entry_id (FK), account_id (FK), description, debit (DECIMAL), credit (DECIMAL), timestamps
```

### 57. `tax_rates` (ضرائب)
```sql
id, company_id, name, rate (DECIMAL 5,2), is_default (BOOLEAN), is_active, timestamps
```

---

## 🔵 المجموعة 10: Insurance (التأمين)

### 58. `insurance_companies`
```sql
id, company_id, name, code (UNIQUE per company), contact_person, email, phone, address, contract_number, contract_start, contract_end, discount_percentage (DECIMAL), payment_terms, credit_limit, current_balance, is_active, notes, timestamps, deleted_at
```

### 59. `insurance_plans`
```sql
id, insurance_company_id (FK), name, code, coverage_percentage (DECIMAL), max_coverage (DECIMAL), deductible (DECIMAL), copay_percentage (DECIMAL), covered_categories (JSONB), excluded_categories (JSONB), is_active, timestamps
```

### 60. `insurance_claims`
```sql
id, company_id, claim_number (UNIQUE), sale_id (FK), insurance_company_id (FK), insurance_plan_id (FK), patient_id (FK), claim_date, amount_claimed (DECIMAL), amount_approved (DECIMAL), amount_paid (DECIMAL), status (submitted, under_review, approved, partially_approved, rejected, paid, cancelled), submitted_at, reviewed_at, resolved_at, paid_at, rejection_reason, notes, submitted_by (FK), reviewed_by (FK NULL), timestamps, deleted_at
```

### 61. `insurance_claim_items`
```sql
id, insurance_claim_id (FK), sale_item_id (FK), product_id (FK), quantity, claimed_amount, approved_amount, paid_amount, rejection_reason, timestamps
```

---

## 🔵 المجموعة 11: SaaS & Billing (الاشتراكات)

### 62. `subscription_plans`
```sql
id, name, slug (UNIQUE), description, price_monthly (DECIMAL), price_yearly (DECIMAL), currency (VARCHAR) DEFAULT 'USD', trial_days (INT) DEFAULT 14, max_branches (INT), max_users (INT), max_products (INT), max_invoices_monthly (INT), features (JSONB), is_active, sort_order, timestamps
```

### 63. `subscriptions`
```sql
id, company_id (FK), plan_id (FK), status (trialing, active, past_due, cancelled, paused), billing_cycle (monthly, yearly), current_period_start, current_period_end, trial_ends_at, cancelled_at, paused_at, stripe_subscription_id (VARCHAR NULL), stripe_customer_id (VARCHAR NULL), payment_method (stripe, paypal, manual), notes, timestamps
```

### 64. `invoices` (فواتير الاشتراك)
```sql
id, company_id (FK), subscription_id (FK), invoice_number (UNIQUE), invoice_date, due_date, subtotal, tax, total, amount_paid, status (draft, sent, paid, overdue, cancelled), pdf_path, stripe_invoice_id (VARCHAR NULL), timestamps, deleted_at
```

### 65. `invoice_items`
```sql
id, invoice_id (FK), description, quantity, unit_price, total, timestamps
```

### 66. `usage_records` (تتبع الاستخدام)
```sql
id, company_id, metric (branches_count, users_count, products_count, invoices_count, storage_mb), current_value (INT), limit_value (INT), recorded_at, timestamps
```

---

## 🔵 المجموعة 12: System (النظام)

### 67. `settings` (الإعدادات)
```sql
id, company_id (FK NULL for system-wide), branch_id (FK NULL), key (VARCHAR), value (TEXT), type (string, number, boolean, json), group (VARCHAR), description, timestamps
```
**Unique**: `uniq_settings_scope_key` (COALESCE(company_id, '0'), COALESCE(branch_id, '0'), key)

### 68. `notifications`
```sql
id (UUID PK), type (VARCHAR), notifiable_type, notifiable_id, data (JSONB), read_at (TIMESTAMP NULL), created_at
```

### 69. `activity_logs` (سجل النشاط)
```sql
id, company_id, log_name (VARCHAR), description (TEXT), subject_type, subject_id, causer_type, causer_id, properties (JSONB), event (created, updated, deleted, custom), timestamps
```

### 70. `audit_logs` (تدقيق أعمق)
```sql
id, company_id, user_id (FK NULL), table_name, record_id, action (insert, update, delete), old_values (JSONB), new_values (JSONB), url, ip_address, user_agent, timestamps
```

### 71. `announcements` (إعلانات النظام)
```sql
id, title, message (TEXT), type (info, warning, success), audience (all, companies, specific_companies), companies_ids (JSONB NULL), starts_at, ends_at, is_active, created_by, timestamps
```

### 72. `support_tickets`
```sql
id, company_id, user_id (FK), ticket_number (UNIQUE), subject, message (TEXT), priority (low, medium, high, urgent), status (open, in_progress, waiting, resolved, closed), assigned_to (FK NULL), resolved_at, closed_at, timestamps, deleted_at
```

### 73. `ticket_messages`
```sql
id, ticket_id (FK), user_id (FK NULL), message (TEXT), attachments (JSONB), is_internal (BOOLEAN), timestamps
```

---

# 🔄 ترتيب تنفيذ الـ Migrations (حسب التبعيات)

> ⚠️ **الترتيب حرج** — لا يمكن إنشاء جدول قبل جداول الـ Foreign Keys الخاصة به.

```
01. Create companies table
02. Create departments table (depends on companies)
03. Create branches table (depends on companies)
04. Create users table (depends on companies, branches, departments)
05. Create roles table (depends on companies)
06. Create permissions table
07. Create role_permission pivot
08. Create model_has_roles pivot
09. Create model_has_permissions pivot
10. Create personal_access_tokens (Sanctum)
11. Create password_reset_tokens
12. Create sessions
13. Create failed_jobs, jobs, job_batches
14. Create categories (depends on companies, self-referencing parent_id)
15. Create manufacturers (depends on companies)
16. Create tax_rates (depends on companies)
17. Create products (depends on companies, categories, manufacturers, tax_rates)
18. Create product_variants (depends on products)
19. Create product_images (depends on products)
20. Create product_price_history (depends on products, users)
21. Create drug_interactions (depends on products)
22. Create customers (depends on companies, insurance_companies — forward reference OK with deferred FK)
23. Create doctors (depends on companies)
24. Create suppliers (depends on companies)
25. Create purchase_orders (depends on companies, branches, suppliers, users)
26. Create purchase_order_items (depends on purchase_orders, products)
27. Create goods_received_notes (depends on companies, branches, suppliers, purchase_orders, users)
28. Create grn_items (depends on goods_received_notes, products)
29. Create purchase_returns (depends on companies, branches, suppliers, purchase_orders)
30. Create purchase_return_items
31. Create inventory (depends on companies, branches, products, suppliers, purchase_orders, goods_received_notes)
32. Create stock_movements (depends on companies, branches, products, users)
33. Create stock_adjustments (depends on companies, branches, users)
34. Create stock_adjustment_items (depends on stock_adjustments, products)
35. Create stock_transfers (depends on companies, branches, users)
36. Create stock_transfer_items (depends on stock_transfers, products)
37. Create stock_takes (depends on companies, branches, users)
38. Create stock_take_items
39. Create insurance_companies (depends on companies)
40. Create insurance_plans (depends on insurance_companies)
41. Create prescriptions (depends on companies, branches, customers, doctors, users)
42. Create prescription_items (depends on prescriptions, products)
43. Create prescription_refills
44. Create controlled_substances_log (depends on companies, branches, products, prescriptions, customers, doctors, users)
45. Create cash_registers (depends on companies, branches, users)
46. Create coupons (depends on companies)
47. Create sales (depends on companies, branches, cash_registers, customers, prescriptions, users, coupons)
48. Create sale_items (depends on sales, products, prescription_items)
49. Create sale_payments (depends on sales)
50. Create sale_returns (depends on companies, branches, sales, customers, users)
51. Create sale_return_items
52. Create cash_register_transactions (depends on cash_registers, users)
53. Create expense_categories (depends on companies)
54. Create expenses (depends on companies, branches, expense_categories, users)
55. Create payment_records (depends on companies, branches, users)
56. Create accounts (depends on companies, self-referencing)
57. Create journal_entries (depends on companies, users)
58. Create journal_entry_lines (depends on journal_entries, accounts)
59. Create insurance_claims (depends on companies, sales, insurance_companies, insurance_plans, customers, users)
60. Create insurance_claim_items
61. Create subscription_plans
62. Create subscriptions (depends on companies, subscription_plans)
63. Create invoices (depends on companies, subscriptions)
64. Create invoice_items
65. Create usage_records (depends on companies)
66. Create settings (depends on companies, branches)
67. Create notifications
68. Create activity_logs (depends on companies)
69. Create audit_logs (depends on companies, users)
70. Create announcements
71. Create support_tickets (depends on companies, users)
72. Create ticket_messages
```

---

# 🎯 المتطلبات الوظيفية لكل Migration

لكل migration، يجب أن تحتوي على:

1. **`up()` method**:
   - `Schema::create()` مع كل الأعمدة
   - **Foreign Keys** مع `ON DELETE` صحيح:
     - `CASCADE` للعلاقات القوية (company → branches)
     - `SET NULL` للعلاقات الاختيارية (user → branch)
     - `RESTRICT` للبيانات الحرجة (products → sales)
   - **Primary Keys** (UUID أو auto-increment)
   - **Indexes** لكل FK + حقول البحث
   - **Unique Constraints** حسب الحاجة
   - **CHECK Constraints** (e.g., `quantity >= 0`, `price >= 0`)
   - **Comments** على الجداول والأعمدة المهمة

2. **`down()` method**:
   - `Schema::dropIfExists()` بالعكس التام للترتيب

3. **مثال على نمط migration**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('manufacturer_id')->nullable();
            $table->string('name', 255);
            $table->string('generic_name', 255)->nullable();
            $table->string('brand_name', 255)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('sku', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('dosage_form', 50)->nullable();
            $table->string('strength', 100)->nullable();
            $table->string('unit_of_measure', 50)->default('piece');
            $table->integer('pack_size')->default(1);
            $table->string('pack_unit', 50)->nullable();
            $table->string('atc_code', 50)->nullable();
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled')->default(false);
            $table->string('controlled_schedule', 20)->nullable();
            $table->string('storage_conditions', 100)->nullable();
            $table->integer('min_stock_level')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('max_stock_level')->default(0);
            $table->decimal('cost_price', 12, 3)->default(0);
            $table->decimal('selling_price', 12, 3)->default(0);
            $table->decimal('wholesale_price', 12, 3)->default(0);
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('image_path', 500)->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('set null');
            $table->foreign('manufacturer_id')
                  ->references('id')->on('manufacturers')
                  ->onDelete('set null');
            $table->foreign('tax_rate_id')
                  ->references('id')->on('tax_rates')
                  ->onDelete('set null');
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'barcode'], 'uniq_products_barcode_company');
            $table->unique(['company_id', 'sku'], 'uniq_products_sku_company');

            // Indexes
            $table->index('company_id');
            $table->index('category_id');
            $table->index('manufacturer_id');
            $table->index('name');
            $table->index('generic_name');
            $table->index('requires_prescription');
            $table->index('is_controlled');
            $table->index('is_active');

            // Check Constraints
            $table->check('cost_price >= 0');
            $table->check('selling_price >= 0');
            $table->check('min_stock_level >= 0');
        });

        // Full-text Search Index (PostgreSQL-specific)
        DB::statement('
            CREATE INDEX idx_products_search ON products 
            USING GIN (to_tsvector(\'arabic\', COALESCE(name, \'\') || \' \' || COALESCE(generic_name, \'\') || \' \' || COALESCE(brand_name, \'\')))
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

---

# 🧩 المتطلبات لكل Model (Eloquent)

لكل جدول، يجب إنشاء Model يحتوي على:

1. **Properties**:
   - `$table` (اسم الجدول)
   - `$primaryKey` (إذا لم يكن `id`)
   - `$keyType = 'string'` (للـ UUID)
   - `$incrementing = false` (للـ UUID)
   - `$fillable` (كل الأعمدة القابلة للتعبئة)
   - `$guarded = []`
   - `$casts` (كل الحقول الخاصة: dates, decimals, booleans, json, arrays)
   - `$hidden` (password, tokens, etc.)
   - `$dates` / `$casts` للتواريخ

2. **Traits**:
   - `HasFactory`
   - `SoftDeletes` (للجداول التجارية)
   - `HasUuids` (للـ UUID)
   - `LogsActivity` (Spatie Activity Log)
   - `BelongsToTenant` (trait مخصص للـ Multi-tenancy)

3. **Relationships**:
   - `belongsTo`, `hasMany`, `belongsToMany`, `morphMany`, etc.
   - كل العلاقات مع eager loading hints

4. **Scopes**:
   - `scopeActive($query)` — `where('is_active', true)`
   - `scopeForCompany($query, $companyId)` — tenant isolation
   - `scopeForBranch($query, $branchId)`
   - `scopeSearch($query, $term)` — بحث شامل
   - `scopeExpiringSoon($query, $days = 90)` — للمنتجات المنتهية قريباً
   - `scopeLowStock($query)` — المخزون المنخفض
   - `scopeControlled($query)` — المواد الخاضعة للرقابة

5. **Accessors & Mutators**:
   - `getFullNameAttribute()` (لـ customers, users, doctors)
   - `getFormattedPriceAttribute()`
   - `setPasswordAttribute()` (hash تلقائي)

6. **Observers**:
   - `creating` — توليد الأرقام التسلسلية (invoice_number, po_number, etc.)
   - `created` — تسجيل النشاط
   - `updating` — حفظ الـ old values
   - `updated` — تسجيل التغييرات
   - `deleting` — soft delete logic

7. **Business Methods**:
   - `isExpired()`, `isLowStock()`, `isControlled()`, etc.
   - `calculateTotal()`, `applyDiscount()`, etc.

---

# 🌱 Seeders (البيانات التجريبية)

## Seeders المطلوبة:

1. **`SubscriptionPlanSeeder`**:
   - Free Plan (1 branch, 2 users, 100 products)
   - Starter Plan ($29/mo, 3 branches, 10 users, 1000 products)
   - Professional Plan ($79/mo, 10 branches, 50 users, 10000 products)
   - Enterprise Plan ($199/mo, unlimited)

2. **`PermissionSeeder`**:
   - كل الصلاحيات مقسمة حسب المجموعة (products.*, inventory.*, sales.*, etc.)
   - ~200+ permission

3. **`RoleSeeder`**:
   - Super Admin (system-level)
   - Company Admin
   - Branch Manager
   - Pharmacist
   - Cashier
   - Accountant
   - Stock Keeper

4. **`CompanySeeder`**:
   - 3 demo companies (صيدلية الأمل، صيدلية الصحة، صيدلية الحياة)
   - كل شركة بفروعها ومستخدميها

5. **`ProductSeeder`**:
   - 500+ منتج واقعي (أدوية شائعة في مصر/الشرق الأوسط)
   - Paracetamol, Amoxicillin, Omeprazole, etc.
   - مع generic_name, dosage_form, strength, ATC codes
   - أسعار واقعية بالجنيه المصري

6. **`CategorySeeder`**:
   - هرمي: Pain Relief > Tablets, Antibiotics > Capsules, etc.
   - ~50 category

7. **`ManufacturerSeeder`**:
   - 50+ manufacturer (Pharco, EIPICO, Amoun, etc.)

8. **`DrugInteractionSeeder`**:
   - 100+ drug interaction شائعة
   - مع severity و management

9. **`CustomerSeeder`:** 100+ عميل واقعي
10. **`DoctorSeeder`:** 30+ طبيب
11. **`SupplierSeeder`:** 20+ مورد
12. **`InventorySeeder`:** مخزون واقعي لكل فرع
13. **`SaleSeeder`:** 500+ عملية بيع (آخر 6 أشهر)
14. **`PurchaseOrderSeeder`:** 100+ أمر شراء
15. **`PrescriptionSeeder`:** 200+ وصفة طبية
16. **`InsuranceCompanySeeder`:** 5 شركات تأمين
17. **`SettingsSeeder`:** إعدادات افتراضية
18. **`TaxRateSeeder`:** VAT 14% (مصر)

## Factories المطلوبة:
- `CompanyFactory`, `BranchFactory`, `UserFactory`
- `ProductFactory`, `CategoryFactory`, `ManufacturerFactory`
- `CustomerFactory`, `DoctorFactory`, `SupplierFactory`
- `SaleFactory`, `SaleItemFactory`
- `PurchaseOrderFactory`, `InventoryFactory`
- `PrescriptionFactory`
- كل factory يجب أن تولد بيانات واقعية (Faker بالعربية + الإنجليزية)

---

# 🔗 ربط قاعدة البيانات بـ Laravel

## 1. ملف `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmacy_db
DB_USERNAME=pharmacy_user
DB_PASSWORD=strong_password_here
DB_SCHEMA=public
DB_TIMEZONE=UTC
```

## 2. ملف `config/database.php`:
```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'pharmacy_db'),
    'username' => env('DB_USERNAME', 'pharmacy_user'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
    'schema' => env('DB_SCHEMA', 'public'),
],
```

## 3. Global Scope للـ Multi-tenancy:
```php
// app/Models/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->company_id) {
            $builder->where($model->getTable() . '.company_id', auth()->user()->company_id);
        }
    }
}
```

---

# 🧪 الاختبارات المطلوبة

1. **Database Tests**:
   - كل migration تعمل بدون أخطاء
   - كل FK constraint يعمل
   - كل unique constraint يعمل
   - كل check constraint يعمل
   - Rollback يعمل

2. **Model Tests**:
   - كل relationship يعمل
   - كل scope يعمل
   - كل accessor/mutator يعمل
   - Soft deletes يعمل

3. **Seeders Tests**:
   - كل seeder يعمل
   - البيانات المولدة صحيحة
   - العلاقات سليمة

---

# 📚 التوثيق المطلوب

1. **`DATABASE_SCHEMA.md`**:
   - مخطط ERD (Mermaid diagram)
   - وصف كل جدول
   - وصف كل علاقة
   - قائمة indexes

2. **`MIGRATIONS_ORDER.md`**:
   - ترتيب التنفيذ
   - التبعيات

3. **`SEEDING_GUIDE.md`**:
   - كيفية تشغيل الـ seeders
   - البيانات التجريبية

---

# ⚠️ قواعد صارمة (Strict Rules)

1. **PostgreSQL فقط** — لا MySQL
2. **UUIDs** للجداول العامة، **Auto-increment** للجداول الداخلية
3. **Decimal** لكل المبالغ (لا float أبداً!)
4. **Soft deletes** على كل الجداول التجارية
5. **Audit trails** (`created_by`, `updated_by`) على كل الجداول
6. **company_id** على كل جدول (multi-tenancy)
7. **Indexes** على كل FK + حقول البحث
8. **Foreign keys** مع `ON DELETE` صحيح
9. **Check constraints** للقيم الموجبة
10. **Comments** على الجداول والأعمدة المهمة
11. **ترتيب migrations** حسب التبعيات
12. **لا circular dependencies**
13. **لا migrations عملاقة** — كل migration جدول واحد أو اثنين
14. **Factories** لكل model
15. **Seeders** واقعية (بيانات مصرية/عربية)
16. **Tests** لكل migration + model
17. **Documentation** كاملة

---

# 📦 المخرجات المطلوبة (Deliverables)

1. ✅ **73+ migration file** مرتبة حسب التبعيات
2. ✅ **73+ Model file** مع كل العلاقات والـ scopes
3. ✅ **73+ Factory file**
4. ✅ **18 Seeder files** واقعية
5. ✅ **DatabaseSeeder** رئيسي يشغل كل شيء بالترتيب
6. ✅ **`.env.example`** محدث
7. ✅ **`config/database.php`** محدث
8. ✅ **`DATABASE_SCHEMA.md`** مع ERD diagram
9. ✅ **`MIGRATIONS_ORDER.md`**
10. ✅ **Tests** لكل migration + model
11. ✅ **Global Scopes** للـ Multi-tenancy
12. ✅ **Observers** للتوليد التلقائي للأرقام
13. ✅ **Custom Traits** (HasUuids, BelongsToTenant, LogsActivity)

---

# 🎯 الإجراء الأول (First Action)

ابدأ بـ:

1. **تحليل الكود الحالي** — افهم البنية الموجودة
2. **إنشاء `DATABASE_SCHEMA.md`** يحتوي على:
   - ERD diagram (Mermaid)
   - وصف كل مجموعة جداول
   - العلاقات
3. **إنشاء `MIGRATIONS_ORDER.md`** بالترتيب الدقيق
4. **البدء بالمجموعة 1** (Core Multi-Tenancy):
   - `companies` migration + model
   - `branches` migration + model
   - `departments` migration + model
   - `users` migration + model
   - **Tests** لكل واحد
5. **الانتقال للمجموعة 2** (RBAC)
6. **وهكذا...** حتى المجموعة 12

**قبل أن تكتب أي سطر كود، أكد لي:**
- فهمت البنية الكاملة ✓
- لديك خطة واضحة للترتيب ✓
- ستلتزم بكل القواعد الصارمة ✓
- ستبدأ بـ `DATABASE_SCHEMA.md` أولاً ✓

ثم ابدأ بالتنفيذ.

---

# 💬 ملاحظات إضافية

- **اللغة المفضلة**: العربية (مع المصطلحات التقنية بالإنجليزية)
- **السوق المستهدف**: مصر/الشرق الأوسط — البيانات التجريبية يجب أن تكون واقعية لهذه السوق
- **الأولوية**: جودة التصميم > سرعة التنفيذ
- **لا تتردد في طرح أسئلة** إذا كان هناك غموض

---

**ابدأ الآن. أظهر لي أنك فهمت كل شيء، ثم ابدأ بـ `DATABASE_SCHEMA.md`.**