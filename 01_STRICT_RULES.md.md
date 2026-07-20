# 🚫 القواعد الصارمة والممنوعات

> هذه القواعد **غير قابلة للتفاوض**. أي مخالفة = إعادة العمل كاملاً.

## 🔴 الممنوعات المطلقة (HARD NO)

### 1. ممنوع التكرار (DRY Violation)
- ❌ لا تكتب نفس المنطق في مكانين
- ❌ لا تكرر استعلامات Eloquent — استخدم Scopes و Repositories
- ❌ لا تكرر Validation Rules — استخدم Form Requests
- ❌ لا تكرر Business Logic — ضعها في Service Layer

### 2. ممنوع كسر البنية الحالية
- ❌ لا تعدل `Company.php`, `Branch.php`, `User.php` بدون إذن صريح
- ❌ لا تحذف أي Migration موجودة
- ❌ لا تغير أسماء الجداول الموجودة
- ✅ فقط **أضف** جداول جديدة أو **وسع** الموجود عبر Migration جديدة

### 3. ممنوع الـ Hardcoding
- ❌ لا تضع أرقام سحرية (magic numbers)
- ❌ لا تضع نصوص بالعربية/الإنجليزية مباشرة في الكود
- ✅ استخدم `config/`, `lang/`, `constants/`

### 4. ممنوع الـ N+1 Queries
- ❌ لا تستدعي علاقات داخل loops
- ✅ استخدم `with()`, `load()`, `select()` بذكاء

### 5. ممنوع الـ Raw SQL إلا عند الضرورة القصوى
- ✅ استخدم Eloquent Query Builder دائماً
- ❌ لا تستخدم `DB::raw()` إلا إذا كان الأداء حرجاً ومُوثّقاً

## 🟡 القواعد الإلزامية (MUST DO)

### 1. Multi-Tenancy
```php
// ✅ صحيح: كل نموذج يجب أن يكون scoped للشركة
class Product extends Model {
    use BelongsToCompany;
    
    protected static function booted() {
        static::addGlobalScope('company', function (Builder $builder) {
            $builder->where('company_id', auth()->user()->company_id);
        });
    }
}