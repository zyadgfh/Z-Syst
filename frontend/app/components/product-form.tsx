"use client";

import { useState, useEffect } from "react";

type Category = { id: string; name: string; categoryName?: string };
type Unit = { id: string; unitName: string; short_code?: string; category?: string; is_fractional?: boolean };

type Product = {
  id: string;
  name?: string;
  product_name?: string;
  productName?: string;
  generic_name?: string;
  brand_name?: string;
  barcode?: string;
  sku?: string;
  product_code?: string;
  category_id?: string | null;
  unit_id?: string | null;
  unit_of_measure?: string;
  is_splittable?: boolean;
  split_unit_id?: string | null;
  split_quantity?: number | null;
  sales_price?: number;
  purchase_price?: number;
  wholesale_price?: number;
  cost_price?: number;
  current_stock?: number;
  min_stock?: number;
  max_stock?: number;
  reorder_level?: number;
  is_active?: boolean;
  description?: string;
  manufacturer_id?: string | null;
  dosage_form?: string;
  strength?: string;
  prescription_required?: boolean;
};

type ProductFormProps = {
  product: Product | null;
  categories: Category[];
  onSave: (data: Record<string, any>) => Promise<void>;
  onCancel: () => void;
  token: string;
  companyId: string | null;
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";

export default function ProductForm({
  product,
  categories,
  onSave,
  onCancel,
  token,
  companyId,
}: ProductFormProps) {
  const isEditing = !!product;
  const [units, setUnits] = useState<Unit[]>([]);

  const [name, setName] = useState(product?.name || product?.product_name || product?.productName || "");
  const [genericName, setGenericName] = useState(product?.generic_name || "");
  const [brandName, setBrandName] = useState(product?.brand_name || "");
  const [barcode, setBarcode] = useState(product?.barcode || "");
  const [categoryId, setCategoryId] = useState(product?.category_id || "");
  const [unitId, setUnitId] = useState(product?.unit_id || "");
  const [price, setPrice] = useState(String(product?.sales_price ?? ""));
  const [wholesalePrice, setWholesalePrice] = useState(String(product?.wholesale_price ?? ""));
  const [cost, setCost] = useState(String(product?.purchase_price || product?.cost_price ?? ""));
  const [minStock, setMinStock] = useState(String(product?.min_stock ?? ""));
  const [maxStock, setMaxStock] = useState(String(product?.max_stock ?? ""));
  const [reorderLevel, setReorderLevel] = useState(String(product?.reorder_level ?? ""));
  const [description, setDescription] = useState(product?.description || "");
  const [dosageForm, setDosageForm] = useState(product?.dosage_form || "");
  const [strength, setStrength] = useState(product?.strength || "");
  const [isSplittable, setIsSplittable] = useState(product?.is_splittable || false);
  const [isActive, setIsActive] = useState(product?.is_active ?? true);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    const loadUnits = async () => {
      try {
        const res = await fetch(API_BASE + "/units?per_page=100", {
          headers: { Authorization: `Bearer ${token}` },
        });
        const payload = await res.json();
        setUnits(payload.data?.data ?? payload.data ?? []);
      } catch {}
    };
    loadUnits();
  }, [token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      await onSave({
        name: name.trim(),
        product_name: name.trim(),
        generic_name: genericName.trim(),
        brand_name: brandName.trim(),
        barcode: barcode.trim(),
        category_id: categoryId || null,
        unit_id: unitId || null,
        sales_price: parseFloat(price) || 0,
        wholesale_price: parseFloat(wholesalePrice) || 0,
        purchase_price: parseFloat(cost) || 0,
        min_stock: parseInt(minStock) || 0,
        max_stock: parseInt(maxStock) || 0,
        reorder_level: parseInt(reorderLevel) || 0,
        description: description.trim(),
        dosage_form: dosageForm.trim(),
        strength: strength.trim(),
        is_splittable: isSplittable,
        is_active: isActive,
      });
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_40%),linear-gradient(135deg,_#020617,_#111827_70%)] px-4 py-6 text-slate-100 sm:px-6 lg:px-8">
      <div className="mx-auto flex max-w-3xl flex-col gap-6">
        {/* Header */}
        <header className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">
                Z-Syst Pharmacy
              </p>
              <h1 className="mt-2 text-3xl font-semibold text-white sm:text-4xl">
                {isEditing ? "✏️ تعديل المنتج" : "➕ إضافة منتج جديد"}
              </h1>
              <p className="mt-3 text-sm text-slate-300">
                {isEditing
                  ? `تعديل: ${product?.name || product?.product_name || product?.productName}`
                  : "املأ التفاصيل لإضافة منتج جديد"}
              </p>
            </div>
            <button
              onClick={onCancel}
              className="rounded-xl bg-slate-800 px-4 py-2 text-sm text-slate-300 hover:bg-slate-700"
            >
              ✕ إغلاق
            </button>
          </div>
        </header>

        {/* Form */}
        <form onSubmit={handleSubmit} className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
          <div className="grid gap-5 sm:grid-cols-2">
            {/* Name */}
            <div className="sm:col-span-2">
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الاسم / Product Name <span className="text-rose-400">*</span>
              </label>
              <input
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                placeholder="مثال: أموكسيسيلين 500mg"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Generic Name */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الاسم العلمي / Generic Name
              </label>
              <input
                value={genericName}
                onChange={(e) => setGenericName(e.target.value)}
                placeholder="مثال: أموكسيسيلين"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Brand Name */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الاسم التجاري / Brand Name
              </label>
              <input
                value={brandName}
                onChange={(e) => setBrandName(e.target.value)}
                placeholder="مثال: Amoxil"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Barcode */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الباركود / Barcode
              </label>
              <input
                value={barcode}
                onChange={(e) => setBarcode(e.target.value)}
                placeholder="مثال: 06240001403550"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Category */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الفئة / Category
              </label>
              <select
                value={categoryId}
                onChange={(e) => setCategoryId(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50"
              >
                <option value="">— اختر الفئة —</option>
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>
                    {cat.name || cat.categoryName}
                  </option>
                ))}
              </select>
            </div>

            {/* Unit */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                وحدة القياس / Unit
              </label>
              <select
                value={unitId}
                onChange={(e) => setUnitId(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50"
              >
                <option value="">— اختر الوحدة —</option>
                {units.map((unit) => (
                  <option key={unit.id} value={unit.id}>
                    {unit.unitName} {unit.short_code ? `(${unit.short_code})` : ""}
                  </option>
                ))}
              </select>
            </div>

            {/* Dosage Form */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                شكل الجرعة / Dosage Form
              </label>
              <select
                value={dosageForm}
                onChange={(e) => setDosageForm(e.target.value)}
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50"
              >
                <option value="">— اختر —</option>
                <option value="tablet">أقراص (Tablet)</option>
                <option value="capsule">كبسول (Capsule)</option>
                <option value="syrup">شراب (Syrup)</option>
                <option value="injection">حقن (Injection)</option>
                <option value="cream">كريم (Cream)</option>
                <option value="ointment">مرهم (Ointment)</option>
                <option value="drops">قطرة (Drops)</option>
                <option value="spray">رذاذ (Spray)</option>
                <option value="powder">مسحوق (Powder)</option>
                <option value="solution">محلول (Solution)</option>
              </select>
            </div>

            {/* Strength */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                القوة / Strength
              </label>
              <input
                value={strength}
                onChange={(e) => setStrength(e.target.value)}
                placeholder="مثال: 500mg, 1000mg"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Code (auto-generated, read-only) */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                كود الصنف / Product Code
              </label>
              <input
                value={product?.product_code || product?.sku || "(تلقائي)"}
                disabled
                className="w-full rounded-xl border border-white/5 bg-slate-900/50 px-4 py-2.5 text-sm text-slate-400 outline-none"
              />
            </div>

            {/* Price Section */}
            <div className="sm:col-span-2">
              <h3 className="mb-3 text-sm font-semibold text-cyan-300 border-b border-white/10 pb-2">💰 الأسعار / Pricing</h3>
            </div>

            {/* Sales Price */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                سعر البيع / Sales Price <span className="text-rose-400">*</span>
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={price}
                onChange={(e) => setPrice(e.target.value)}
                required
                placeholder="0.00"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Wholesale Price */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                سعر الجملة / Wholesale Price
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={wholesalePrice}
                onChange={(e) => setWholesalePrice(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Cost */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                التكلفة / Cost Price
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={cost}
                onChange={(e) => setCost(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Stock Section */}
            <div className="sm:col-span-2">
              <h3 className="mb-3 text-sm font-semibold text-emerald-300 border-b border-white/10 pb-2">📦 المخزون / Stock</h3>
            </div>

            {/* Min Stock */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الحد الأدنى / Min Stock
              </label>
              <input
                type="number"
                min="0"
                value={minStock}
                onChange={(e) => setMinStock(e.target.value)}
                placeholder="0"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Max Stock */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الحد الأقصى / Max Stock
              </label>
              <input
                type="number"
                min="0"
                value={maxStock}
                onChange={(e) => setMaxStock(e.target.value)}
                placeholder="0"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Reorder Level */}
            <div>
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                نقطة إعادة الطلب / Reorder Level
              </label>
              <input
                type="number"
                min="0"
                value={reorderLevel}
                onChange={(e) => setReorderLevel(e.target.value)}
                placeholder="0"
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>

            {/* Divisible (Splittable) */}
            <div className="flex items-center gap-3 rounded-xl bg-slate-800/70 p-3">
              <label className="relative inline-flex cursor-pointer items-center">
                <input
                  type="checkbox"
                  checked={isSplittable}
                  onChange={(e) => setIsSplittable(e.target.checked)}
                  className="peer sr-only"
                />
                <div className="h-6 w-11 rounded-full bg-slate-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-cyan-500 peer-checked:after:translate-x-full"></div>
              </label>
              <span className="text-sm text-slate-300">منتج قابل للتجزئة (Splittable)</span>
            </div>

            {/* Active */}
            <div className="flex items-center gap-3 rounded-xl bg-slate-800/70 p-3">
              <label className="relative inline-flex cursor-pointer items-center">
                <input
                  type="checkbox"
                  checked={isActive}
                  onChange={(e) => setIsActive(e.target.checked)}
                  className="peer sr-only"
                />
                <div className="h-6 w-11 rounded-full bg-slate-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-full"></div>
              </label>
              <span className="text-sm text-slate-300">نشط (Active)</span>
            </div>

            {/* Description */}
            <div className="sm:col-span-2">
              <label className="mb-1.5 block text-sm font-medium text-slate-300">
                الوصف / Description
              </label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={3}
                placeholder="وصف المنتج..."
                className="w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-400/50 placeholder:text-slate-500"
              />
            </div>
          </div>

          {/* Actions */}
          <div className="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
            <button
              type="submit"
              disabled={isSaving || !name.trim()}
              className="rounded-2xl bg-cyan-500 px-8 py-3 font-semibold text-white shadow-lg shadow-cyan-500/30 transition-all hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {isSaving ? "⏳ جاري الحفظ..." : isEditing ? "💾 تحديث المنتج" : "✅ إنشاء المنتج"}
            </button>
            <button
              type="button"
              onClick={onCancel}
              className="rounded-2xl bg-slate-800 px-6 py-3 text-sm text-slate-300 hover:bg-slate-700"
            >
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </main>
  );
}

