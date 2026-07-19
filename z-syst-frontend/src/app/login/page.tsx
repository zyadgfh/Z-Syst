import Link from "next/link";

export default function LoginPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 p-6">
      <div className="w-full max-w-5xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-xl">
        <div className="grid lg:grid-cols-[1fr_0.9fr]">
          <div className="p-8 sm:p-10">
            <p className="text-sm font-medium text-sky-700">تسجيل الدخول</p>
            <h1 className="mt-2 text-3xl font-semibold text-slate-900">مرحبًا بك في Z-Syst</h1>
            <p className="mt-3 text-slate-600">ادخل إلى لوحة الإدارة الخاصة بصيدليتك.</p>

            <form className="mt-8 space-y-4">
              <div>
                <label className="mb-2 block text-sm font-medium text-slate-700">البريد الإلكتروني</label>
                <input className="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="name@pharmacy.com" />
              </div>
              <div>
                <label className="mb-2 block text-sm font-medium text-slate-700">كلمة المرور</label>
                <input type="password" className="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="********" />
              </div>
              <button className="w-full rounded-full bg-[#0091ff] px-4 py-3 font-medium text-white">تسجيل الدخول</button>
            </form>

            <div className="mt-6 text-sm text-slate-600">
              ليس لديك حساب؟ <Link href="/" className="font-medium text-sky-700">تواصل معنا</Link>
            </div>
          </div>

          <div className="bg-slate-900 p-8 text-white sm:p-10">
            <h2 className="text-2xl font-semibold">واجهة حديثة لكل الأدوار</h2>
            <p className="mt-3 text-slate-300">من أمين الصندوق إلى مدير الفرع، كل شيء في مكان واحد.</p>
            <div className="mt-8 space-y-3">
              <div className="rounded-2xl bg-white/10 p-4">POS سريع ومجهز للسرعة</div>
              <div className="rounded-2xl bg-white/10 p-4">تقارير مالية دقيقة</div>
              <div className="rounded-2xl bg-white/10 p-4">دعم عربي/إنجليزي كامل</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
