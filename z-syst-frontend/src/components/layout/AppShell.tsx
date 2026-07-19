import Link from "next/link";
import { ReactNode } from "react";
import { ThemeToggle } from "./ThemeToggle";

const navItems = [
  { href: "/dashboard", label: "لوحة القيادة" },
  { href: "/pos", label: "POS" },
  { href: "/products", label: "المنتجات" },
  { href: "/inventory", label: "المخزون" },
  { href: "/sales", label: "المبيعات" },
  { href: "/reports", label: "التقارير" },
  { href: "/settings", label: "الإعدادات" },
];

export function AppShell({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen bg-slate-50 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
      <aside className="hidden w-72 flex-col border-r border-slate-200 bg-white p-6 lg:flex dark:border-slate-800 dark:bg-slate-900">
        <div className="mb-8">
          <p className="text-sm font-medium text-sky-700">Z-Syst</p>
          <h2 className="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">نظام إدارة صيدلية</h2>
        </div>

        <nav className="space-y-2">
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="flex rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-400"
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      <div className="flex-1">
        <header className="border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-slate-500 dark:text-slate-400">واجهة الإدارة</p>
              <h1 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Z-Syst Pharmacy SaaS</h1>
            </div>
            <div className="flex items-center gap-3">
              <ThemeToggle />
              <div className="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-300">EN / AR</div>
              <div className="h-10 w-10 rounded-full bg-sky-600" />
            </div>
          </div>
        </header>

        <main className="p-6">{children}</main>
      </div>
    </div>
  );
}
