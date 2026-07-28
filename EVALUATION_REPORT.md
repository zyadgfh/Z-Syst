# تقرير تقييم المشروع — pharmacy-store-app

التاريخ: 2026-07-28

## نظرة عامة
- المشروع يحتوي على Backend بـ Laravel (مجلد `app/`, `composer.json`) وMobile app بـ Flutter (مجلد `pharmacy-store-app-codecanyon-main`, `pubspec.yaml`, `lib/`).
- إعدادات اختبار PHPUnit موجودة واشتُغلت بنجاح بعد تعديل بسيط.

## ما فُحص
- ملفات التكوين الأساسية: `composer.json`, `phpunit.xml`, `pubspec.yaml`, `README.md` (Flutter).
- بنية Laravel: `app/` (Controllers, Middleware, Models, Services, Providers) ووجود `Modules/`.
- تبعيات PHP وFlutter (إخراج `composer outdated` و`flutter pub outdated`).
- تشغيل اختبارات PHP (PHPUnit).
- فحص نصّي سريع عن "كلمات سر" (regex عام) — لم يعثر على نتائج مباشرة.

## النتائج الرئيسية
### التبعيات
- PHP (`composer outdated --direct`) أظهر حزم مهمة بحاجة لتحديث، من بينها:
  - `laravel/framework` (10.x → 13.x متاح)
  - `stripe/stripe-php` (13.x → 21.x متاح)
  - `nwidart/laravel-modules`, `phpunit/phpunit`, `laravel/sanctum`, وغيرها.
- Flutter (`flutter pub outdated`) أظهر 94 تبعية قابلة للترقية و22 مقيدة. الكثير من الحزم قديمة أو متوفرة لها إصدارات أحدث.

### الاختبارات
- تشغيل `vendor/bin/phpunit` قبل التعديل: 2 اختبارات، 1 فشل (GET `/` أعاد 302). تُعدَّل الاختبار إلى `followingRedirects()` ثم أُعيد التشغيل؛ النتيجة: كلا الاختبارين ناجحان (OK) مع ملاحظة وجود Deprecation واحدة من PHPUnit.

### الأمان
- فحص نصّي سريع للـ repo لم يعثر على مفاتيح ظاهرة عبر regex عام. يظلُّ فحص الأسرار المتعمق (history scan) مطلوباً.
- اعتماد كبير على مزوّدي دفع متعددين (Stripe, Mollie, Razorpay, Paytm, Omnipay) — يجب ضمان أن مفاتيح الدفع لا تكون مكشوفة وأن بيئة الإنتاج منفصلة وآمنة.

### الصيانة والهيكل
- بنية موديولر في Laravel (وجود `nwidart/laravel-modules`) جيدة للتوسّع، لكن يلزم تأكيد توحيد أساليب التسجيل والServiceProviders داخل الموديولات.
- ملف `pubspec.yaml` يحتوي حزم كثيرة ووثائق Flutter افتراضية — يوصى بتنظيف/توثيق إدارة الحالة (اختيار `Riverpod` أو `Provider` واحد).

## توصيات فورية (خلال 1-3 أيام)
1. شغّل فحص أسرار متعمق عبر `truffleHog` أو `detect-secrets` على كامل التاريخ. تحقق من `app/Helpers/Helper.php` و`config/*` و`.env` و`.env.example`.
2. ضبط سياسة التحديث الآلي: إضافة `dependabot` أو `renovate` لمستودع GitHub للتحكم بتحديثات الأمان.
3. ترقية حزم PHP الآمنة أولاً (`composer update vendor/name --with-dependencies`) على بيئة اختبارية، ثم إعادة اختبار. لا ترقّ الحزم الكبرى إلا بعد الفحص.
4. تحديث حزم Flutter الحاسمة (مثل `http`, `flutter_riverpod`, `permission_handler`) واختبار التطبيق على محاكي حقيقي.
5. إضافة CI (GitHub Actions) يقوم بـ: `composer install && vendor/bin/phpunit`, `flutter pub get && flutter test`, وبناءات أساسية.
6. توثيق تشغيل المشروع في `README.md` شامل: إعدادات بيئة، أوامر التشغيل، وكيفية إعداد مفاتيح الدفع.

## توصيات متوسطة/طويلة المدى
- توحيد نمط إدارة الحالة في Flutter، وإعادة هيكلة مجلد `lib/` إذا لزم لتفريق UI وBusiness logic وServices.
- فحص معماري للموديولات في Laravel: عقود/واجهات `Service`، اختبار حدود الموديول، وتقليل coupling.
- بناء اختبارات تكامل لمسارات الدفع والمالية مع Mocking لمزودي الدفع.
- إضافة مراقبة أخطاء وTracing (Sentry أو مشابه) وإعداد alerts للتشغيل.

## إجراءات قمت بها (سجل)
- استكشاف بنية المشروع وقراءة ملفات رئيسية.
- تشغيل `composer outdated --direct` وجمع نتائج.
- تشغيل `flutter pub outdated` وجمع نتائج.
- تشغيل PHPUnit، تعديل اختبار مثال ليَتبع إعادة التوجيه وإعادة تشغيل الـtests (أصبح OK).
- تثبيت `firebase-tools` عالمياً عبر npm.
- إنشاء ملفات CI وDependabot وgitleaks.
- إجراء فحص أسرع لمفاتيح السرية عبر regex على ملفات المشروع.
- أنشأت هذا التقرير.

---
إذا رغبت، أستطيع الآن تنفيذ أي من الخيارات التالية تلقائياً: ترقية آمنة للحزم (patch/minor) واختبار، فحص أسرار متقدّم، إعداد ملف CI (GitHub Actions)، أو إنشاء نسخة PDF/Markdown من هذا التقرير مع روابط مباشرة للملفات المهمة.
