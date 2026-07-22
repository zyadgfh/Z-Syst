"use client";

import { useState, useEffect } from "react";

type Discount = {
  id: string;
  name: string;
  code: string | null;
  type: "percentage" | "fixed";
  value: number;
  min_purchase_amount: number | null;
  max_discount_amount: number | null;
  apply_to: string;
  start_date: string | null;
  end_date: string | null;
  usage_limit: number | null;
  used_count: number;
  is_active: boolean;
  description: string | null;
  created_at: string;
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";

export default function DiscountManager({ token }: { token: string }) {
  const [discounts, setDiscounts] = useState<Discount[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingDiscount, setEditingDiscount] = useState<Discount | null>(null);
  const [statusMessage, setStatusMessage] = useState("");

  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [type, setType] = useState<"percentage" | "fixed">("percentage");
  const [value, setValue] = useState("");
  const [applyTo, setApplyTo] = useState("all");
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [minPurchase, setMinPurchase] = useState("");
  const [maxDiscount, setMaxDiscount] = useState("");

  const loadDiscounts = async () => {
    try {
      const res = await fetch(API_BASE + "/discounts?per_page=50", {
        headers: { Authorization: "Bearer " + token },
      });
      const payload = await res.json();
      setDiscounts(payload.data?.data ?? payload.data ?? []);
    } catch {
      setStatusMessage("Failed to load discounts");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadDiscounts();
  }, [token]);

  const resetForm = () => {
    setName("");
    setCode("");
    setType("percentage");
    setValue("");
    setApplyTo("all");
    setStartDate("");
    setEndDate("");
    setIsActive(true);
    setMinPurchase("");
    setMaxDiscount("");
    setEditingDiscount(null);
  };

  const handleEdit = (discount: Discount) => {
    setName(discount.name);
    setCode(discount.code || "");
    setType(discount.type);
    setValue(String(discount.value));
    setApplyTo(discount.apply_to);
    setStartDate(discount.start_date || "");
    setEndDate(discount.end_date || "");
    setIsActive(discount.is_active);
    setMinPurchase(discount.min_purchase_amount ? String(discount.min_purchase_amount) : "");
    setMaxDiscount(discount.max_discount_amount ? String(discount.max_discount_amount) : "");
    setEditingDiscount(discount);
    setShowForm(true);
  };

  const handleDelete = async (discount: Discount) => {
    if (!confirm("Delete discount '" + discount.name + "'?")) return;
    try {
      const res = await fetch(API_BASE + "/discounts/" + discount.id, {
        method: "DELETE",
        headers: { Authorization: "Bearer " + token },
      });
      if (!res.ok) throw new Error("Delete failed");
      loadDiscounts();
      setStatusMessage("Discount deleted");
    } catch {
      setStatusMessage("Delete failed");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload: Record<string, any> = {
        name,
        code: code || null,
        type,
        value: parseFloat(value) || 0,
        apply_to: applyTo,
        start_date: startDate || null,
        end_date: endDate || null,
        is_active: isActive,
        min_purchase_amount: minPurchase ? parseFloat(minPurchase) : null,
        max_discount_amount: maxDiscount ? parseFloat(maxDiscount) : null,
      };

      const isUpdate = !!editingDiscount;
      const url = isUpdate
        ? API_BASE + "/discounts/" + editingDiscount!.id
        : API_BASE + "/discounts";
      const res = await fetch(url, {
        method: isUpdate ? "PUT" : "POST",
        headers: { "Content-Type": "application/json", Authorization: "Bearer " + token },
        body: JSON.stringify(payload),
      });

      if (!res.ok) throw new Error("Save failed");

      setShowForm(false);
      resetForm();
      loadDiscounts();
      setStatusMessage(isUpdate ? "Discount updated" : "Discount created");
    } catch {
      setStatusMessage("Save failed");
    }
  };

  return (
    <div className="rounded-3xl border border-white/10 bg-slate-900/70 p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-semibold text-white">🏷️ الخصومات / Discounts</h2>
        <button
          onClick={() => {
            resetForm();
            setShowForm(true);
          }}
          className="rounded-2xl bg-cyan-500 px-6 py-3 font-semibold text-white"
        >
          ➕ إضافة خصم
        </button>
      </div>

      {statusMessage && (
        <div className="mb-4 rounded-xl bg-slate-800 px-4 py-2 text-sm text-slate-300">
          {statusMessage}
        </div>
      )}

      {showForm && (
        <form onSubmit={handleSubmit} className="mb-6 rounded-2xl border border-white/10 bg-slate-800/70 p-5">
          <h3 className="mb-4 text-lg font-semibold text-cyan-300">
            {editingDiscount ? "✏️ تعديل الخصم" : "➕ خصم جديد"}
          </h3>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">الاسم *</label>
              <input value={name} onChange={(e) => setName(e.target.value)} required
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50" />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">الكود (اختياري)</label>
              <input value={code} onChange={(e) => setCode(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50" />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">النوع</label>
              <select value={type} onChange={(e) => setType(e.target.value as "percentage" | "fixed")}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50">
                <option value="percentage">نسبة %</option>
                <option value="fixed">قيمة ثابتة</option>
              </select>
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">القيمة *</label>
              <input type="number" step="0.01" min="0" value={value} onChange={(e) => setValue(e.target.value)} required
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50" />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">التطبيق على</label>
              <select value={applyTo} onChange={(e) => setApplyTo(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50">
                <option value="all">الكل</option>
                <option value="products">منتجات محددة</option>
                <option value="categories">فئات محددة</option>
              </select>
            </div>
            <div className="flex items-center gap-3">
              <label className="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} className="peer sr-only" />
                <div className="h-6 w-11 rounded-full bg-slate-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-full"></div>
              </label>
              <span className="text-sm text-slate-300">نشط</span>
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">تاريخ البدء</label>
              <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50" />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">تاريخ الانتهاء</label>
              <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50" />
            </div>
          </div>
          <div className="mt-4 flex gap-3">
            <button type="submit" className="rounded-2xl bg-cyan-500 px-6 py-2.5 font-semibold text-white">
              {editingDiscount ? "💾 تحديث" : "✅ حفظ"}
            </button>
            <button type="button" onClick={() => { setShowForm(false); resetForm(); }}
              className="rounded-2xl bg-slate-800 px-6 py-2.5 text-sm text-slate-300 hover:bg-slate-700">
              إلغاء
            </button>
          </div>
        </form>
      )}

      {/* Discounts List */}
      <div className="space-y-2">
        {isLoading ? (
          <div className="text-center text-sm text-slate-400 py-4">Loading...</div>
        ) : discounts.length === 0 ? (
          <div className="text-center text-sm text-slate-400 py-4">No discounts yet</div>
        ) : (
          discounts.map((discount) => (
            <div key={discount.id} className="flex items-center justify-between rounded-xl bg-slate-800/50 p-4">
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <span className="font-medium text-white">{discount.name}</span>
                  {discount.code && (
                    <span className="rounded bg-cyan-500/20 px-2 py-0.5 text-xs text-cyan-300">
                      {discount.code}
                    </span>
                  )}
                  <span className={"rounded-full px-2 py-0.5 text-xs " + (discount.is_active ? "bg-emerald-500/20 text-emerald-300" : "bg-slate-600/50 text-slate-400")}>
                    {discount.is_active ? "نشط" : "غير نشط"}
                  </span>
                </div>
                <div className="mt-1 text-xs text-slate-400">
                  {discount.type === "percentage" ? `${discount.value}%` : `${discount.value} $`}
                  {" | "}تطبيق: {discount.apply_to}
                  {discount.used_count > 0 && ` | استخدام: ${discount.used_count}`}
                </div>
              </div>
              <div className="flex gap-2">
                <button onClick={() => handleEdit(discount)}
                  className="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-700">
                  تعديل
                </button>
                <button onClick={() => handleDelete(discount)}
                  className="rounded-lg bg-rose-500/20 px-3 py-1.5 text-xs text-rose-300 hover:bg-rose-500/30">
                  حذف
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}

