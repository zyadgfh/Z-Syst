توصيات لإدارة المستودع المدمج

مقترحات تنفيذية آمنة مرتبة بالأولوية:

1) تنظيم المشاريع الفرعية (مستحسن)
   - أنشئ مجلداً `third_party/` وانقل المجلدات: `free-claude-code`, `Cody`, `pharmacy-store-app-codecanyon-main`, `pospro-app-codecanyon-Codecanyon_6.1` إلخ.
   - استخدم أوامر git التالية (بعد أخذ نسخة احتياطية):

```bash
git mv pharmacy-store-app-codecanyon-main third_party/
git mv pospro-app-codecanyon-Codecanyon_6.1 third_party/
git mv free-claude-code third_party/
git mv Cody third_party/
git commit -m "Move third-party / subprojects to third_party/"
```

2) توحيد متغيرات البيئة
   - ادمج متغيرات الموديولات في `.env.example` الجذري (تمت إضافة مقترح لهذا الملف).
   - لا تضع أسرارًا حقيقية في repo؛ استعمل vault/CI secrets.

3) إدارة الاعتمادات (`composer.json` / `package.json`)
   - خيار آمن: اترك `composer.json` في الجذر مسؤولًا عن تشغيل الـ backend، واحتفظ بملفات الموديولات إن كانت موديولات منشورة كحزم.
   - إذا تريد دمج الاعتمادات من الموديولات إلى الجذر: جمع الحزم المكررة والتحقق من نسخها المتوافقة، ثم تشغيل:

```bash
composer validate
composer update --with-all-dependencies
```

4) فحوص سلامة وتشغيل
   - بعد أي تغيير في الاعتمادات أو نقل مجلدات، شغل:

```bash
composer install
php artisan migrate --seed
vendor/bin/phpunit
vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G
```

5) تحديث التوثيق
   - حدث `README.md` لإظهار بنية المشروع الجديدة وروابط إلى `docs/third_party/README_LIST.md` و`docs/RECOMMENDATIONS.md`.

إذا أوافقك، أستطيع تنفيذ أمر `git mv` للمواضيع الفرعية هنا (سيغير مسارها في المستودع). أحتاج موافقتك للقيام بنقل فعلي لأن هذه العملية تغير بنية المشروع وتؤثر على المسارات.
