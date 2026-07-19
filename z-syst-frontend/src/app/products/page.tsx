import { AppShell } from "@/components/layout/AppShell";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";

const products = [
  { name: "باراسيتامول", sku: "P-001", stock: 150, price: "25 جنيه" },
  { name: "أموكسيسيلين", sku: "P-002", stock: 45, price: "80 جنيه" },
  { name: "إيبوبروفين", sku: "P-003", stock: 12, price: "65 جنيه" },
];

export default function ProductsPage() {
  return (
    <AppShell>
      <div className="space-y-6">
        <Card className="flex items-center justify-between p-5">
          <div>
            <p className="text-sm text-slate-500">إدارة المنتجات</p>
            <h1 className="text-2xl font-semibold text-slate-900">المنتجات</h1>
          </div>
          <Button>+ إضافة منتج</Button>
        </Card>

        <Card className="p-5">
          <table className="w-full text-right">
            <thead>
              <tr className="border-b border-slate-200 text-sm text-slate-500">
                <th className="pb-3">الاسم</th>
                <th className="pb-3">SKU</th>
                <th className="pb-3">المخزون</th>
                <th className="pb-3">السعر</th>
              </tr>
            </thead>
            <tbody>
              {products.map((product) => (
                <tr key={product.sku} className="border-b border-slate-100 text-sm text-slate-700">
                  <td className="py-3 font-medium">{product.name}</td>
                  <td className="py-3">{product.sku}</td>
                  <td className="py-3">{product.stock}</td>
                  <td className="py-3">{product.price}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      </div>
    </AppShell>
  );
}
