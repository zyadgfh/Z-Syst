# تحليلات مجانية — اعرف لماذا يدفع/يلغي الصيدلي (صفر تكلفة)

> طلبت "كل ما سبق" للتحليلات — هذا هو الحل المجاني

## ماذا نريد أن نعرف؟
- أي ميزة يستخدمها أكثر (POS vs تقارير)
- متى يترك النظام (Churn)
- لماذا يرقي من 399 إلى 599

## الحل صفر تكلفة (3 أدوات مجانية)

### 1) Vercel Analytics (موجود)
- **التكلفة:** 0 (مضمن مع Vercel)
- **يظهر:** زيارات، صفحات، سرعة
- **كود:** `@vercel/analytics` مثبت بالفعل

### 2) Supabase SQL مباشر (مجاني)
```sql
-- أكثر الميزات استخداماً (last 30 days)
select 'sales' as feature, count(*) from sales where created_at > now()-interval '30 days'
union all select 'purchases', count(*) from purchases where created_at > now()-interval '30 days'
union all select 'expenses', count(*) from expenses where created_at > now()-interval '30 days';

-- صيدليات لم تدخل منذ 7 أيام (معرضة للإلغاء)
select b.company_name, max(s.created_at) as last_sale
from businesses b left join sales s on s.business_id=b.id
group by b.id having max(s.created_at) < now()-interval '7 days';
```

### 3) PostHog Free (بديل Google Analytics)
- **التكلفة:** 1M event/شهر مجاناً
- **الاستضافة:** PostHog Cloud مجاني
- **التتبع:** `posthog.capture('sell_clicked', { branch: 1 })`

## لوحة تحكم مجانية (Supabase + Metabase)
- **Metabase Cloud Free:** اربطه بـ Supabase (Read Only) → لوحة تحكم بدون كود
- **أو:** استخدم Supabase Dashboard → SQL Editor → احفظ الاستعلامات

## الخلاصة
- لا تحتاج Mixpanel مدفوع
- Vercel + Supabase SQL + PostHog Free = كل التحليلات مجاناً
