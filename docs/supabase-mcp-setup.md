# إعداد Supabase MCP ووكلاء الذكاء الاصطناعي

> آخر تحديث: 2026-10-01

يحتوي هذا المشروع الآن على:

1. **إعداد MCP جاهز** في جذر المستودع: `.mcp.json`
2. **مهارات Supabase لوكلاء البرمجة** (Agent Skills) مثبتة بنطاق المشروع:
   - `.agents/skills/supabase` — تعليمات العمل مع كل منتجات Supabase (مترابطة رمزيًا مع `.claude/skills/` وWindsurf وغيرها)
   - `.agents/skills/supabase-postgres-best-practices` — أفضل ممارسات Postgres
   - ملف القفل `skills-lock.json` (استعادة سريعة عبر `npx skills experimental_install`)

---

## 1) تشغيل خادم MCP المحلي (الطريقة الرسمية القياسية)

ملف `.mcp.json` يشغّل `@supabase/mcp-server-supabase` عبر stdio ويقرأ قيمتين من البيئة:

| المتغير | من أين تحصل عليه | ضروري؟ |
|---|---|---|
| `SUPABASE_ACCESS_TOKEN` | لوحة Supabase → صورة حسابك → **Access tokens** → أنشئ توكن | نعم (للإدارة: مشاريع، هجرات، أنواع…) |
| `SUPABASE_DB_PASSWORD` | Project Settings → Database (نفس كلمة مرور الاتصال بقاعدة البيانات) | فقط إذا أردت أوامر قاعدة البيانات المباشرة (`apply_migration`, `execute_sql`…) |

ثم في أي عميل MCP يدعم ملفات مشاريع (Claude Code مثلًا): افتح مجلد المشروع وسيُكتشف الخادم تلقائيًا.

> ⚠️ لا تضع القيم الحقيقية داخل `.mcp.json` أو أي ملف متتبع — استخدم متغيرات البيئة فقط.

## 2) الربط مع ChatGPT (خطوة "Add to ChatGPT")

هذا الربط يتم من حسابك في ChatGPT نفسه ولا يمكن تنفيذه من هذا المستودع:

1. افتح ChatGPT → **Settings** → **Connectors**
2. ابحث عن **Supabase** واضغط **Connect**
3. سيطلب السماح لـ ChatGPT بالوصول إلى مشروعك عبر OAuth — اختر المشروع
4. نقطة النهاية المستخدمة خلف الكواليس: `https://mcp.supabase.com/mcp?project-ref=<REF>`

إن كان عميلك يدعم MCP عن بُعد مع OAuth (بدون تشغيل خادم محلي) يمكن استخدام نفس الرابط مباشرة بدل إعداد stdio أعلاه.

## 3) المهارات المثبتة

- التحديث: `npx skills update supabase -p`
- الإزالة: `npx skills remove supabase supabase-postgres-best-practices`
- التثبيت من القفل: `npx skills experimental_install`

## 4) دليل سريع: أي مفتاح لأي غرض؟

| المفتاح | الصيغة | الغرض | أين يوجد |
|---|---|---|---|
| **مرجع المشروع** (Project Ref) | 20 حرفًا مثل `abcdefghijklmnopqrst` | اسم النطاق `https://<ref>.supabase.co` والاتصال المباشر | رابط لوحة المشروع أو Project Settings → General |
| **كلمة مرور قاعدة البيانات** | تختارها بنفسك | الاتصال المباشر بـ Postgres (Pooler ومنفذا 5432/6543) وأدوات قاعدة البيانات في MCP | Project Settings → Database → Database password |
| **توكن الوصول الشخصي** | يبدأ بـ `sbp_` | `SUPABASE_ACCESS_TOKEN` لخادم MCP وواجهة الإدارة و`supabase link` | صورة الحساب → Access tokens |
| **المفتاح السري للبيانات** (الجيل الجديد) | يبدأ بـ `sb_secret_` | بديل مفتاح `service_role` القديم: يصادق على Data API من جهة الخادم **ويتجاوز RLS** — لا يوضع في أي تطبيق عميل | المشروع → Connect أو Settings → API Keys |
| **مفتاح النشر** | يبدأ بـ `sb_publishable_` | تطبيقات المتصفح/الجوال (صلاحيات محدودة وتحترم RLS) | المشروع → Connect |

> ⚠️ لا تخلط بينها: مفتاح `sb_secret_` لا يصلح كلمةَ مرور لقاعدة البيانات ولا توكنًا للإدارة، وتوكن `sbp_` لا يصادق اتصالات Postgres المباشرة.
> أي مفتاح يُشارك في محادثة أو يُلتقط في سجل يعتبر مكشوفًا — عطّله وأنشئ بديلًا فورًا من نفس الصفحة.

## 5) الأدوات الجاهزة في هذا المستودع

- **جسر المتصفح**: `tools/supabase-web/` — واجهة ويب تتصل مباشرة عبر بروتوكول Postgres (مفيدة في البيئات التي تحجب مواقع Supabase وتسمح بمنافذ 5432/6543).
- **سكربت مزامنة المخطط**: `supabase/update_2026_10_01.sql` — يضيف `businesses.status` و`testimonials.status` على قاعدة السحابة (آمن وقابل لإعادة التشغيل).
