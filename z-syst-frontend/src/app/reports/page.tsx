import { AppShell } from "@/components/layout/AppShell";

export default function ReportsPage() {
  return (
    <AppShell>
      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">التقارير</h1>
        <p className="mt-2 text-slate-600">تقارير المبيعات والمخزون والأداء المالي ستظهر هنا.</p>
      </div>
    </AppShell>
  );
}
