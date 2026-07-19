import Link from "next/link";

const metrics = [
  { label: "صيدليات نشطة", value: "2,400+" },
  { label: "عمليات يومية", value: "120K+" },
  { label: "رضا المستخدمين", value: "98%" },
];

const features = [
  "POS سريع ومتاح دون اتصال",
  "إدارة المخزون مع تتبع الانتهاء",
  "تقارير مالية ومبيعات فورية",
  "دعم عربي/إنجليزي مع RTL كامل",
];

export default function Home() {
  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(0,145,255,0.15),_transparent_40%)]">
      <section className="mx-auto flex max-w-7xl flex-col gap-10 px-6 py-10 lg:px-8">
        <header className="flex items-center justify-between rounded-full border border-slate-200 bg-white/80 px-4 py-3 shadow-sm backdrop-blur">
          <div className="text-lg font-semibold text-slate-900">Z-Syst</div>
          <nav className="flex items-center gap-4 text-sm font-medium text-slate-700">
            <Link href="/dashboard" className="hover:text-brand-500">لوحة القيادة</Link>
            <Link href="/pos" className="hover:text-brand-500">POS</Link>
            <Link href="/login" className="hover:text-brand-500">تسجيل الدخول</Link>
          </nav>
        </header>

        <div className="grid items-center gap-8 rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl lg:grid-cols-[1.1fr_0.9fr] lg:p-12">
          <div className="space-y-6">
            <div className="inline-flex rounded-full bg-sky-100 px-3 py-1 text-sm font-medium text-sky-700">
              نظام صيدلية ذكي ومهني
            </div>
            <h1 className="text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl">
              إدارة صيدلية واحدة، تجربة رقمية احترافية من أول لحظة.
            </h1>
            <p className="max-w-2xl text-lg text-slate-600">
              من المبيعات والمخزون إلى التقارير والعمليات، Z-Syst يجمع كل ما تحتاجه في واجهة سريعة، واضحة، وموثوقة.
            </p>
            <div className="flex flex-wrap gap-3">
              <Link href="/dashboard" className="rounded-full bg-[#0091ff] px-5 py-3 font-medium text-white transition hover:bg-[#0074cc]">
                تجربة لوحة القيادة
              </Link>
              <Link href="/pos" className="rounded-full border border-slate-300 px-5 py-3 font-medium text-slate-700 transition hover:bg-slate-100">
                فتح POS السريع
              </Link>
            </div>
          </div>

          <div className="rounded-3xl bg-slate-900 p-6 text-white shadow-2xl">
            <div className="rounded-2xl border border-white/10 bg-white/10 p-5">
              <p className="text-sm text-slate-300">مؤشرات اليوم</p>
              <div className="mt-4 grid gap-3">
                {metrics.map((item) => (
                  <div key={item.label} className="flex items-center justify-between rounded-xl bg-white/10 px-4 py-3">
                    <span className="text-sm text-slate-300">{item.label}</span>
                    <span className="text-lg font-semibold">{item.value}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        <section className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 className="text-2xl font-semibold text-slate-900">الميزات الأساسية</h2>
            <ul className="mt-5 space-y-3 text-slate-600">
              {features.map((feature) => (
                <li key={feature} className="flex items-start gap-2">
                  <span className="mt-1 text-[#0091ff]">●</span>
                  <span>{feature}</span>
                </li>
              ))}
            </ul>
          </div>

          <div className="rounded-3xl border border-slate-200 bg-slate-900 p-8 text-white shadow-sm">
            <h2 className="text-2xl font-semibold">ماذا يقدّم النظام؟</h2>
            <p className="mt-3 text-slate-300">
              واجهة موحّدة لمالك الصيدلية، المدير، الصيدلي، والأمين، مع أولويات واضحة وسرعة تشغيل عالية.
            </p>
            <div className="mt-6 grid gap-4 sm:grid-cols-2">
              <div className="rounded-2xl bg-white/10 p-4">
                <div className="text-2xl font-semibold">24/7</div>
                <div className="text-sm text-slate-300">دعم العمليات</div>
              </div>
              <div className="rounded-2xl bg-white/10 p-4">
                <div className="text-2xl font-semibold">RTL</div>
                <div className="text-sm text-slate-300">عربي/إنجليزي</div>
              </div>
            </div>
          </div>
        </section>
      </section>
    </main>
  );
}
