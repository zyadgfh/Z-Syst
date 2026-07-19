import { AppShell } from "@/components/layout/AppShell";

export default function SettingsPage() {
  return (
    <AppShell>
      <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">الإعدادات</h1>
        <p className="mt-2 text-slate-600">إدارة المستخدمين، الفروع، والاتصالات هنا.</p>
      </div>
    </AppShell>
  );
}
