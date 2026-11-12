# /cybersec — تشغيل تدقيق أمني باستخدام حزمة المهارات المثبتة

يستخدم أمرًا مهارة التوجيه `z-syst-cybersec` وحزمة الـ 62 مهارة المثبتة من
`mukul975/Anthropic-Cybersecurity-Skills` (المرجع الكامل في
`.agents/skills/z-syst-cybersec/PROVENANCE.md`).

## متى يُستخدم

- طلب فحص أو تدقيق أمني لمشروع Z-Syst (Laravel + Flutter + Supabase + Vercel)
- مراجعة عزل المستأجرين، RLS، JWT/GoTrue، إعدادات CI/CD
- تحليل فجوات الامتثال (HIPAA / GDPR / PCI) لنظام صيدلية

## سير العمل القياسي

1. اقرأ `PROJECT_RULES/04_SECURITY_RULES.md` وتقارير الأمان السابقة في `docs/zsyst/`.
2. شغّل المهارة الحتمية `code-vulnscan` (`.github/skills/code-vulnscan/`) أولاً.
3. طبّق مهارات التخطيط الموضحة في جدول "Component → playbook map" داخل
   `.agents/skills/z-syst-cybersec/SKILL.md` يدوياً على الكود.
4. استعلم عن الإعدادات الحية عبر موصلات `supabase` و`vercel` **للقراءة فقط**
   (أسماء المتغيرات ونتائج SELECT فقط — بدون قيم أسرار أو أي تعديل).
5. أصدر التقرير في `docs/zsyst/NN-security-audit-YYYY-MM-DD.md` بصيغة
   observed/inferred/unverified مع الدليل file:line ودرجة الخطورة والإصلاح المقترح.

## قواعد صارمة

- لا تعديل كود التطبيق أثناء التدقيق، لا طباعة قيم أسرار، لا تعديل إعدادات حية.
- كل نتيجة يجب أن يكون لها: مَنبع ← مَصب ← مسار، مُتحقَّق منه بقراءة الكود فعلياً.
- عزل المستأجرين (`business_id`) هو فئة المخاطر الأولى في كل مسار استعلام جديد.
