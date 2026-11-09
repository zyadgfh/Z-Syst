# عربي وإنجليزي — RTL كامل صفر تكلفة

> اخترت "عربي + إنجليزي قابل للتبديل" — هذا هو التنفيذ المجاني

## الوضع الحالي (مقاس)
- `lang/ar.json` و `lang/en.json` موجودة
- `app()->getLocale()` يحدد اللغة
- Tailwind RTL يحتاج `dir="rtl"` فقط

## التنفيذ صفر تكلفة (ساعة واحدة)

### 1) HTML dir
```blade
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
```

### 2) Tailwind RTL (مجاني)
```javascript
// tailwind.config.js — لا تحتاج plugin، استخدم logical properties
// بدل ml-4 استخدم ms-4 (margin-start) — يعمل تلقائياً RTL/LTR
```

### 3) زر التبديل
```blade
<a href="/lang/en">English</a> | <a href="/lang/ar">عربي</a>
```

### 4) الأرقام والباركود تبقى LTR دائماً
```css
.barcode, .numbers { direction: ltr; display: inline-block; }
```

## ماذا عن الموبايل؟
- Flutter يدعم RTL تلقائياً عبر `Directionality`
- لا تكلفة إضافية

## الخلاصة
- لا تحتاج مكتبة مدفوعة — HTML dir + Tailwind logical properties تكفي
- اختبر: بدّل اللغة → يجب أن تنقلب القوائم والجداول تلقائياً
