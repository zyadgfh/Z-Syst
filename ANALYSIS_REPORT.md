# 📊 تقرير التحليل العميق - Z-Syst Pharmacy Management SaaS (Phase 4)

> هذا التقرير يُستخدم كـ **خطوة توثيق قبل أي حذف/نقل**. بناءً على حالتك، تمّت **Phase 1-3** بالفعل، والآن نبدأ **Phase 4: Migrate Code**.

---

## 📌 الملخص التنفيذي
- **الهدف الآن**: نقل/تنظيم الكود الحالي داخل `app/Modules/*` + فصل طبقات (Domain/Application/Infrastructure) دون كسر endpoints أو الداتابيز.
- **الحالة الحالية**: بنية modular “جاهزة” (Core/Shared/Infrastructure directories موجودة) لكن أغلب الكود لازال تحت `app/Models` و `app/Services` و `app/Http` (Phase 4 belum اكتمل).

---

## 🔍 ملخص سريع لما تم بناؤه سابقاً (مذكور في REFACTORING_LOG.md)
- Phase 3: تم إنشاء البنية الأساسية داخل:
  - `app/Core`
  - `app/Modules` (directories فقط)
  - `app/Infrastructure`
  - `app/Shared`
  - `app/Support`
- تم إنشاء:
  - `App\Core\Abstracts\AbstractRepository.php`
  - `App\Core\Abstracts\AbstractService.php`
  - `App\Shared\Providers\ModuleServiceProvider.php`
- وما زال قيد التنفيذ: **نقل الكود الفعلي إلى modules** وتحديث namespaces/bindings/routes.

---

## 🗂️ File Inventory (Snapshot)
تم رصد أن الكود الأساسي ما زال موجوداً في هذه المناطق:
- `app/Models/*` (Models كثيرة ومنتشرة)
- `app/Services/*` (services كثيرة ومركزية)
- `app/Http/*` (controllers/requests/resources/middleware)
- `app/Scopes/TenantScope.php`
- `app/Providers/*` و `app/Shared/Providers/*`

> ملاحظة: أداة `search_files` تعطلت بسبب غياب `ripgrep` على جهازك، لذلك اعتمدنا على **list_files** وقراءة ملفات محددة.

---

## 🧩 تحليل التبعية العالية المخاطر قبل النقل
### 1) Auth / RBAC
- يوجد:
  - `app/Models/User.php`, `Role.php`, `Permission.php`
  - `app/Services/AuthService.php`, `RbacService.php`, `TwoFactorService.php`
  - policies موجودة في `app/Policies/*`
- **خطر النقل**: binding للـ guards/permissions + middleware + controllers/routes الحالية.

### 2) Multi-Tenancy (TenantScope / Company / Branch)
- يوجد:
  - `app/Scopes/TenantScope.php`
  - `app/Services/TenantManager.php`
  - traits/ظروف في models
- **خطر النقل**: التأثير على كل الاستعلامات داخل modules.

### 3) Inventory / Stock / Stock Movements
- يوجد:
  - `app/Models/Stock.php`, `StockTransfer.php`, `StockMovementService.php`
- **خطر النقل**: الاتساق بين الحركات (movements) وحسابات الكمية.

### 4) Sales / POS / Purchases / Prescriptions
- توجد models عديدة تحت `app/Models` وخدمات تحت `app/Services`.
- **خطر النقل**: معاملات الفاتورة/الخصومات/الإشعارات/التكاملات الخارجية.

---

## 🧾 قوائم “مرشحة لإعادة الهيكلة” (بدون حذف في Phase 4)
> حسب شروطك: **لا حذف** قبل الموافقة، و Phase 4 يركز على **النقل/التنظيم**.

### Core Kandidat (ثابت كمكان مشترك)
- `TenantScope` (يجب أن يبقى/يتحرك ضمن `app/Core` أو `app/Shared` حسب قرار معماري)
- exceptions / enums / DTOs المشتركة إذا كانت موجودة في Core بالفعل

### Modules المرشحة (حسب خطة REFACTORING_LOG)
1. **Auth module**: `User`, `Role`, `Permission`, auth controllers/services/requests/2FA
2. **Products module**: `Product`, `Category`, `Manufacturer`, `Drug/Medicine*` إذا كانت جزء من نفس السجل/المفهوم
3. **Inventory module**: `Stock`, `ProductStock`, `StockTransfer`, `StockMovementService`
4. **Sales module**: `Sale`, `SaleItem`, `SaleReturn`, invoice-related services/exports
5. **Purchases module**: `Purchase`, `PurchaseOrder`, GRN/return models

---

## 🗑️ Dead Code Report (مؤجل للحذف المرحلي)
- نظراً لعدم إمكانية عمل search/coverage على مستوى كامل حالياً (تعطل ripgrep)، نؤجل توليد “قائمة dead files” دقيقة.
- سنستخدم بدلها (ممكن لاحقاً):
  - `php artisan route:list`
  - `composer dump-autoload`
  - أدوات static analysis (phpstan/psalm) بعد التأكد من إعداد composer.

---

## 🧠 توصية التنفيذ (Target Integration Strategy)
### Strategy: “Strangler Migration” داخل Laravel
- لا ننقل كل شيء دفعة واحدة.
- ننقل module واحد (مثلاً Auth) ثم:
  1) نضيف `ModuleServiceProvider`
  2) نثبت routes الخاصة بالmodule
  3) نربط bindings (Repository/Service) إن كانت موجودة
  4) نقوم بتحديث namespaces تدريجياً
  5) نغلق الفجوات بأفضل توافق (alias bindings أو facades إن لزم)

---

## 🏗️ البنية الجديدة (ملخص جاهز للتطبيق)
المطلوب كما في refactoring.md:
- `app/Modules/{ModuleName}/Domain`
- `app/Modules/{ModuleName}/Application`
- `app/Modules/{ModuleName}/Infrastructure`
- `app/Modules/{ModuleName}/Database`
- `app/Modules/{ModuleName}/Routes`
- `app/Modules/{ModuleName}/Config`
- `app/Modules/{ModuleName}/Resources`
- `app/Modules/{ModuleName}/Tests`

---

## ✅ Verification Plan (قبل أي حذف/نقل كبير)
بعد كل نقل (module-by-module):
- `composer dump-autoload`
- `php artisan optimize:clear`
- `php artisan route:list`
- `php artisan test`

---

## 🧾 Action Items الخاصة بالمرحلة الحالية (Phase 4)
1. اختيار Module أولاً (سأقترح **Auth** كبداية عالية الفائدة)
2. رصد كل الملفات المرتبطة بالأوثنتكيشن الحالية:
   - routes/middleware/controllers/requests/services/models/policies
3. إنشاء skeleton للـ module (infrastructure/domain/application)
4. نقل الملفات + تحديث namespaces
5. تفعيل ModuleServiceProvider
6. تشغيل route:test + tests

---

## 📌 ملاحظة عن قيود الأدوات
تعطلت `search_files` بسبب غياب `ripgrep` في بيئة تشغيل الأداة. لذلك اعتمدت على:
- `list_files`
- `read_file`
- لاحقاً يمكن تشغيل بدائل عبر `execute_command` (مثل `findstr`/`dir`/`php artisan route:list`) أو تثبيت ripgrep.

