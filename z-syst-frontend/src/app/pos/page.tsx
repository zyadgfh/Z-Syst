import { AppShell } from "@/components/layout/AppShell";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";

const products = [
  { name: "باراسيتامول", price: "٢٥ جنيه", stock: "متوفر" },
  { name: "أموكسيسيلين", price: "٨٠ جنيه", stock: "متوفر" },
  { name: "إيبوبروفين", price: "٦٥ جنيه", stock: "قليل" },
];

export default function PosPage() {
  return (
    <AppShell>
      <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-slate-500">شاشة الدفع</p>
              <h1 className="text-2xl font-semibold text-slate-900">POS سريع</h1>
            </div>
            <div className="rounded-full bg-sky-100 px-3 py-1 text-sm font-medium text-sky-700">حالة: جاهز</div>
          </div>

          <div className="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-sm text-slate-500">بحث / باركود</p>
            <div className="mt-2 h-12 rounded-xl border border-dashed border-slate-300 bg-white" />
          </div>

          <div className="mt-6 grid gap-3 md:grid-cols-3">
            {products.map((product) => (
              <div key={product.name} className="rounded-2xl border border-slate-200 p-4">
                <p className="font-semibold text-slate-900">{product.name}</p>
                <p className="mt-2 text-sm text-slate-500">{product.price}</p>
                <p className="mt-2 text-sm text-emerald-600">{product.stock}</p>
              </div>
            ))}
          </div>
        </Card>

        <aside className="rounded-3xl border border-slate-200 bg-slate-900 p-6 text-white shadow-sm">
          <h2 className="text-xl font-semibold">السلة</h2>
          <div className="mt-6 space-y-3">
            <div className="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3">
              <span>باراسيتامول</span>
              <span>٢ × ٢٥</span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3">
              <span>أموكسيسيلين</span>
              <span>١ × ٨٠</span>
            </div>
          </div>

          <div className="mt-8 space-y-2 border-t border-white/10 pt-4 text-sm">
            <div className="flex justify-between"><span>الإجمالي</span><span>١٣٠</span></div>
            <div className="flex justify-between"><span>الخصم</span><span>٠</span></div>
            <div className="flex justify-between"><span>الضريبة</span><span>١٣</span></div>
            <div className="mt-3 flex justify-between text-lg font-semibold"><span>الإجمالي النهائي</span><span>١٤٣</span></div>
          </div>

          <Button className="mt-8 w-full">تأكيد الدفع</Button>
        </aside>
      </div>
    </AppShell>
  );
}
