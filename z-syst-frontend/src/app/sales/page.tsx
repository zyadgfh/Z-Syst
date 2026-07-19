import { AppShell } from "@/components/layout/AppShell";

const sales = [
  { id: "#1001", customer: "أحمد", total: "١٤٣ جنيه", status: "مكتمل" },
  { id: "#1002", customer: "سارة", total: "٩٠ جنيه", status: "قيد الانتظار" },
  { id: "#1003", customer: "محمد", total: "٢٢٠ جنيه", status: "مكتمل" },
];

export default function SalesPage() {
  return (
    <AppShell>
      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">المبيعات</h1>
        <div className="mt-5 space-y-3">
          {sales.map((sale) => (
            <div key={sale.id} className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
              <div>
                <p className="font-medium text-slate-800">{sale.id}</p>
                <p className="text-sm text-slate-500">{sale.customer}</p>
              </div>
              <div className="flex items-center gap-4 text-sm text-slate-600">
                <span>{sale.total}</span>
                <span className="rounded-full bg-slate-100 px-3 py-1">{sale.status}</span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AppShell>
  );
}
