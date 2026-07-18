# 📋 ترتيب الـ Migrations بدقة

> ⚠️ **الترتيب حرج** — لا يمكن إنشاء جدول قبل جداول الـ Foreign Keys الخاصة به.

## 🔵 المجموعة 1: Core Multi-Tenancy (0001)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 01 | `2026_07_18_000001_create_companies_table.php` | جدول الشركات | لا شيء |
| 02 | `2026_07_18_000002_create_users_table.php` | جدول المستخدمين | companies |
| 03 | `2026_07_18_000003_create_branches_table.php` | جدول الفروع | companies, users |
| 04 | `2026_07_18_000004_create_departments_table.php` | جدول الأقسام | branches, users |

## 🔵 المجموعة 2: RBAC & Security (0002)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 05 | `2026_07_18_000005_create_roles_table.php` | الأدوار | companies |
| 06 | `2026_07_18_000006_create_permissions_table.php` | الصلاحيات | لا شيء |
| 07 | `2026_07_18_000007_create_role_permission_table.php` | Pivot | roles, permissions |
| 08 | `2026_07_18_000008_create_model_has_roles_table.php` | Pivot Spatie | roles, users |
| 09 | `2026_07_18_000009_create_model_has_permissions_table.php` | Pivot Spatie | permissions, users |
| 10 | `2026_07_18_000010_create_personal_access_tokens_table.php` | Sanctum Tokens | users |
| 11 | `2026_07_18_000011_create_password_reset_tokens_table.php` | إعادة تعيين كلمة المرور | لا شيء |
| 12 | `2026_07_18_000012_create_sessions_table.php` | الجلسات | users |
| 13 | `2026_07_18_000013_create_failed_jobs_table.php` | Jobs الفاشلة | لا شيء |
| 14 | `2026_07_18_000014_create_jobs_table.php` | Queue Jobs | لا شيء |
| 15 | `2026_07_18_000015_create_job_batches_table.php` | Job Batches | لا شيء |

## 🔵 المجموعة 3: Product Catalog (0003)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 16 | `2026_07_18_000016_create_categories_table.php` | تصنيفات المنتجات | companies, self-reference |
| 17 | `2026_07_18_000017_create_manufacturers_table.php` | الشركات المصنعة | companies |
| 18 | `2026_07_18_000018_create_tax_rates_table.php` | أسعار الضرائب | companies |
| 19 | `2026_07_18_000019_create_products_table.php` | المنتجات/الأدوية | companies, categories, manufacturers, tax_rates |
| 20 | `2026_07_18_000020_create_product_variants_table.php` | متغيرات المنتجات | products |
| 21 | `2026_07_18_000021_create_product_images_table.php` | صور المنتجات | products |
| 22 | `2026_07_18_000022_create_product_price_history_table.php` | تاريخ الأسعار | products, users |
| 23 | `2026_07_18_000023_create_drug_interactions_table.php` | تفاعلات الأدوية | products, self |

## 🔵 المجموعة 4: CRM (0004)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 24 | `2026_07_18_000024_create_insurance_companies_table.php` | شركات التأمين | companies |
| 25 | `2026_07_18_000025_create_customers_table.php` | العملاء/المرضى | companies, insurance_companies |
| 26 | `2026_07_18_000026_create_doctors_table.php` | الأطباء | companies |

## 🔵 المجموعة 5: Supply Chain (0005)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 27 | `2026_07_18_000027_create_suppliers_table.php` | الموردون | companies |
| 28 | `2026_07_18_000028_create_purchase_orders_table.php` | أوامر الشراء | companies, branches, suppliers, users |
| 29 | `2026_07_18_000029_create_purchase_order_items_table.php` | بنود أوامر الشراء | purchase_orders, products |
| 30 | `2026_07_18_000030_create_goods_received_notes_table.php` | إذونات الاستلام | companies, branches, suppliers, purchase_orders, users |
| 31 | `2026_07_18_000031_create_grn_items_table.php` | بنود GRN | goods_received_notes, products |
| 32 | `2026_07_18_000032_create_purchase_returns_table.php` | مرتجعات المشتريات | companies, branches, suppliers |
| 33 | `2026_07_18_000033_create_purchase_return_items_table.php` | بنود المرتجعات | purchase_returns, products |

## 🔵 المجموعة 6: Inventory (0006)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 34 | `2026_07_18_000034_create_inventory_table.php` | المخزون الحالي | companies, branches, products, suppliers |
| 35 | `2026_07_18_000035_create_stock_movements_table.php` | حركات المخزون | companies, branches, products, users |
| 36 | `2026_07_18_000036_create_stock_adjustments_table.php` | تسويات المخزون | companies, branches, users |
| 37 | `2026_07_18_000037_create_stock_adjustment_items_table.php` | بنود التسوية | stock_adjustments, products |
| 38 | `2026_07_18_000038_create_stock_transfers_table.php` | تحويلات المخزون | companies, branches, users |
| 39 | `2026_07_18_000039_create_stock_transfer_items_table.php` | بنود التحويل | stock_transfers, products |
| 40 | `2026_07_18_000040_create_stock_takes_table.php` | عمليات الجرد | companies, branches, users |
| 41 | `2026_07_18_000041_create_stock_take_items_table.php` | بنود الجرد | stock_takes, products |

## 🔵 المجموعة 7: Prescriptions (0007)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 42 | `2026_07_18_000042_create_prescriptions_table.php` | الوصفات الطبية | companies, branches, customers, doctors, users |
| 43 | `2026_07_18_000043_create_prescription_items_table.php` | بنود الوصفات | prescriptions, products |
| 44 | `2026_07_18_000044_create_prescription_refills_table.php` | إعادة التعبئة | prescriptions, users |
| 45 | `2026_07_18_000045_create_controlled_substances_log_table.php` | سجل المواد الخاضعة للرقابة | companies, branches, products, prescriptions, customers, doctors, users |

## 🔵 المجموعة 8: Sales & POS (0008)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 46 | `2026_07_18_000046_create_coupons_table.php` | كوبونات الخصم | companies |
| 47 | `2026_07_18_000047_create_cash_registers_table.php` | الصناديق النقدية | companies, branches, users |
| 48 | `2026_07_18_000048_create_sales_table.php` | عمليات البيع | companies, branches, cash_registers, customers, prescriptions, users, coupons |
| 49 | `2026_07_18_000049_create_sale_items_table.php` | بنود المبيعات | sales, products, prescription_items |
| 50 | `2026_07_18_000050_create_sale_payments_table.php` | مدفوعات متعددة | sales |
| 51 | `2026_07_18_000051_create_sale_returns_table.php` | مرتجعات المبيعات | companies, branches, sales, customers, users |
| 52 | `2026_07_18_000052_create_sale_return_items_table.php` | بنود المرتجعات | sale_returns, products |
| 53 | `2026_07_18_000053_create_cash_register_transactions_table.php` | حركات الصندوق | cash_registers, users |

## 🔵 المجموعة 9: Financial (0009)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 54 | `2026_07_18_000054_create_expense_categories_table.php` | فئات المصروفات | companies |
| 55 | `2026_07_18_000055_create_expenses_table.php` | المصروفات | companies, branches, expense_categories, users |
| 56 | `2026_07_18_000056_create_payment_records_table.php` | سداد الموردين/العملاء | companies, branches, users |
| 57 | `2026_07_18_000057_create_accounts_table.php` | الحسابات المحاسبية | companies, self-reference |
| 58 | `2026_07_18_000058_create_journal_entries_table.php` | القيود المحاسبية | companies, users |
| 59 | `2026_07_18_000059_create_journal_entry_lines_table.php` | بنود القيود | journal_entries, accounts |

## 🔵 المجموعة 10: Insurance (0010)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 60 | `2026_07_18_000060_create_insurance_plans_table.php` | خطط التأمين | insurance_companies |
| 61 | `2026_07_18_000061_create_insurance_claims_table.php` | مطالبات التأمين | companies, sales, insurance_companies, insurance_plans, customers, users |
| 62 | `2026_07_18_000062_create_insurance_claim_items_table.php` | بنود المطالبات | insurance_claims, sale_items, products |

## 🔵 المجموعة 11: SaaS & Billing (0011)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 63 | `2026_07_18_000063_create_subscription_plans_table.php` | خطط الاشتراك | لا شيء |
| 64 | `2026_07_18_000064_create_subscriptions_table.php` | الاشتراكات | companies, subscription_plans |
| 65 | `2026_07_18_000065_create_invoices_table.php` | فواتير الاشتراك | companies, subscriptions |
| 66 | `2026_07_18_000066_create_invoice_items_table.php` | بنود الفواتير | invoices |
| 67 | `2026_07_18_000067_create_usage_records_table.php` | سجلات الاستخدام | companies |

## 🔵 المجموعة 12: System (0012)

| الترتيب | اسم الملف | الوصف | التبعيات |
|---------|----------|-------|----------|
| 68 | `2026_07_18_000068_create_settings_table.php` | الإعدادات | companies, branches |
| 69 | `2026_07_18_000069_create_notifications_table.php` | الإشعارات | لا شيء |
| 70 | `2026_07_18_000070_create_activity_logs_table.php` | سجل النشاط | companies |
| 71 | `2026_07_18_000071_create_audit_logs_table.php` | التدقيق | companies, users |
| 72 | `2026_07_18_000072_create_announcements_table.php` | الإعلانات | users |
| 73 | `2026_07_18_000073_create_support_tickets_table.php` | تذاكر الدعم | companies, users |
| 74 | `2026_07_18_000074_create_ticket_messages_table.php` | رسائل التذاكر | support_tickets, users |

---

## 📝 ملاحظات التنفيذ

### ✅ القاعدة الفعلية:
- **74 migration file** كما هو مطلوب
- **PostgreSQL 16** مع JSONB, Full-text Search
- **UUIDs** للجداول العامة
- **Soft Deletes** على كل الجداول التجارية
- **Audit Trail** (created_by, updated_by, deleted_by)
- **Indexes** محسّنة على كل FK + حقول البحث
- **Check Constraints** للقيم الموجبة

### ⚠️ التبعيات الحرجة:
- `companies` يجب أن يكون أولاً (كل شيء يعتمد عليه)
- `users` ثانياً (roles, permissions, sessions, tokens)
- `branches` ثالثاً (sales, purchase_orders, inventory)
- `products` قبل inventory و sale_items