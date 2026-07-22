"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

type DashboardStats = {
  totalProducts: number;
  totalStock: number;
  totalSales: number;
  totalRevenue: number;
  lowStockItems: number;
};

type RecentSale = {
  id: number;
  totalAmount: number;
  status: string;
  createdAt: string;
};

export default function DashboardScreen() {
  const [stats, setStats] = useState<DashboardStats>({
    totalProducts: 0,
    totalStock: 0,
    totalSales: 0,
    totalRevenue: 0,
    lowStockItems: 0,
  });
  const [recentSales, setRecentSales] = useState<RecentSale[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [token, setToken] = useState<string | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) return;
    loadDashboardData();
  }, [isAuthenticated]);

  const loadDashboardData = async () => {
    setIsLoading(true);
    try {
      const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost:3001";
      
      // Fetch products for count
      const productsRes = await fetch(`${apiBase}/products/branch/1`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      
      // Fetch sales stats
      const salesRes = await fetch(`${apiBase}/pos/sales`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (productsRes.ok) {
        const products = await productsRes.json();
        const totalStock = products.reduce((sum: number, p: any) => sum + (p.stock || 0), 0);
        const lowStockItems = products.filter((p: any) => (p.stock || 0) < 10).length;
        
        setStats((prev) => ({
          ...prev,
          totalProducts: products.length,
          totalStock,
          lowStockItems,
        }));
      }

      if (salesRes.ok) {
        const sales = await salesRes.json();
        const totalRevenue = sales.reduce((sum: number, s: any) => sum + Number(s.totalAmount || 0), 0);
        setStats((prev) => ({
          ...prev,
          totalSales: sales.length,
          totalRevenue,
        }));
        setRecentSales(sales.slice(0, 5));
      }
    } catch (error) {
      // Demo data fallback
      setStats({
        totalProducts: 24,
        totalStock: 1250,
        totalSales: 18,
        totalRevenue: 2450.75,
        lowStockItems: 5,
      });
      setRecentSales([
        { id: 1, totalAmount: 45.5, status: "COMPLETED", createdAt: new Date().toISOString() },
        { id: 2, totalAmount: 78.25, status: "COMPLETED", createdAt: new Date().toISOString() },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  if (!isAuthenticated) {
    return (
      <main className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 px-4 py-8 text-slate-100">
        <div className="mx-auto max-w-2xl">
          <h1 className="mb-6 text-3xl font-bold">Dashboard</h1>
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <p className="mb-4 text-slate-300">Please sign in to view dashboard</p>
            <button
              onClick={() => {
                setToken("demo-token");
                setIsAuthenticated(true);
              }}
              className="rounded-xl bg-cyan-500 px-4 py-2 font-semibold text-white hover:bg-cyan-400"
            >
              Sign in (Demo)
            </button>
          </div>
        </div>
      </main>
    );
  }

  const currency = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  });

  return (
    <main className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 px-4 py-8 text-slate-100">
      <div className="mx-auto max-w-7xl">
        {/* Header */}
        <header className="mb-8">
          <h1 className="text-3xl font-bold">Dashboard</h1>
          <p className="text-slate-400">Overview of your pharmacy operations</p>
        </header>

        {/* Stats Grid */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <div className="text-sm text-slate-400">Total Products</div>
            <div className="mt-2 text-3xl font-bold text-cyan-300">{stats.totalProducts}</div>
          </div>
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <div className="text-sm text-slate-400">Total Stock</div>
            <div className="mt-2 text-3xl font-bold text-emerald-300">{stats.totalStock}</div>
          </div>
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <div className="text-sm text-slate-400">Total Sales</div>
            <div className="mt-2 text-3xl font-bold text-white">{stats.totalSales}</div>
          </div>
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <div className="text-sm text-slate-400">Total Revenue</div>
            <div className="mt-2 text-3xl font-bold text-amber-300">{currency.format(stats.totalRevenue)}</div>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {/* Low Stock Alert */}
          <div className="rounded-2xl border border-rose-400/30 bg-rose-500/10 p-6 lg:col-span-1">
            <h2 className="mb-4 text-xl font-semibold text-rose-300">Low Stock Alert</h2>
            <p className="text-4xl font-bold text-rose-400">{stats.lowStockItems}</p>
            <p className="mt-2 text-sm text-slate-300">Items need restocking</p>
            <Link href="/inventory" className="mt-4 inline-block text-sm text-cyan-300 hover:underline">
              View inventory →
            </Link>
          </div>

          {/* Recent Sales */}
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6 lg:col-span-2">
            <h2 className="mb-4 text-xl font-semibold">Recent Sales</h2>
            {isLoading ? (
              <div className="text-slate-400">Loading...</div>
            ) : recentSales.length === 0 ? (
              <div className="text-slate-400">No recent sales</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-white/10">
                      <th className="px-3 py-2 text-left text-sm">Sale ID</th>
                      <th className="px-3 py-2 text-left text-sm">Amount</th>
                      <th className="px-3 py-2 text-left text-sm">Status</th>
                      <th className="px-3 py-2 text-left text-sm">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recentSales.map((sale) => (
                      <tr key={sale.id} className="border-b border-white/5">
                        <td className="px-3 py-2">#{sale.id}</td>
                        <td className="px-3 py-2">{currency.format(sale.totalAmount)}</td>
                        <td className="px-3 py-2">
                          <span className="rounded-full bg-emerald-500/20 px-2 py-1 text-xs text-emerald-300">
                            {sale.status}
                          </span>
                        </td>
                        <td className="px-3 py-2 text-slate-400">
                          {new Date(sale.createdAt).toLocaleDateString()}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}