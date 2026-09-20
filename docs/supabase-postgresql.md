# تشغيل Laravel على PostgreSQL/Supabase

يستخدم المشروع migrations Laravel مع Schema Builder. بعد هذا التحويل يمكن تشغيلها على PostgreSQL، مع إبقاء MySQL ممكنًا عبر `DB_CONNECTION`.

## الإعداد

1. انسخ `.env.supabase.example` إلى `.env`.
2. املأ بيانات الاتصال من صفحة **Connect** في Supabase، ولا تحفظ كلمة المرور في Git.
3. ثبّت امتداد PHP `pdo_pgsql` وعميل PostgreSQL عند الحاجة.
4. نفّذ `php artisan config:clear` ثم `php artisan migrate --pretend --database=pgsql` للمراجعة.
5. بعد مراجعة SQL، نفّذ `php artisan migrate --database=pgsql` في بيئة Supabase غير الإنتاجية أولًا.

## ملاحظات التوافق

- استُبدلت أنواع unsigned بأنواع integer/bigInteger لأن PostgreSQL لا يملك unsigned integer أصليًا.
- أزيلت `after(...)` لأنها خاصية ترتيب أعمدة خاصة بـ MySQL.
- استُبدلت `double` بـ `decimal` لتجنب أخطاء الفاصلة العائمة في الأسعار والأرصدة والنسب المالية.
- يجب مراجعة أي كود استعلام يستخدم `IFNULL` أو backticks أو `DATE_FORMAT` أو SQL خام؛ هذه لا تأتي من migrations الحالية لكنها قد تظهر في الخدمات والتقارير.
- تشغيل migrations لا ينقل بيانات MySQL ولا ينشئ مستخدمي Supabase Auth ولا سياسات RLS تلقائيًا.

## قبل الإنتاج

يجب تشغيل النسخ على قاعدة staging، ومقارنة عدد الجداول والأعمدة والمفاتيح والفهارس، ثم اختبار البيع والشراء والمرتجع والجرد والنسخ الاحتياطي. لا تُعدّل migrations سبق تطبيقها على بيئة إنتاج؛ استخدم migration جديدة للبيئات القائمة.
