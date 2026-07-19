import { AppShell } from "@/components/layout/AppShell";

const items = [
  { name: "باراسيتامول", level: "متوسط", quantity: 150 },
  { name: "أموكسيسيلين", level: "منخفض", quantity: 20 },
  { name: "إيبوبروفين", level: "مقبول", quantity: 85 },
];

export default function InventoryPage() {
  return (
    <AppShell>
      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">المخزون</h1>
        <div className="mt-5 grid gap-3">
          {items.map((item) => (
            <div key={item.name} className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
              <span className="font-medium text-slate-800">{item.name}</span>
              <div className="flex gap-4 text-sm text-slate-600">
                <span>المستوى: {item.level}</span>
                <span>الكمية: {item.quantity}</span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AppShell>
  );
}
