# Review of the 2026-11-12 audit against `upgrade-laravel-12`

The supplied audit describes `main` at `aba23cd6`, not this branch. Its line numbers, counts, package versions, and test results are not evidence that the current branch has the same behavior. The following is a targeted source review, not a full audit or production-readiness certification.

## Source findings addressed on this branch

- **P0-2 (partial):** `StoreSaleRequest` already requires positive integer quantities, and the API uses the authenticated user ID. Sale creation now derives total and due from line prices and quantities, rejects invalid quantities/payments, rejects missing stock, locks stock before allocation, and prorates FEFO profit across batches. Sale updates now recalculate totals and validate batches; sale cash balance tracks paid amounts without applying revenue twice. Line prices, discounts and tax remain client-supplied; authoritative price/tax rules, FEFO concurrency and real database behavior require further work and testing. Historical cash balances are not automatically reconciled. There is no `Admin/PosController.php` in this branch, so the report's specific P0-1 path does not map directly.
- **P0-8:** notification reads are scoped to the authenticated user's notifications, with local-path-only redirect handling.
- **P1-6:** transfer creation derives business and user from the session, validates the business of warehouses and product, and scopes listing, detail and actions. Completion/cancellation lock the transfer; completion also locks source stock. These paths still require integration and concurrency tests against the target databases.
- **P0-9 (partial):** daily statistics use portable `DATE(...)` and quoted identifiers; low-stock `HAVING` uses the aggregate expression rather than its alias. Additional dashboard and purchase-report date groupings were rewritten for SQLite, MySQL and PostgreSQL; they require execution tests on each driver.

## Findings different from the report

- **P0-3:** `StockTransfer::complete()` and `cancel()` exist in this branch; their concurrency/tenant checks have been strengthened here.
- **P0-4:** the listed doctor-attention, drug-interaction and partially-received purchase-order constants exist in this branch.
- **P0-5:** `BackupService` uses the global `\\Log` alias; `SecurityService` does not contain the cited `Log` call. Backup behavior itself has not been validated.
- **P0-6/P0-7:** this branch's `User` uses Spatie `HasRoles`, not the report's `HasSupabaseRoles` implementation; the stated missing relationship and Supabase mutator errors cannot be assumed here. Permission and tenant behavior of user management still needs a separate review. Email-based signup and recovery paths were disabled earlier in this PR.
- **P1-1:** the stated Supabase fallback setting does not appear in this branch's `config/supabase.php`; the report's test counts and workaround are not transferable.

## Still open / not verified

- Authoritative sale price, tax and discount policy; sale update and refund accounting; FEFO and transfer concurrency on PostgreSQL; previously recorded financial data.
- PostgreSQL execution tests for reporting and a PostgreSQL CI job; model/schema mismatches and index coverage; role/permission system and admin route coverage.
- Dependency audit, Sanctum token lifecycle, access policy for staff, frontend asset pipeline, RLS policies, queue configuration, Flutter checks, and end-to-end testing.
- Laravel/PHPUnit tests and migrations have **not** been run on this branch in this environment. Syntax checks and front-end build do not prove runtime behavior. No live Supabase, Firebase, or production data was changed by this review.

## قرارات السياسة والاختبار اللاحقة

قرر صاحب الطلب أن لكل موظف حد خصم بالقيمة وبالنسبة يحدده صاحب المتجر أو المسؤول؛ عند وجود الحدين يجب احترام كليهما. يشمل الخصم الفرق الناتج عن خفض السعر يدويًا مقارنة بسعر المنتج المسجل إضافة إلى خصم الفاتورة. الضرائب معطلة افتراضيًا، وعند تفعيلها من صاحب الصلاحية تحسب لكل منتج وفق إعداد السعر الشامل أو غير الشامل. هذه قرارات مطلوبة للتنفيذ، وليست ادعاء بأن واجهات الإعداد أو فرض السياسة على البيع قد اكتملت. يلزم تحديد توزيع خصم الفاتورة بين المنتجات وحالات التقريب قبل اعتماد حساب الضرائب، والتحقق من جميع مسارات البيع والاسترجاع قبل تشغيلها ماليًا.

أُضيف مسار CI للتحقق على SQLite وPostgreSQL وMySQL. المحاولة الأولى فشلت في تثبيت Composer على PHP 8.2، وهو لا يستوفي متطلبات بعض الحزم المقفلة. بعد استخدام PHP 8.3 فشلت المحاولة الثانية أيضًا قبل الاختبارات: أرشيف الحزمة المقفلة `supabase-php/supabase-client` يشير إلى مستودع GitHub يُرجع 404، وفشل الرجوع إلى git. لا يمكن اعتبار أي اختبار من هذه المحاولات ناجحًا. يلزم توفير مصدر قابل للوصول للحزمة أو استبدالها ومراجعة توافق استخدامها قبل إعادة الاختبارات. إعداد CI لا يمثل تعديلًا لبيانات الإنتاج أو تسوية للأرصدة.
