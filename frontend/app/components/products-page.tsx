"use client";
import { useEffect, useState, useRef, useCallback, useMemo } from "react";
import AuthCard from "./auth-card";
import ProductForm from "./product-form";

type Category = { id: string; name: string; categoryName?: string };
type Unit = { id: string; unitName: string; short_code?: string };
type Company = { id: string; name: string; companyName?: string };
type Branch = { id: string; name: string; branchName?: string };

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
  category?: Category | null;
  category_id?: string | null;
  unit?: Unit | null;
  unit_id?: string | null;
  unit_of_measure?: string;
  is_splittable?: boolean;
  sales_price?: number;
  purchase_price?: number;
  wholesale_price?: number;
  cost_price?: number;
  current_stock?: number;
  min_stock?: number;
  reorder_level?: number;
  is_active?: boolean;
  created_at?: string;
};

type SearchTab = "all" | "name" | "barcode" | "generic" | "code";

type ProductsPageProps = {
  token: string;
  companyId: string | null;
  branchId: string | null;
  userEmail: string | null;
  onLogout: () => void;
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";
const CURRENCY = new Intl.NumberFormat("en-US", {
  style: "currency",
  currency: "USD",
  maximumFractionDigits: 2,
});

const SEARCH_TABS: { key: SearchTab; label: string; icon: string }[] = [
  { key: "all", label: "🔍 Smart", icon: "🔍" },
  { key: "name", label: "📝 الاسم", icon: "📝" },
  { key: "generic", label: "🧪 العلمي", icon: "🧪" },
  { key: "barcode", label: "🔢 باركود", icon: "🔢" },
  { key: "code", label: "🏷️ الكود", icon: "🏷️" },
];

export default function ProductsPage({
  token,
  companyId: propCompanyId,
  branchId: propBranchId,
  userEmail,
  onLogout,
}: ProductsPageProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [companies, setCompanies] = useState<Company[]>([]);
  const [branches, setBranches] = useState<Branch[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [statusMessage, setStatusMessage] = useState("Loading products...");
  const [searchQuery, setSearchQuery] = useState("");
  const [searchTab, setSearchTab] = useState<SearchTab>("all");
  const [selectedCategoryId, setSelectedCategoryId] = useState("");
  const [selectedCompanyId, setSelectedCompanyId] = useState(propCompanyId || "");
  const [selectedBranchId, setSelectedBranchId] = useState(propBranchId || "");
  const [showForm, setShowForm] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [isImporting, setIsImporting] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  // ── Build query string from all filters ─────────────────────────
  const buildQueryString = useCallback(() => {
    const params = new URLSearchParams();
    params.set("per_page", "200");

    if (searchQuery.trim()) {
      params.set("search", searchQuery.trim());
      if (searchTab !== "all") {
        params.set("search_type", searchTab);
      }
    }

    if (selectedCategoryId) params.set("category_id", selectedCategoryId);
    if (selectedCompanyId) params.set("company_id", selectedCompanyId);
    if (selectedBranchId) params.set("branch_id", selectedBranchId);

    return params.toString();
  }, [searchQuery, searchTab, selectedCategoryId, selectedCompanyId, selectedBranchId]);

  // ── Load products ────────────────────────────────────────────────
  const loadProducts = useCallback(async () => {
    setIsLoading(true);
    try {
      const qs = buildQueryString();
      const res = await fetch(`${API_BASE}/products?${qs}`, {
        headers: { Authorization: "Bearer " + token },
      });
      const payload = await res.json();
      const list: Product[] = payload.data?.data ?? payload.data ?? [];
      setProducts(list);
      const filterInfo = [];
      if (searchQuery.trim()) filterInfo.push(`"${searchQuery.trim()}"`);
      if (selectedCategoryId) filterInfo.push("فئة");
      if (selectedCompanyId) filterInfo.push("شركة");
      if (selectedBranchId) filterInfo.push("فرع");
      const label = filterInfo.length ? ` 🔍 ${filterInfo.join(" + ")}` : "";
      setStatusMessage(`✅ ${list.length} منتج${label}`);
    } catch (err) {
      setStatusMessage("❌ Error: " + (err instanceof Error ? err.message : "Load failed"));
    } finally {
      setIsLoading(false);
    }
  }, [token, buildQueryString, searchQuery, selectedCategoryId, selectedCompanyId, selectedBranchId]);

  // ── Debounced auto-search ────────────────────────────────────────
  const triggerSearch = useCallback(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      loadProducts();
    }, 400);
  }, [loadProducts]);

  // ── Load categories, companies, branches ─────────────────────────
  const loadCategories = async () => {
    try {
      const res = await fetch(API_BASE + "/categories?per_page=100", {
        headers: { Authorization: "Bearer " + token },
      });
      const payload = await res.json();
      setCategories(payload.data?.data ?? payload.data ?? []);
    } catch {}
  };

  const loadCompanies = async () => {
    try {
      const res = await fetch(API_BASE + "/companies?per_page=100", {
        headers: { Authorization: "Bearer " + token },
      });
      const payload = await res.json();
      setCompanies(payload.data?.data ?? payload.data ?? []);
    } catch {}
  };

  const loadBranches = async () => {
    try {
      const res = await fetch(API_BASE + "/branches?per_page=100", {
        headers: { Authorization: "Bearer " + token },
      });
      const payload = await res.json();
      setBranches(payload.data?.data ?? payload.data ?? []);
    } catch {}
  };

  // Track initial load to avoid cascading setState warnings
  const initialLoadDone = useRef(false);
  useEffect(() => {
    if (!initialLoadDone.current) {
      initialLoadDone.current = true;
      loadProducts();
      loadCategories();
      loadCompanies();
      loadBranches();
    }
  }, [token]);

  // ── Clear all filters ────────────────────────────────────────────
  const clearAllFilters = () => {
    setSearchQuery("");
    setSearchTab("all");
    setSelectedCategoryId("");
    setSelectedCompanyId(propCompanyId || "");
    setSelectedBranchId(propBranchId || "");
    setIsLoading(true);
    setTimeout(() => loadProducts(), 0);
  };

  // ── Filter status (for highlighting active filters) ──────────────
  const hasActiveFilters = useMemo(() => {
    return !!(searchQuery.trim() || selectedCategoryId || selectedCompanyId || selectedBranchId || searchTab !== "all");
  }, [searchQuery, selectedCategoryId, selectedCompanyId, selectedBranchId, searchTab]);

  // ── Highlight matching text ──────────────────────────────────────
  const highlightText = (text: string | undefined | null, query: string) => {
    if (!text) return "-";
    if (!query.trim()) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const parts = text.split(new RegExp(`(${escaped})`, "gi"));
    return parts.map((part, i) =>
      part.toLowerCase() === query.toLowerCase()
        ? <mark key={i} className="bg-amber-400/30 text-amber-200 rounded px-0.5">{part}</mark>
        : part
    );
  };

  const handleDelete = async (product: Product) => {
    if (!confirm("Delete " + (product.name || product.productName) + "?")) return;
    try {
      const res = await fetch(API_BASE + "/products/" + product.id, {
        method: "DELETE",
        headers: { Authorization: "Bearer " + token },
      });
      if (!res.ok) throw new Error("Delete failed");
      setProducts((prev) => prev.filter((p) => p.id !== product.id));
      setStatusMessage((product.name || product.productName) + " deleted");
    } catch (err) {
      setStatusMessage("Delete failed: " + (err instanceof Error ? err.message : ""));
    }
  };

  const handleSave = async (data: Record<string, string | number | boolean | null>) => {
    try {
      const isUpdate = !!editingProduct;
      const url = isUpdate
        ? API_BASE + "/products/" + editingProduct!.id
        : API_BASE + "/products";
      const res = await fetch(url, {
        method: isUpdate ? "PUT" : "POST",
        headers: { "Content-Type": "application/json", Authorization: "Bearer " + token },
        body: JSON.stringify({ ...data, company_id: propCompanyId }),
      });
      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.message || "Save failed");
      }
      setShowForm(false);
      setEditingProduct(null);
      setStatusMessage(isUpdate ? "Product updated" : "Product created");
      loadProducts();
    } catch (err) {
      setStatusMessage("Save failed: " + (err instanceof Error ? err.message : ""));
    }
  };

  const handleExport = async () => {
    try {
      const res = await fetch(API_BASE + "/products/export", {
        headers: { Authorization: "Bearer " + token },
      });
      if (!res.ok) throw new Error("Export failed");
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "products-export.xlsx";
      a.click();
      window.URL.revokeObjectURL(url);
      setStatusMessage("✅ Export downloaded");
    } catch (err) {
      setStatusMessage("Export failed: " + (err instanceof Error ? err.message : ""));
    }
  };

  const handleImport = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setIsImporting(true);
    setStatusMessage("Importing...");

    try {
      const formData = new FormData();
      formData.append("file", file);

      const res = await fetch(API_BASE + "/products/import", {
        method: "POST",
        headers: { Authorization: "Bearer " + token },
        body: formData,
      });

      if (!res.ok) throw new Error("Import failed");

      const result = await res.json();
      setStatusMessage(
        `✅ Imported: ${result.data?.imported_rows || 0} rows, Failed: ${result.data?.failed_rows || 0}`
      );
      loadProducts();
    } catch (err) {
      setStatusMessage("Import failed: " + (err instanceof Error ? err.message : ""));
    } finally {
      setIsImporting(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  };

  if (!token) {
    return (
      <main className="min-h-screen px-4 py-6 text-slate-100">
        <div className="mx-auto flex max-w-3xl flex-col gap-6">
          <header className="rounded-3xl border border-white/10 bg-slate-900/70 p-6">
            <h1 className="text-3xl font-semibold text-white">Sign In</h1>
            <p className="mt-2 text-sm text-slate-400">Authenticate to manage products</p>
          </header>
          <AuthCard onAuthenticated={() => {}} />
        </div>
      </main>
    );
  }

  if (showForm) {
    return (
      <ProductForm
        product={editingProduct}
        categories={categories}
        onSave={handleSave}
        onCancel={() => {
          setShowForm(false);
          setEditingProduct(null);
        }}
        token={token}
        companyId={propCompanyId}
      />
    );
  }

  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_40%),linear-gradient(135deg,_#020617,_#111827_70%)] px-4 py-6 text-slate-100 sm:px-6 lg:px-8">
      <div className="mx-auto flex max-w-7xl flex-col gap-6">
        {/* Header */}
        <header className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">
                Z-Syst Pharmacy
              </p>
              <h1 className="mt-2 text-3xl font-semibold text-white sm:text-4xl">
                🏥 المنتجات / Products
              </h1>
              <p className="mt-3 text-sm text-slate-300">
                إدارة مخزون الصيدلية - Manage pharmacy inventory
              </p>
            </div>
            <div className="rounded-2xl border border-cyan-400/30 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-200">
              <div className="font-semibold">🏪 Main Pharmacy</div>
              <div className="mt-1 text-cyan-100">{statusMessage}</div>
              {userEmail && <div className="mt-1 text-cyan-100">{userEmail}</div>}
            </div>
          </div>
        </header>

        {/* Actions Bar - Search & Filters */}
        <div className="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
          {/* Search Type Tabs */}
          <div className="flex flex-wrap items-center gap-1.5 mb-4">
            {SEARCH_TABS.map((tab) => (
              <button
                key={tab.key}
                onClick={() => {
                  setSearchTab(tab.key);
                  triggerSearch();
                }}
                className={`rounded-xl px-3.5 py-2 text-xs font-medium transition-all ${
                  searchTab === tab.key
                    ? "bg-cyan-500/20 text-cyan-200 border border-cyan-400/40 shadow-sm shadow-cyan-500/10"
                    : "bg-slate-800/70 text-slate-400 border border-transparent hover:bg-slate-700/70 hover:text-slate-200"
                }`}
              >
                {tab.label}
              </button>
            ))}
            {hasActiveFilters && (
              <button
                onClick={clearAllFilters}
                className="rounded-xl px-3.5 py-2 text-xs font-medium bg-rose-500/20 text-rose-300 border border-rose-400/30 hover:bg-rose-500/30 transition-all ml-auto"
              >
                ✕ مسح الكل
              </button>
            )}
          </div>

          {/* Search + Filters Row */}
          <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            {/* Search Input */}
            <div className="flex flex-1 items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
              <svg className="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  triggerSearch();
                }}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    if (debounceRef.current) clearTimeout(debounceRef.current);
                    loadProducts();
                  }
                }}
                placeholder={
                  searchTab === "all" ? "🔍 بحث في الكل (اسم, باركود, كود)..." :
                  searchTab === "name" ? "📝 بحث بالاسم التجاري..." :
                  searchTab === "generic" ? "🧪 بحث بالاسم العلمي..." :
                  searchTab === "barcode" ? "🔢 بحث بالباركود..." :
                  "🏷️ بحث بكود الصنف..."
                }
                className="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-500"
              />
              {searchQuery && (
                <button
                  onClick={() => {
                    setSearchQuery("");
                    loadProducts();
                  }}
                  className="text-sm text-slate-400 hover:text-white shrink-0"
                >
                  ✕
                </button>
              )}
            </div>

            {/* Filter Dropdowns */}
            <div className="flex flex-wrap items-center gap-2">
              {/* Category Filter */}
              <select
                value={selectedCategoryId}
                onChange={(e) => {
                  setSelectedCategoryId(e.target.value);
                  triggerSearch();
                }}
                className="rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2.5 text-xs text-white outline-none focus:border-cyan-400/50 min-w-[110px]"
              >
                <option value="">📂 كل الفئات</option>
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>
                    {cat.name || cat.categoryName}
                  </option>
                ))}
              </select>

              {/* Company Filter */}
              <select
                value={selectedCompanyId}
                onChange={(e) => {
                  setSelectedCompanyId(e.target.value);
                  triggerSearch();
                }}
                className="rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2.5 text-xs text-white outline-none focus:border-cyan-400/50 min-w-[110px]"
              >
                <option value="">🏢 كل الشركات</option>
                {companies.map((comp) => (
                  <option key={comp.id} value={comp.id}>
                    {comp.name || comp.companyName}
                  </option>
                ))}
              </select>

              {/* Branch Filter */}
              <select
                value={selectedBranchId}
                onChange={(e) => {
                  setSelectedBranchId(e.target.value);
                  triggerSearch();
                }}
                className="rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2.5 text-xs text-white outline-none focus:border-cyan-400/50 min-w-[110px]"
              >
                <option value="">📍 كل الفروع</option>
                {branches.map((br) => (
                  <option key={br.id} value={br.id}>
                    {br.name || br.branchName}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Action Buttons Row */}
          <div className="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-white/5 pt-4">
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => {
                  setEditingProduct(null);
                  setShowForm(true);
                }}
                className="rounded-2xl bg-cyan-500 px-5 py-2.5 font-semibold text-white shadow-lg shadow-cyan-500/30 transition-all hover:bg-cyan-400 text-sm"
              >
                ➕ إضافة منتج
              </button>
              <button
                onClick={handleExport}
                className="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition-all hover:bg-emerald-500"
              >
                📥 تصدير Excel
              </button>
              <label className="cursor-pointer rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition-all hover:bg-amber-500">
                📤 استيراد Excel
                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".xlsx,.xls,.csv"
                  onChange={handleImport}
                  className="hidden"
                  disabled={isImporting}
                />
              </label>
            </div>
            <button
              onClick={() => {
                setIsLoading(true);
                loadProducts();
              }}
              className="rounded-2xl bg-slate-800 px-4 py-2.5 text-sm text-slate-300 transition-all hover:bg-slate-700"
            >
              🔄 تحديث
            </button>
          </div>

          {/* Stats */}
          <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div className="rounded-xl bg-slate-800/70 p-3 text-center">
              <p className="text-2xl font-bold text-white">{products.length}</p>
              <p className="text-xs text-slate-400">الكل / Total</p>
            </div>
            <div className="rounded-xl bg-slate-800/70 p-3 text-center">
              <p className="text-2xl font-bold text-emerald-300">
                {products.filter((p) => (p.current_stock ?? 0) > 0).length}
              </p>
              <p className="text-xs text-slate-400">متوفر / In Stock</p>
            </div>
            <div className="rounded-xl bg-slate-800/70 p-3 text-center">
              <p className="text-2xl font-bold text-amber-300">
                {products.filter((p) => (p.current_stock ?? 0) <= 0).length}
              </p>
              <p className="text-xs text-slate-400">نفذ / Out of Stock</p>
            </div>
            <div className="rounded-xl bg-slate-800/70 p-3 text-center">
              <p className="text-2xl font-bold text-cyan-300">{categories.length}</p>
              <p className="text-xs text-slate-400">فئات / Categories</p>
            </div>
          </div>
        </div>

        {/* Products Table */}
        <div className="rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-slate-950/40 backdrop-blur overflow-x-auto">
          {isLoading ? (
            <div className="p-8 text-center text-sm text-slate-400">
              <div className="animate-spin inline-block w-6 h-6 border-2 border-cyan-400 border-t-transparent rounded-full mb-2"></div>
              <p>جاري التحميل...</p>
            </div>
          ) : products.length === 0 ? (
            <div className="p-8 text-center text-sm text-slate-400">
              {searchQuery || hasActiveFilters ? "🔍 لا توجد نتائج - جرب تغيير معايير البحث" : "📦 لا توجد منتجات"}
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-white/10 bg-slate-800/70">
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الكود
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الاسم
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الاسم العلمي
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الباركود
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الفئة
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                    الوحدة
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-400">
                    سعر البيع
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-400">
                    سعر الجملة
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-400">
                    المخزون
                  </th>
                  <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-400">
                    إجراءات
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {products.map((product) => (
                  <tr key={product.id} className="hover:bg-slate-800/50 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs text-cyan-300">
                      {highlightText(product.product_code || product.sku, searchQuery)}
                    </td>
                    <td className="px-4 py-3 font-medium text-white">
                      <div className="flex items-center gap-2">
                        {highlightText(product.name || product.product_name || product.productName, searchQuery)}
                        {product.is_splittable && (
                          <span className="rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] text-amber-300">
                            قابل للتجزئة
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-slate-300">
                      {highlightText(product.generic_name, searchQuery)}
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-400">
                      {highlightText(product.barcode, searchQuery)}
                    </td>
                    <td className="px-4 py-3">
                      <span className="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-300">
                        {product.category?.name || product.category?.categoryName || "-"}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-400">
                      {product.unit?.unitName || product.unit?.short_code || product.unit_of_measure || "-"}
                    </td>
                    <td className="px-4 py-3 text-right font-medium text-cyan-300">
                      {CURRENCY.format(product.sales_price || 0)}
                    </td>
                    <td className="px-4 py-3 text-right text-slate-400">
                      {CURRENCY.format(product.wholesale_price || 0)}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <span
                        className={
                          "font-medium " +
                          ((product.current_stock ?? 0) <= 0
                            ? "text-rose-300"
                            : (product.current_stock ?? 0) < 10
                            ? "text-amber-300"
                            : "text-emerald-300")
                        }
                      >
                        {Math.floor(product.current_stock ?? 0)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          onClick={() => {
                            setEditingProduct(product);
                            setShowForm(true);
                          }}
                          className="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-700 transition-colors"
                        >
                          ✏️ تعديل
                        </button>
                        <button
                          onClick={() => handleDelete(product)}
                          className="rounded-lg bg-rose-500/20 px-3 py-1.5 text-xs text-rose-300 hover:bg-rose-500/30 transition-colors"
                        >
                          🗑 حذف
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </main>
  );
}

