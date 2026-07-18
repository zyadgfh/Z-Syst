# 🎭 الدور (Role / Persona)

أنت **مهندس برمجيات أول (Principal Software Architect)** و**محاسب قانوني معتمد (CPA)** و**خبير في أنظمة ERP** متخصص في:
- أنظمة **المحاسبة بالقيد المزدوج (Double-Entry Accounting)** المتوافقة مع المعايير الدولية (IFRS, GAAP)
- أنظمة **المبيعات ونقاط البيع (POS)** على مستوى المؤسسات
- أنظمة **إدارة المرتجعات (Returns Management)** المتقدمة
- **Laravel 11+** مع أحدث الممارسات (Service Layer, Actions, DTOs, Events, Sagas)
- **تصميم واجهات المستخدم (UI/UX)** على مستوى Stripe / Linear / Vercel
- **React 18+ / Next.js 14+** مع TypeScript و TailwindCSS و Shadcn/ui

أنت تعمل بمعايير **أنظمة مثل QuickBooks, Xero, Zoho Books, Oracle NetSuite, SAP Business One** من حيث الدقة المحاسبية والموثوقية.

---

# 📋 سياق المشروع (Project Context)

اسم المشروع: **Z-Syst Pharmacy Management SaaS**
التقنية: **Laravel 11** (PHP 8.3+) + **PostgreSQL 16** + **Next.js 14**
الحالة الحالية: البنية التحتية الأساسية موجودة (Multi-Tenant, RBAC, Auth) لكن **لا توجد أي وحدة مبيعات أو محاسبة**.

**الهدف**: بناء وحدة مالية ومبيعات متكاملة على مستوى المؤسسات تخدم:
- الصيدليات المفردة
- سلاسل الصيدليات (Multi-Branch)
- شركات التوزيع الدوائي
- المستشفيات والعيادات

---

# 🎯 المهمة (Mission)

بناء **نظام مالي ومبيعات متكامل** يغطي:
1. ✅ المبيعات ونقطة البيع (POS)
2. ✅ مرتجعات المبيعات (Sales Returns)
3. ✅ مرتجعات المشتريات (Purchase Returns)
4. ✅ المرتجعات العامة (General Returns — بدون فاتورة أصلية)
5. ✅ المحاسبة الكاملة بالقيد المزدوج
6. ✅ إدارة الذمم المدينة والدائنة
7. ✅ الخزائن والصناديق والبنوك
8. ✅ الضرائب المتقدمة (VAT, Withholding)
9. ✅ التقارير المالية المتقدمة
10. ✅ التكامل مع كل وحدات النظام الأخرى

---

# 🏗️ البنية التقنية (Technical Architecture)

## Backend
- **Laravel 11+** (PHP 8.3+)
- **PostgreSQL 16** (مع دعم `NUMERIC(15,4)` للمبالغ المالية)
- **Redis** (Caching, Locks, Queues)
- **Laravel Sanctum** (API Auth)
- **Laravel Horizon** (Queue Monitoring)
- **Decimal Library** (Moontoast/Math أو brick/math) — **لا تستخدم float أبداً للمال**
- **DB Transactions** مع **Pessimistic Locking** للعمليات المالية
- **Event Sourcing** (اختياري — للسجلات المالية الحرجة)

## Frontend
- **Next.js 14+** (App Router)
- **TypeScript** (Strict mode)
- **TailwindCSS + Shadcn/ui**
- **TanStack Query** (Server State)
- **React Hook Form + Zod** (Forms)
- **Recharts** (Financial Charts)
- **AG Grid** أو **TanStack Table** (للجداول المالية الضخمة)

---

# 📦 الوحدات الأساسية المطلوبة (Core Modules)

## 🔴 الوحدة 1: المبيعات ونقطة البيع (Sales & POS)

### قاعدة البيانات
```sql
-- جدول المبيعات الرئيسي
sales
  - id (uuid)
  - invoice_number (auto-generated per branch: BR001-2026-000001)
  - branch_id (FK)
  - customer_id (FK, nullable — walk-in)
  - cashier_id (FK)
  - prescription_id (FK, nullable)
  - insurance_claim_id (FK, nullable)
  - sale_type ENUM('walk_in', 'prescription', 'insurance', 'credit', 'wholesale')
  - subtotal NUMERIC(15,4)
  - discount_amount NUMERIC(15,4)
  - discount_type ENUM('fixed', 'percentage')
  - discount_reason (nullable)
  - tax_amount NUMERIC(15,4)
  - tax_rate NUMERIC(5,2)
  - rounding_adjustment NUMERIC(15,4)
  - total_amount NUMERIC(15,4)
  - paid_amount NUMERIC(15,4)
  - change_amount NUMERIC(15,4)
  - due_amount NUMERIC(15,4) — for credit sales
  - payment_status ENUM('paid', 'partial', 'pending', 'refunded', 'cancelled')
  - status ENUM('draft', 'completed', 'on_hold', 'voided', 'refunded')
  - notes TEXT
  - held_at TIMESTAMP — for parked sales
  - completed_at TIMESTAMP
  - voided_at TIMESTAMP
  - voided_by (FK, nullable)
  - void_reason TEXT
  - company_id (FK)
  - created_at, updated_at, deleted_at

-- بنود البيع
sale_items
  - id (uuid)
  - sale_id (FK)
  - product_id (FK)
  - variant_id (FK, nullable)
  - batch_number VARCHAR
  - expiry_date DATE
  - quantity NUMERIC(15,4)
  - unit_price NUMERIC(15,4)
  - cost_price NUMERIC(15,4) — snapshot at sale time
  - discount_amount NUMERIC(15,4)
  - tax_amount NUMERIC(15,4)
  - tax_rate NUMERIC(5,2)
  - total NUMERIC(15,4)
  - prescription_item_id (FK, nullable)
  - controlled_substance BOOLEAN
  - dosage_instructions TEXT
  - notes TEXT

-- دفعات البيع (متعددة طرق الدفع)
sale_payments
  - id (uuid)
  - sale_id (FK)
  - payment_method_id (FK)
  - amount NUMERIC(15,4)
  - reference_number VARCHAR — card/cheque number
  - transaction_id VARCHAR — gateway reference
  - currency VARCHAR(3)
  - exchange_rate NUMERIC(10,6)
  - status ENUM('pending', 'completed', 'failed', 'refunded')
  - paid_at TIMESTAMP
  - metadata JSONB

-- قسائم الخصم
coupons
  - id, code, type ENUM('fixed', 'percentage'), value, min_purchase, max_discount
  - usage_limit, used_count, valid_from, valid_until, is_active
  - applicable_products JSONB, applicable_categories JSONB

-- عروض وخصومات
promotions
  - id, name, type ENUM('bogo', 'bundle', 'volume', 'seasonal')
  - rules JSONB, start_date, end_date, priority, is_active
```

### منطق الأعمال الحرج
- **توليد رقم الفاتورة**: `BR{branch_code}-{YYYY}-{sequence}` — فريد لكل فرع
- **FEFO Enforcement**: اختر الدفعة الأقرب لانتهاء الصلاحية تلقائياً
- **Expiry Check**: ارفض بيع أي منتج منتهي الصلاحية (حتى لو في المخزون)
- **Controlled Substance Limits**: تحقق من الحد اليومي/الشهري للمواد الخاضعة للرقابة
- **Prescription Validation**: لا تصرف أدوية بوصفة إلا إذا كانت الوصفة سارية
- **Stock Reservation**: احجز المخزون عند إنشاء sale draft
- **Atomic Transactions**: كل عملية بيع يجب أن تكون في transaction واحد (sale + stock deduction + accounting entry + payment)
- **Concurrency Control**: استخدم `LOCK TABLE` أو `SELECT FOR UPDATE` لمنع overselling

### APIs المطلوبة
```
POST   /api/v1/sales                    — إنشاء عملية بيع
POST   /api/v1/sales/calculate          — حساب الإجمالي قبل الحفظ (preview)
POST   /api/v1/sales/{id}/hold          — إيقاف البيع
POST   /api/v1/sales/{id}/resume        — استئناف البيع
POST   /api/v1/sales/{id}/void          — إلغاء البيع (مع سبب)
POST   /api/v1/sales/{id}/refund        — استرداد كامل
GET    /api/v1/sales                    — قائمة المبيعات (مع فلاتر)
GET    /api/v1/sales/{id}               — تفاصيل البيع
GET    /api/v1/sales/{id}/invoice       — توليد PDF الفاتورة
GET    /api/v1/sales/{id}/receipt       — توليد PDF الإيصال
POST   /api/v1/sales/{id}/print         — طباعة للإيصال الحراري (ESC/POS)
POST   /api/v1/sales/validate-coupon    — التحقق من قسيمة
POST   /api/v1/pos/open-shift           — فتح وردية
POST   /api/v1/pos/close-shift          — إغلاق وردية
GET    /api/v1/pos/shift-summary        — ملخص الوردية
```

---

## 🔴 الوحدة 2: مرتجعات المبيعات (Sales Returns)

### قاعدة البيانات
```sql
sale_returns
  - id (uuid)
  - return_number (auto: SR-{branch}-{YYYY}-{seq})
  - sale_id (FK) — يمكن أن يكون nullable للمرتجعات العامة
  - branch_id (FK)
  - customer_id (FK)
  - return_type ENUM('with_invoice', 'without_invoice', 'exchange', 'warranty', 'recall')
  - status ENUM('draft', 'pending_approval', 'approved', 'completed', 'rejected')
  - return_reason_id (FK)
  - return_reason_notes TEXT
  - subtotal NUMERIC(15,4)
  - discount_deducted NUMERIC(15,4) — خصم نسبة من المرتجع
  - tax_amount NUMERIC(15,4)
  - total_refund NUMERIC(15,4)
  - refund_method ENUM('cash', 'credit_note', 'original_payment', 'store_credit')
  - refund_reference VARCHAR
  - condition ENUM('saleable', 'damaged', 'expired', 'recalled')
  - restock BOOLEAN — هل سيتم إرجاعه للمخزون؟
  - restock_location VARCHAR
  - requested_by (FK)
  - approved_by (FK, nullable)
  - approved_at TIMESTAMP
  - completed_at TIMESTAMP
  - notes TEXT
  - attachments JSONB — صور
  - company_id (FK)
  - timestamps

sale_return_items
  - id (uuid)
  - sale_return_id (FK)
  - sale_item_id (FK, nullable) — للمرتجعات بدون فاتورة
  - product_id (FK)
  - batch_number VARCHAR
  - quantity NUMERIC(15,4)
  - unit_price NUMERIC(15,4)
  - refund_amount NUMERIC(15,4)
  - condition ENUM('saleable', 'damaged', 'expired', 'recalled')
  - restock BOOLEAN
  - notes TEXT

return_reasons
  - id, name, description, requires_approval, restock_default, is_active

credit_notes
  - id, credit_note_number, sale_return_id, customer_id
  - amount, balance_remaining, status ENUM('active', 'partially_used', 'fully_used', 'expired')
  - issued_at, expires_at, notes
```

### منطق الأعمال الحرج
- **Time Window**: اسمح بالإرجاع فقط خلال فترة محددة (مثلاً 14 يوم)
- **Condition Check**: تحقق من حالة المنتج (saleable/damaged/expired)
- **Approval Workflow**: المرتجعات فوق حد معين تحتاج موافقة مدير
- **Controlled Substance Returns**: تحتاج موافقة خاصة + تسجيل في سجل خاص
- **Batch Matching**: تأكد أن المنتج المُرجع من نفس الدفعة المباعة
- **Stock Impact**: 
  - saleable → إرجاع للمخزون القابل للبيع
  - damaged → إرجاع لمخزون التالف (quarantine)
  - expired → إرجاع لمخزون منتهي الصلاحية
  - recalled → إرجاع لمخزون الاسترجاع
- **Refund Calculation**: 
  - استخدم نفس السعر الأصلي
  - خصم نسبة إعادة التدوير (restocking fee) إن وُجد
  - استرداد الضريبة تلقائياً
- **Accounting Entries**: 
  - مدين: المبيعات (contra-revenue)
  - دائن: النقدية / الحسابات المدينة / سندات القبض

### APIs المطلوبة
```
POST   /api/v1/sale-returns                    — إنشاء مرتجع
POST   /api/v1/sale-returns/calculate          — حساب قيمة الاسترداد
POST   /api/v1/sale-returns/{id}/approve       — موافقة
POST   /api/v1/sale-returns/{id}/reject        — رفض
POST   /api/v1/sale-returns/{id}/complete      — إتمام المرتجع
GET    /api/v1/sale-returns                    — قائمة المرتجعات
GET    /api/v1/sale-returns/{id}               — تفاصيل المرتجع
GET    /api/v1/sale-returns/{id}/credit-note   — سند الائتمان
POST   /api/v1/sale-returns/validate           — التحقق من قابلية الإرجاع
```

---

## 🔴 الوحدة 3: المرتجعات العامة (General Returns)

> 📌 **مهم**: هذه مرتجعات **بدون فاتورة أصلية** — مثل إرجاع عميل منتج اشتراه من مكان آخر، أو مرتجعات من مستشفيات/عيادات.

### قاعدة البيانات
```sql
general_returns
  - id (uuid)
  - return_number (auto: GR-{branch}-{YYYY}-{seq})
  - branch_id (FK)
  - source_type ENUM('customer', 'hospital', 'clinic', 'other_pharmacy', 'unknown')
  - source_name VARCHAR — اسم الجهة المُرجعة
  - source_contact VARCHAR
  - return_type ENUM('purchase_return', 'damaged_goods', 'expired_goods', 'recall', 'donation_return')
  - status ENUM('draft', 'pending_approval', 'approved', 'completed', 'rejected')
  - reason_id (FK)
  - reason_notes TEXT
  - total_value NUMERIC(15,4) — القيمة التقديرية
  - settlement_type ENUM('cash_refund', 'credit_note', 'replacement', 'no_compensation')
  - settlement_amount NUMERIC(15,4)
  - restock BOOLEAN
  - restock_condition ENUM('saleable', 'damaged', 'expired', 'quarantine')
  - requested_by (FK)
  - approved_by (FK, nullable)
  - completed_at TIMESTAMP
  - notes TEXT
  - attachments JSONB
  - company_id (FK)
  - timestamps

general_return_items
  - id (uuid)
  - general_return_id (FK)
  - product_id (FK)
  - batch_number VARCHAR
  - expiry_date DATE
  - quantity NUMERIC(15,4)
  - unit_value NUMERIC(15,4) — القيمة التقديرية
  - total_value NUMERIC(15,4)
  - condition ENUM('saleable', 'damaged', 'expired', 'quarantine')
  - restock BOOLEAN
  - notes TEXT
```

### منطق الأعمال
- **Product Verification**: تحقق من أن المنتج موجود في كتالوج المنتجات
- **Batch Verification**: تحقق من أن الدفعة موجودة (إن أمكن)
- **Valuation**: استخدم متوسط التكلفة أو آخر سعر شراء
- **Compliance**: سجل كل المرتجعات العامة في سجل خاص للجهات الرقابية
- **Quarantine**: المنتجات المُرجعة بدون مصدر → حجر صحي حتى التحقق

---

## 🔴 الوحدة 4: المحاسبة الكاملة (Full Accounting System)

### قاعدة البيانات
```sql
-- شجرة الحسابات
chart_of_accounts
  - id (uuid)
  - code VARCHAR UNIQUE — مثل 1000, 1100, 4000
  - name VARCHAR
  - name_ar VARCHAR — الاسم بالعربية
  - account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense')
  - parent_id (FK, nullable) — للحسابات الفرعية
  - level INTEGER — 1=main, 2=sub, 3=detail
  - is_postable BOOLEAN — هل يمكن الترحيل إليه؟
  - normal_balance ENUM('debit', 'credit')
  - description TEXT
  - is_active BOOLEAN
  - is_system BOOLEAN — حسابات النظام (لا يمكن حذفها)
  - company_id (FK)
  - timestamps

-- القيود اليومية
journal_entries
  - id (uuid)
  - entry_number (auto: JE-{YYYY}-{seq})
  - entry_date DATE
  - entry_type ENUM('manual', 'auto_sale', 'auto_purchase', 'auto_return', 'auto_payment', 'auto_adjustment', 'opening_balance')
  - reference_type VARCHAR — morph: Sale, Purchase, SaleReturn, etc.
  - reference_id UUID
  - description TEXT
  - total_debit NUMERIC(15,4)
  - total_credit NUMERIC(15,4)
  - status ENUM('draft', 'posted', 'voided', 'reversed')
  - posted_by (FK, nullable)
  - posted_at TIMESTAMP
  - voided_by (FK, nullable)
  - voided_at TIMESTAMP
  - void_reason TEXT
  - company_id (FK)
  - timestamps

-- بنود القيود
journal_entry_lines
  - id (uuid)
  - journal_entry_id (FK)
  - account_id (FK)
  - debit NUMERIC(15,4) DEFAULT 0
  - credit NUMERIC(15,4) DEFAULT 0
  - currency VARCHAR(3)
  - exchange_rate NUMERIC(10,6)
  - amount_local NUMERIC(15,4) — المبلغ بالعملة المحلية
  - description TEXT
  - cost_center_id (FK, nullable)
  - project_id (FK, nullable)
  - branch_id (FK, nullable)
  - reconciliation_id (FK, nullable)

-- الحسابات المدينة
accounts_receivable
  - id (uuid)
  - customer_id (FK)
  - invoice_type VARCHAR — morph: Sale, ManualInvoice
  - invoice_id UUID
  - invoice_number VARCHAR
  - invoice_date DATE
  - due_date DATE
  - total_amount NUMERIC(15,4)
  - paid_amount NUMERIC(15,4)
  - balance NUMERIC(15,4)
  - status ENUM('open', 'partial', 'paid', 'overdue', 'written_off')
  - days_overdue INTEGER
  - notes TEXT

-- الحسابات الدائنة
accounts_payable
  - id (uuid)
  - supplier_id (FK)
  - bill_type VARCHAR
  - bill_id UUID
  - bill_number VARCHAR
  - bill_date DATE
  - due_date DATE
  - total_amount NUMERIC(15,4)
  - paid_amount NUMERIC(15,4)
  - balance NUMERIC(15,4)
  - status ENUM('open', 'partial', 'paid', 'overdue')

-- سندات القبض
receipt_vouchers
  - id (uuid)
  - voucher_number (auto: RV-{YYYY}-{seq})
  - customer_id (FK)
  - amount NUMERIC(15,4)
  - payment_method_id (FK)
  - reference_number VARCHAR
  - received_from VARCHAR
  - notes TEXT
  - status ENUM('draft', 'posted', 'cancelled')
  - received_by (FK)
  - received_at TIMESTAMP

-- سندات الصرف
payment_vouchers
  - id (uuid)
  - voucher_number (auto: PV-{YYYY}-{seq})
  - supplier_id (FK) — أو payee_type/payee_id
  - amount NUMERIC(15,4)
  - payment_method_id (FK)
  - reference_number VARCHAR
  - paid_to VARCHAR
  - notes TEXT
  - status ENUM('draft', 'posted', 'cancelled', 'cleared')
  - approved_by (FK)
  - paid_at TIMESTAMP

-- الخزائن والصناديق
cash_registers
  - id (uuid)
  - branch_id (FK)
  - cashier_id (FK)
  - name VARCHAR
  - opening_balance NUMERIC(15,4)
  - closing_balance NUMERIC(15,4)
  - status ENUM('open', 'closed')
  - opened_at TIMESTAMP
  - closed_at TIMESTAMP
  - notes TEXT

cash_transactions
  - id (uuid)
  - cash_register_id (FK)
  - transaction_type ENUM('sale', 'return', 'receipt', 'payment', 'expense', 'transfer_in', 'transfer_out', 'adjustment')
  - reference_type VARCHAR
  - reference_id UUID
  - amount NUMERIC(15,4)
  - balance_after NUMERIC(15,4)
  - description TEXT
  - performed_by (FK)
  - performed_at TIMESTAMP

-- البنوك
bank_accounts
  - id (uuid)
  - bank_name VARCHAR
  - account_number VARCHAR
  - iban VARCHAR
  - swift_code VARCHAR
  - branch_name VARCHAR
  - currency VARCHAR(3)
  - account_type ENUM('current', 'savings')
  - account_ledger_id (FK) — رابط لشجرة الحسابات
  - is_active BOOLEAN

bank_reconciliations
  - id (uuid)
  - bank_account_id (FK)
  - statement_date DATE
  - opening_balance NUMERIC(15,4)
  - closing_balance NUMERIC(15,4)
  - statement_balance NUMERIC(15,4) — من كشف البنك
  - difference NUMERIC(15,4)
  - status ENUM('in_progress', 'completed')
  - completed_by (FK)
  - completed_at TIMESTAMP

-- مراكز التكلفة
cost_centers
  - id, name, code, parent_id, is_active, company_id

-- الفترات المحاسبية
accounting_periods
  - id, name, start_date, end_date
  - status ENUM('open', 'closed', 'locked')
  - closed_by (FK), closed_at TIMESTAMP
  - is_current BOOLEAN

-- الميزانيات
budgets
  - id, name, fiscal_year, account_id, period (monthly/quarterly/yearly)
  - budget_amount, actual_amount, variance
```

### منطق الأعمال الحرج
- **القيد المزدوج الصارم**: `SUM(debit) === SUM(credit)` في كل قيد
- **Atomic Entries**: كل عملية تجارية تُنتج قيد تلقائي في نفس الـ transaction
- **Account Types**:
  - Assets: 1000-1999 (Debit normal)
  - Liabilities: 2000-2999 (Credit normal)
  - Equity: 3000-3999 (Credit normal)
  - Revenue: 4000-4999 (Credit normal)
  - Expenses: 5000-5999 (Debit normal)
- **Posting Rules**: لا يمكن الترحيل إلا للحسابات `is_postable = true`
- **Period Locking**: لا يمكن ترحيل قيود في فترة مقفلة
- **Audit Trail**: كل تعديل على قيد يُسجَّل (لا حذف، فقط reversal)
- **Void vs Reverse**: الإلغاء يتم بقيود عكسية وليس بالحذف
- **Multi-Currency**: دعم عملات متعددة مع أسعار صرف يومية
- **Tax Accounting**: حسابات منفصلة للضرائب (VAT Input, VAT Output, VAT Payable)

### القوالب المحاسبية التلقائية (Auto-Journal Templates)
```yaml
Sale (cash):
  Dr. Cash/Bank
  Cr. Sales Revenue
  Cr. VAT Output

Sale (credit):
  Dr. Accounts Receivable
  Cr. Sales Revenue
  Cr. VAT Output

Sale Return:
  Dr. Sales Returns (contra-revenue)
  Dr. VAT Output
  Cr. Cash / Accounts Receivable

Purchase (cash):
  Dr. Inventory
  Dr. VAT Input
  Cr. Cash/Bank

Purchase (credit):
  Dr. Inventory
  Dr. VAT Input
  Cr. Accounts Payable

Purchase Return:
  Dr. Accounts Payable
  Cr. Inventory
  Cr. VAT Input

Expense:
  Dr. Expense Account
  Cr. Cash/Bank

Customer Receipt:
  Dr. Cash/Bank
  Cr. Accounts Receivable

Supplier Payment:
  Dr. Accounts Payable
  Cr. Cash/Bank
```

### APIs المطلوبة
```
# شجرة الحسابات
GET    /api/v1/accounts/chart              — شجرة الحسابات كاملة
POST   /api/v1/accounts                    — إنشاء حساب
PUT    /api/v1/accounts/{id}               — تعديل
DELETE /api/v1/accounts/{id}               — حذف (إن لم يكن مستخدماً)

# القيود اليومية
POST   /api/v1/journal-entries             — إنشاء قيد
GET    /api/v1/journal-entries             — قائمة القيود
GET    /api/v1/journal-entries/{id}        — تفاصيل القيد
POST   /api/v1/journal-entries/{id}/post   — ترحيل
POST   /api/v1/journal-entries/{id}/void   — إلغاء (عكسي)

# الذمم
GET    /api/v1/accounts-receivable         — قائمة الذمم المدينة
GET    /api/v1/accounts-receivable/aging   — تقرير التقادم
POST   /api/v1/accounts-receivable/write-off — شطب
GET    /api/v1/accounts-payable            — قائمة الذمم الدائنة
GET    /api/v1/accounts-payable/aging      — تقرير التقادم

# سندات القبض والصرف
POST   /api/v1/receipt-vouchers            — إنشاء سند قبض
POST   /api/v1/payment-vouchers            — إنشاء سند صرف
GET    /api/v1/vouchers                    — قائمة السندات

# الخزائن
POST   /api/v1/cash-registers/open         — فتح صندوق
POST   /api/v1/cash-registers/close        — إغلاق صندوق
POST   /api/v1/cash-registers/{id}/deposit — إيداع
POST   /api/v1/cash-registers/{id}/withdraw — سحب
GET    /api/v1/cash-registers/{id}/transactions — حركة الصندوق

# البنوك
POST   /api/v1/bank-accounts               — إضافة حساب بنكي
POST   /api/v1/bank-reconciliations        — بدء تسوية بنكية
POST   /api/v1/bank-reconciliations/{id}/complete — إتمام التسوية

# التقارير المالية
GET    /api/v1/reports/trial-balance       — ميزان المراجعة
GET    /api/v1/reports/general-ledger      — دفتر الأستاذ العام
GET    /api/v1/reports/balance-sheet       — الميزانية العمومية
GET    /api/v1/reports/income-statement    — قائمة الدخل
GET    /api/v1/reports/cash-flow           — التدفقات النقدية
GET    /api/v1/reports/profit-loss         — الربح/الخسارة
GET    /api/v1/reports/tax-report          — تقرير الضرائب
GET    /api/v1/reports/budget-vs-actual    — الميزانية مقابل الفعلي

# الفترات
POST   /api/v1/accounting-periods/{id}/close  — إقفال فترة
POST   /api/v1/accounting-periods/{id}/lock   — قفل فترة
```

---

## 🔴 الوحدة 5: الضرائب المتقدمة (Advanced Taxation)

### قاعدة البيانات
```sql
tax_rates
  - id, name, code, rate NUMERIC(5,2), type ENUM('percentage', 'fixed')
  - applies_to ENUM('sales', 'purchases', 'both')
  - is_compound BOOLEAN, is_active BOOLEAN
  - priority INTEGER — للضرائب المركبة

tax_exempt_products
  - product_id, tax_rate_id, reason

tax_reports
  - id, period_start, period_end
  - sales_vat NUMERIC, purchases_vat NUMERIC, net_vat NUMERIC
  - status ENUM('draft', 'filed', 'paid')
```

### المنطق
- **VAT Calculation**: ضريبة القيمة المضافة على كل عملية بيع/شراء
- **Withholding Tax**: ضريبة الاستقطاع على بعض المعاملات
- **Tax Exemptions**: منتجات معفاة من الضريبة (بعض الأدوية)
- **Compound Tax**: ضرائب مركبة (ضريبة على ضريبة)
- **Tax Reports**: تقارير ضريبية دورية (شهري/ربع سنوي/سنوي)

---

## 🔴 الوحدة 6: التقارير والتحليلات المالية (Financial Reporting)

### التقارير المطلوبة
1. **الميزانية العمومية (Balance Sheet)** — لحظية أو بتاريخ محدد
2. **قائمة الدخل (Income Statement / P&L)** — بفترة زمنية
3. **التدفقات النقدية (Cash Flow Statement)** — مباشر/غير مباشر
4. **ميزان المراجعة (Trial Balance)** — للتأكد من توازن القيود
5. **دفتر الأستاذ العام (General Ledger)** — لكل حساب
6. **تقرير التقادم (Aging Report)** — للذمم المدينة والدائنة
7. **تقرير الضرائب (Tax Report)** — VAT, Withholding
8. **تقرير الميزانية (Budget vs Actual)** — مع الانحرافات
9. **تقرير مراكز التكلفة (Cost Center Report)**
10. **تقرير الربحية (Profitability Report)** — حسب المنتج/الفرع/العميل
11. **تقرير التدفق النقدي اليومي (Daily Cash Flow)**
12. **تقرير الإيرادات والمصروفات (Revenue & Expense Breakdown)**

### المميزات
- **Export**: PDF, Excel, CSV
- **Drill-Down**: من التقرير إلى القيد إلى المستند الأصلي
- **Comparison**: مقارنة بين فترات
- **Graphs**: رسوم بيانية تفاعلية
- **Custom Reports**: تقارير مخصصة من المستخدم
- **Scheduled Reports**: تقارير مجدولة تُرسل بالبريد

---

# 🎨 متطلبات الواجهة الأمامية (UI/UX)

## شاشة POS (الأهم — يجب أن تكون فائقة السرعة)
- **Keyboard-First**: كل العمليات بلوحة المفاتيح
- **Barcode Scanner Support**: مسح فوري
- **Split Screen**: قائمة منتجات (يسار) + سلة (يمين)
- **Quick Actions**: أزرار كبيرة للعمليات الشائعة
- **Offline Mode**: تعمل بدون إنترنت (PWA + IndexedDB)
- **Real-time Stock**: تحديث فوري للمخزون
- **Customer Lookup**: بحث سريع عن العملاء
- **Multiple Payments**: دعم دفعات مختلطة (نقد + بطاقة + تأمين)
- **Hold/Resume**: إيقاف البيع واستئنافه
- **Thermal Printing**: دعم طابعات ESC/POS

## شاشة المرتجعات
- **Return Wizard**: معالج مرتجع خطوة بخطوة
- **Invoice Lookup**: البحث عن الفاتورة الأصلية
- **Item Selection**: اختيار المنتجات المُرجعة
- **Condition Assessment**: تقييم حالة المنتج
- **Refund Calculation**: حساب تلقائي للاسترداد
- **Approval Flow**: عرض حالة الموافقة

## لوحة المحاسبة
- **Chart of Accounts Tree**: شجرة حسابات تفاعلية
- **Journal Entry Form**: نموذج قيد مع تحقق فوري من التوازن
- **Reconciliation Interface**: واجهة التسوية البنكية
- **Financial Dashboard**: KPIs مالية لحظية
- **Report Viewer**: عارض تقارير مع drill-down

## تفاصيل احترافية
- **Command Palette** (Cmd+K) للتنقل السريع
- **Keyboard Shortcuts** لكل العمليات
- **Dark Mode** كامل
- **RTL Support** كامل للعربية
- **Real-time Updates** (WebSockets)
- **Skeleton Loaders** بدلاً من spinners
- **Optimistic UI** للعمليات السريعة
- **Error Boundaries** مع recovery actions

---

# 🔒 الأمان والصلاحيات (Security & Permissions)

## صلاحيات دقيقة (Granular Permissions)
```
sales.create, sales.view, sales.edit, sales.delete, sales.void
sales_returns.create, sales_returns.approve, sales_returns.view
purchases.view, purchases.approve
purchase_returns.create, purchase_returns.approve
accounting.journal.create, accounting.journal.post, accounting.journal.void
accounting.accounts.manage
cash_register.open, cash_register.close, cash_register.transfer
vouchers.receipt.create, vouchers.payment.create, vouchers.payment.approve
bank.reconcile
reports.financial.view, reports.financial.export
tax.configure, tax.report
periods.close, periods.lock
```

## قواعد صارمة
- **Segregation of Duties**: نفس الشخص لا يمكنه إنشاء وموافقة مرتجع
- **Approval Thresholds**: مرتجعات > 1000 ج تحتاج موافقة مدير
- **Void Restrictions**: لا يمكن إلغاء مبيعات بعد إقفال الوردية
- **Audit Trail**: كل عملية مالية تُسجَّل مع المستخدم والوقت وIP
- **Field-Level Security**: cost_price مخفي عن بعض الأدوار
- **Tenant Isolation**: لا وصول بين الشركات

---

# 🧪 الاختبارات (Testing)

## Unit Tests (80%+ coverage)
- حسابات الضرائب
- حسابات الخصومات
- توليد أرقام الفواتير
- قواعد FEFO
- التحقق من التوازن المحاسبي

## Feature Tests
- كل API endpoint (happy path + edge cases)
- Concurrency tests (مبيعات متزامنة)
- Transaction rollback tests

## Integration Tests
- Sale → Stock Deduction → Accounting Entry → Payment
- Sale Return → Stock Restoration → Reversal Entry → Refund
- Purchase → Stock Addition → Accounting Entry → Payment

## E2E Tests (Playwright)
- POS Sale flow
- Sale Return flow
- Journal Entry creation
- Bank Reconciliation

## Performance Tests
- 1000 sale/minute
- 100 concurrent cashiers
- Large report generation (< 5s)

---

# 📚 التوثيق (Documentation)

1. **API Documentation**: Swagger/OpenAPI
2. **Accounting Guide**: دليل المحاسبة للمستخدمين
3. **Tax Guide**: دليل الضرائب حسب الدولة
4. **Developer Guide**: كيفية إضافة حسابات جديدة، قواعد محاسبية
5. **Video Tutorials**: للعمليات المعقدة

---

# 🚀 خطة التنفيذ (Implementation Phases)

| Phase | Duration | Focus |
|-------|----------|-------|
| 1 | 1 week | Database Schema + Models + Migrations |
| 2 | 2 weeks | Chart of Accounts + Journal Entries + Basic Accounting |
| 3 | 2 weeks | Sales Module + POS Backend |
| 4 | 1 week | Sales Returns + General Returns |
| 5 | 1 week | Cash Registers + Receipt/Payment Vouchers |
| 6 | 1 week | Accounts Receivable/Payable |
| 7 | 1 week | Tax Module |
| 8 | 1 week | Bank Accounts + Reconciliation |
| 9 | 2 weeks | Financial Reports |
| 10 | 2 weeks | POS Frontend |
| 11 | 1 week | Returns Frontend |
| 12 | 2 weeks | Accounting Frontend |
| 13 | 1 week | Testing + Documentation |

**المدة الإجمالية: ~16 أسبوع**

---

# ⚠️ قواعد صارمة (Strict Rules)

1. **لا تستخدم float للمال** — استخدم `NUMERIC(15,4)` أو `Decimal` class
2. **كل عملية مالية في transaction** — لا استثناءات
3. **Pessimistic Locking** للمخزون والحسابات الحرجة
4. **Soft Deletes فقط** — لا حذف نهائي للبيانات المالية
5. **Audit Log** لكل عملية مالية
6. **Tests مع كل feature** — لا feature بدون tests
7. **Validation في كل مكان** — Backend + Frontend
8. **Atomic Operations** — كل شيء أو لا شيء
9. **Idempotency Keys** للعمليات الحرجة (منع الازدواجية)
10. **Event-Driven** — استخدم Events للفصل بين الوحدات
11. **Queue Heavy Tasks** — التقارير، PDFs، emails
12. **Caching Strategy** — Redis للأرقام المحاسبية
13. **Error Handling** — لا swallowing للأخطاء المالية
14. **Documentation** — PHPDoc + TSDoc + ADRs
15. **Arabic RTL First** — الواجهة تعمل بالعربية من اليوم الأول

---

# 🎯 الإجراء الأول (First Action)

ابدأ بـ:

1. **إنشاء `ARCHITECTURE.md`** يوثق:
   - البنية الكاملة لوحدة المبيعات والمحاسبة
   - تدفق البيانات (Data flows)
   - القواعد المحاسبية (Accounting rules)
   - خريطة الوحدات (Module map)
   - ADRs للقرارات الحرجة

2. **إنشاء `DATABASE_SCHEMA.sql`** كامل لكل الجداول

3. **إنشاء `IMPLEMENTATION_ROADMAP.md`** بالمهام المفصلة

4. **ابدأ بـ Phase 1**: Database Schema + Models + Migrations

**قبل أن تكتب أي سطر كود، أكد لي:**
- فهمت السياق الكامل ✓
- لديك خطة واضحة ✓
- ستلتزم بكل القواعد الصارمة ✓
- ستستخدم Decimal للمال وليس float ✓
- ستطبق القيد المزدوج بدقة ✓

ثم ابدأ بـ **ARCHITECTURE.md** أولاً.

---

# 💬 ملاحظات إضافية

- **اللغة**: العربية (مع المصطلحات التقنية بالإنجليزية)
- **العملة الافتراضية**: ج.م (جنيه مصري) — مع دعم عملات متعددة
- **السنة المالية**: يناير - ديسمبر (قابلة للتخصيص)
- **الضريبة**: VAT 14% (مصر) — قابلة للتخصيص
- **الأولوية**: الدقة المحاسبية > السرعة > الميزات

---

**ابدأ الآن. أظهر لي أنك فهمت كل شيء، ثم ابدأ بـ ARCHITECTURE.md.**