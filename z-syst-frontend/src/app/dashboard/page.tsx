import Link from "next/link";
import { AppShell } from "@/components/layout/AppShell";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { tokens } from "@/lib/tokens";

const cards = [
  { title: "إجمالي المبيعات", value: "١٢٤٬٥٦٠ جنيه", hint: "+12% عن الأسبوع الماضي" },
  { title: "المنتجات منخفضة المخزون", value: "١٨ منتج", hint: "تنبيه فوري" },
  { title: "الطلبات المعلقة", value: "٦", hint: "قيد التنفيذ" },
  { title: "الصلاحية القريبة", value: "٩", hint: "راجع المخزون" },
];

export default function DashboardPage() {
  return (
    <AppShell>
      <div className="mx-auto max-w-7xl space-y-6">
        <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div>
            <p className="text-sm text-slate-500">مرحبًا بك مرة أخرى</p>
            <h1 className="text-2xl font-semibold text-slate-900">لوحة القيادة</h1>
          </div>
          <div className="flex gap-3">
            <Link href="/">
              <Button variant="secondary">الرئيسية</Button>
            </Link>
            <Link href="/pos">
              <Button style={{ backgroundColor: tokens.brand[500], color: tokens.neutral[0] }}>فتح POS</Button>
            </Link>
          </div>
        </header>

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {cards.map((card) => (
            <Card key={card.title} className="p-5">
              <p className="text-sm text-slate-500">{card.title}</p>
              <p className="mt-3 text-2xl font-semibold text-slate-900">{card.value}</p>
              <p className="mt-2 text-sm text-emerald-600">{card.hint}</p>
            </Card>
          ))}
        </section>

        <section className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500">النشاط الأخير</p>
                <h2 className="text-xl font-semibold text-slate-900">المبيعات الأخيرة</h2>
              </div>
              <Button variant="ghost">عرض الكل</Button>
            </div>

            <div className="mt-5 space-y-3">
              {[
                { label: "عملية بيع #1024", value: "١٤٣ جنيه" },
                { label: "عملية بيع #1023", value: "٩٠ جنيه" },
                { label: "عملية بيع #1022", value: "٢٢٠ جنيه" },
              ].map((item) => (
                <div key={item.label} className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
                  <span className="text-sm font-medium text-slate-700">{item.label}</span>
                  <span className="text-sm text-slate-500">{item.value}</span>
                </div>
              ))}
            </div>
          </Card>

          <Card className="p-6">
            <p className="text-sm text-slate-500">تحليل سريع</p>
            <h2 className="mt-1 text-xl font-semibold text-slate-900">أداء اليوم</h2>
            <div className="mt-5 space-y-4">
              <div className="rounded-xl bg-sky-50 p-4">
                <p className="text-sm text-sky-700">أعلى مبيعات</p>
                <p className="mt-1 text-2xl font-semibold text-slate-900">باراسيتامول</p>
              </div>
              <div className="rounded-xl bg-emerald-50 p-4">
                <p className="text-sm text-emerald-700">التغطية</p>
                <p className="mt-1 text-2xl font-semibold text-slate-900">٩٣٪</p>
              </div>
            </div>
          </Card>
        </section>
      </div>
    </AppShell>
  );
}
