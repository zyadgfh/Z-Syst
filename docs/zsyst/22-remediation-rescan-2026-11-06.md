# 22 — إصلاحات وتدقيق إعادة الفحص (بعد تقرير 21)

**Date:** 2026-11-06 · **Scope:** كل إصلاح قابل للكود من تقرير `21-security-scan` + إعادة فحص كاملة `/ecc` (نفس المنهجية، وضع offline)

## قبل → بعد

| ID | قبل | الإصلاح المنفَّذ | بعد (إعادة الفحص) |
|---|---|---|---|
| H-01 | أسرار في تاريخ git | **بيد المالك** (تدوير Stripe + filter-repo) — لا يُصلَح بكود | كما هو — بند تشغيلي معلّق |
| H-02 | رفع بلا allowlist (SVG/XSS) | `zsValidateUpload()` في المسارات الثلاثة (امتداد+MIME، SVG مرفوض) + allowlist قابلة للضبط في `integrations.uploads` | ✅ مغلق (4 نقاط تحقق) |
| M-01 | re2js ReDoS GHSA-gg6h-894w-4m9c | `npm audit fix` → re2js **2.9.1** | ✅ `npm audit`: **0 vulnerabilities** |
| M-02 | كوكي بلا secure | `'secure' => env(..., ! app()->isLocal())` | ✅ مفروض خارج local |
| M-03 | مسارات دفع عامة + تسميم session | بوابات auth + ملكية على `index` و`payment` (superadmin bypass)؛ session يُكتب من الهوية المتحققة لا من الرابط | ✅ مغلق (بوابتان) |
| L-01 | exec بلا تهريب | `escapeshellarg` ×10 (mysqldump + mysql restore) | ✅ مغلق |
| L-02 | B608 + B104 (أداة dev) | تحقق معرّف صارم `_safe_ident()` + nosec موثق + ربط loopback افتراضي (`BRIDGE_HOST`) | ✅ **bandit: 0 findings** |
| I-01 | مجلدات code-vulnscan متتبعة | أُزيلت من التتبع والشجرة (5 ملفات مجلدات) | ✅ 0 ملفات متتبعة |
| I-02 | تحقق CVE كامل | يتطلب شبكة (NVD/OSV) | بيد المالك/لاحقًا |

## إعادة الفحص — خلاصة الأنماط (كلها خضراء)
`eval/unserialize` صفر · SQL خام بمدخلات صفر · **npm audit 0** · **bandit 0** · أسرار الشجرة صفر · uploads مقيّدة · مسارات الدفع مُحصّنة · secure cookie مفروض.

## بند واحد متبقٍ (تشغيلي، بيد المالك)
**H-01**: تدوير مفاتيح Stripe في لوحة Stripe (فوري)، ثم `git filter-repo --path database/database_export.sql --path .env --invert-paths` + force-push وإبطال الفروع القديمة، ثم gitleaks في CI. الكود لا يستطيع فعل أيٍّ من ذلك.
