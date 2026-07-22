"use client";

import { useEffect, useMemo, useState } from "react";
import AuthCard, { type AuthResult } from "./auth-card";

// ── Types ─────────────────────────────────────────────────────────────────┐

type Product = {
  id: string;
  name: string;
  generic_name: string;
  sku: string;
  barcode: string;
  sale_price: number;
  current_stock: number;
  is_active: boolean;
  category: { id: string; name: string } | null;
};

type CartItem = {
  product_id: string;
  product_name: string;
  barcode: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax: number;
  line_total: number;
};

type SaleItemResult = {
  product_id: string;
  product_name: string;
  barcode: string;
  batch_number: string | null;
  quantity: number;
  unit_price: number;
  discount: number;
  tax: number;
  line_total: number;
};

type SalePaymentResult = {
  id: string;
  payment_method: string;
  amount: number;
  reference_number: string | null;
  transaction_id: string | null;
};

type SaleResult = {
  id: string;
  invoice_number: string;
  customer_name: string;
  customer_phone: string | null;
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  total_amount: number;
  amount_paid: number;
  change_amount: number;
  due_amount: number;
  payment_method: string;
  payment_status: string;
  status: string;
  items_count: number;
  items: SaleItemResult[];
  payments: SalePaymentResult[];
  receipt_url: string;
};

// ── Constants ──────────────────────────────────────────────────────────────

const PAYMENT_METHODS = [
  { id: "cash", label: "💰 Cash", icon: "💵" },
  { id: "card", label: "💳 Card", icon: "💳" },
  { id: "wallet", label: "📱 Wallet", icon: "📱" },
  { id: "insurance", label: "🏥 Insurance", icon: "🏥" },
];

const CURRENCY = new Intl.NumberFormat("en-US", {
  style: "currency",
  currency: "USD",
  maximumFractionDigits: 2,
});

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";

// ── Component ──────────────────────────────────────────────────────────────

export default function PosScreen() {
  const [products, setProducts] = useState<Product[]>([]);
  const [query, setQuery] = useState("");
  const [cart, setCart] = useState<CartItem[]>([]);
  const [paymentMethod, setPaymentMethod] = useState("cash");
  const [statusMessage, setStatusMessage] = useState("Loading products...");
  const [isCheckingOut, setIsCheckingOut] = useState(false);
  const [isLoadingProducts, setIsLoadingProducts] = useState(true);
  const [token, setToken] = useState<string | null>(null);
  const [userEmail, setUserEmail] = useState<string | null>(null);
  const [companyId, setCompanyId] = useState<string | null>(null);
  const [branchId, setBranchId] = useState<string | null>(null);
  const [lastSale, setLastSale] = useState<SaleResult | null>(null);
  const [showReceipt, setShowReceipt] = useState(false);
  const [discountPercent, setDiscountPercent] = useState(0);
  const [customerName, setCustomerName] = useState("");

  // ══════════════════════════════════════════════════════════════════════════
  //  Load products from backend after authentication
  // ══════════════════════════════════════════════════════════════════════════

  useEffect(() => {
    if (!token) {
      setStatusMessage("Sign in to start selling");
      setIsLoadingProducts(false);
      return;
    }

    const loadProducts = async () => {
      try {
        const response = await fetch(`${API_BASE}/products?per_page=100`, {
          headers: { Authorization: `Bearer ${token}` },
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const payload = await response.json();
        const productList: Product[] = payload.data ?? [];
        setProducts(productList);
        setStatusMessage(`✅ ${productList.length} products loaded`);
      } catch (err) {
        setStatusMessage(
          `⚠️ Could not load products: ${err instanceof Error ? err.message : "Connection error"}`,
        );
      } finally {
        setIsLoadingProducts(false);
      }
    };

    loadProducts();
  }, [token]);

  // ══════════════════════════════════════════════════════════════════════════
  //  Search / filter products
  // ══════════════════════════════════════════════════════════════════════════

  const filteredProducts = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return products;

    return products.filter(
      (p) =>
        [p.name, p.generic_name, p.sku, p.barcode, p.category?.name].some(
          (v) => v && v.toLowerCase().includes(q),
        ) && p.is_active,
    );
  }, [products, query]);

  // ══════════════════════════════════════════════════════════════════════════
  //  Cart math
  // ══════════════════════════════════════════════════════════════════════════

  const subtotal = cart.reduce((sum, i) => sum + i.unit_price * i.quantity, 0);
  const discountAmount = subtotal * (discountPercent / 100);
  const taxableAmount = subtotal - discountAmount;
  const tax = taxableAmount * 0.08;
  const total = taxableAmount + tax;

  // ══════════════════════════════════════════════════════════════════════════
  //  Cart actions
  // ══════════════════════════════════════════════════════════════════════════

  const addToCart = (product: Product) => {
    if (product.current_stock <= 0) {
      setStatusMessage(`⛔ ${product.name} is out of stock`);
      return;
    }

    setCart((prev) => {
      const existing = prev.find((i) => i.product_id === product.id);
      if (existing) {
        const qty = existing.quantity + 1;
        return prev.map((i) =>
          i.product_id === product.id
            ? { ...i, quantity: qty, line_total: qty * i.unit_price }
            : i,
        );
      }
      return [
        ...prev,
        {
          product_id: product.id,
          product_name: product.name,
          barcode: product.barcode,
          quantity: 1,
          unit_price: product.sale_price,
          discount: 0,
          tax: 0,
          line_total: product.sale_price,
        },
      ];
    });

    setStatusMessage(`➕ ${product.name} added`);
  };

  const updateQty = (productId: string, delta: number) => {
    setCart((prev) =>
      prev
        .map((i) => {
          if (i.product_id !== productId) return i;
          const qty = Math.max(0, i.quantity + delta);
          return { ...i, quantity: qty, line_total: qty * i.unit_price };
        })
        .filter((i) => i.quantity > 0),
    );
  };

  const removeFromCart = (productId: string) => {
    setCart((prev) => prev.filter((i) => i.product_id !== productId));
    setStatusMessage("🗑️ Item removed");
  };

  // ══════════════════════════════════════════════════════════════════════════
  //  Checkout → calls the POS Sales Module endpoint
  // ══════════════════════════════════════════════════════════════════════════

  const handleCheckout = async () => {
    if (cart.length === 0) {
      setStatusMessage("🛒 Cart is empty — add items first");
      return;
    }
    if (!token) {
      setStatusMessage("🔑 Sign in first");
      return;
    }

    setIsCheckingOut(true);
    setStatusMessage("⏳ Processing sale...");

    try {
      const response = await fetch(`${API_BASE}/pos/sales`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          items: cart.map((i) => ({
            product_id: i.product_id,
            quantity: i.quantity,
            unit_price: i.unit_price,
            discount: i.discount || 0,
            tax: i.tax || 0,
          })),
          customer_name: customerName || "Walk-in Customer",
          branch_id: branchId,
          company_id: companyId,
          payment_method: paymentMethod,
          amount_paid: total,
          discount_amount: discountAmount,
          tax_amount: tax,
        }),
      });

      if (!response.ok) {
        const err = await response.json().catch(() => ({ message: "Unknown error" }));
        throw new Error(err.message || err.error || "Sale failed");
      }

      const payload = await response.json();
      const sale: SaleResult = payload.data;

      setLastSale(sale);
      setShowReceipt(true);
      setCart([]);
      setDiscountPercent(0);
      setCustomerName("");
      setStatusMessage(
        `✅ Invoice #${sale.invoice_number} — ${CURRENCY.format(sale.total_amount)}`,
      );
    } catch (error) {
      setStatusMessage(
        `❌ ${error instanceof Error ? error.message : "Sale failed"}`,
      );
    } finally {
      setIsCheckingOut(false);
    }
  };

  // ══════════════════════════════════════════════════════════════════════════
  //  Receipt preview
  // ══════════════════════════════════════════════════════════════════════════

  const openReceipt = () => {
    if (!lastSale) return;
    const receiptWindow = window.open("", "_blank", "width=380,height=700");
    if (!receiptWindow) return;

    receiptWindow.document.write(`
      <html dir="rtl">
      <head>
        <title>Receipt #${lastSale.invoice_number}</title>
        <style>
          @page { margin: 0; }
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body { font-family: 'Courier New', monospace; font-size: 13px; padding: 16px; color: #222; }
          .center { text-align: center; }
          .header { border-bottom: 2px dashed #888; padding-bottom: 12px; margin-bottom: 12px; }
          .header h2 { font-size: 18px; margin-bottom: 4px; }
          table { width: 100%; border-collapse: collapse; margin: 12px 0; }
          th, td { text-align: left; padding: 6px 4px; border-bottom: 1px dashed #ccc; }
          th { font-size: 11px; text-transform: uppercase; color: #666; }
          .right { text-align: right; }
          .total-row td { font-weight: bold; font-size: 15px; padding-top: 10px; border-top: 2px solid #333; border-bottom: none; }
          .footer { margin-top: 16px; padding-top: 12px; border-top: 2px dashed #888; text-align: center; font-size: 11px; color: #666; }
        </style>
      </head>
      <body>
        <div class="center header">
          <h2>🧾 Pharmacy Receipt</h2>
          <p>#${lastSale.invoice_number}</p>
          <p style="font-size:11px;color:#666">${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
        </div>
        <p><strong>Customer:</strong> ${lastSale.customer_name}</p>
        <p><strong>Payment:</strong> ${lastSale.payment_method.toUpperCase()}</p>
        <table>
          <tr><th>Item</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Total</th></tr>
          ${lastSale.items
            .map(
              (i) =>
                `<tr><td>${i.product_name}</td><td class="right">${i.quantity}</td><td class="right">${CURRENCY.format(i.unit_price)}</td><td class="right">${CURRENCY.format(i.line_total)}</td></tr>`,
            )
            .join("")}
          <tr><td colspan="3" class="right">Subtotal</td><td class="right">${CURRENCY.format(lastSale.items.reduce((s, i) => s + i.line_total, 0))}</td></tr>
          <tr class="total-row"><td colspan="3" class="right">TOTAL</td><td class="right">${CURRENCY.format(lastSale.total_amount)}</td></tr>
        </table>
        <p class="right"><strong>Paid:</strong> ${CURRENCY.format(lastSale.amount_paid)}</p>
        <p class="right"><strong>Change:</strong> ${CURRENCY.format(lastSale.change_amount)}</p>
        <div class="footer">
          <p>Thank you for your visit! 💊</p>
          <p style="margin-top:4px">${lastSale.status === "completed" ? "✅ Paid in full" : "⚠️ Partial payment"}</p>
        </div>
        <script>window.print();<\/script>
      </body>
      </html>
    `);
    receiptWindow.document.close();
  };

  // ══════════════════════════════════════════════════════════════════════════
  //  Render: Unauthenticated → show AuthCard
  // ══════════════════════════════════════════════════════════════════════════

  if (!token) {
    return (
      <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_40%),linear-gradient(135deg,_#020617,_#111827_70%)] px-4 py-6 text-slate-100 sm:px-6 lg:px-8">
        <div className="mx-auto flex max-w-5xl flex-col gap-6">
          <header className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
            <p className="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">
              Z-Syst Pharmacy
            </p>
            <h1 className="mt-2 text-3xl font-semibold text-white sm:text-4xl">
              Point of Sale
            </h1>
            <p className="mt-3 max-w-xl text-sm text-slate-300 sm:text-base">
              Sign in to start selling — every transaction deducts real inventory and logs a full
              audit trail.
            </p>
          </header>
          <AuthCard
            onAuthenticated={(result) => {
              setToken(result.token);
              setUserEmail(result.email);
              setCompanyId(result.company_id);
              setBranchId(result.branch_id);
            }}
          />
        </div>
      </main>
    );
  }

  // ══════════════════════════════════════════════════════════════════════════
  //  Render: Authenticated → full POS layout
  // ══════════════════════════════════════════════════════════════════════════

  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_40%),linear-gradient(135deg,_#020617,_#111827_70%)] px-4 py-6 text-slate-100 sm:px-6 lg:px-8">
      <div className="mx-auto flex max-w-7xl flex-col gap-6">
        {/* ── Header ── */}
        <header className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">
                Pharmacy POS
              </p>
              <h1 className="mt-2 text-3xl font-semibold text-white sm:text-4xl">
                Fast checkout
              </h1>
              <p className="mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                Search products, build a cart, and complete sales with automatic stock deduction and
                receipt generation.
              </p>
            </div>
            <div className="rounded-2xl border border-cyan-400/30 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-200">
              <div className="font-semibold">🏪 Main Pharmacy</div>
              <div className="mt-1 text-cyan-100">{statusMessage}</div>
              {userEmail && <div className="mt-1 text-cyan-100">👤 {userEmail}</div>}
            </div>
          </div>
        </header>

        {/* ── Main grid: Products + Cart ── */}
        <section className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
          {/* ── Left column: Product catalog ── */}
          <div className="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
            <div className="flex flex-col gap-4">
              {/* Toolbar */}
              <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <h2 className="text-xl font-semibold text-white">🔎 Products</h2>
                  <p className="text-sm text-slate-400">
                    {products.length} items —{" "}
                    {Math.round(
                      (products.filter((p) => p.current_stock > 0).length /
                        Math.max(products.length, 1)) *
                        100,
                    )}
                    % in stock
                  </p>
                </div>
              </div>

              {/* Search */}
              <label className="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                <span className="text-xl">🔎</span>
                <input
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  placeholder="Search by name, barcode, SKU, or category…"
                  className="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-500"
                />
                {query && (
                  <button
                    onClick={() => setQuery("")}
                    className="text-sm text-slate-400 hover:text-white"
                  >
                    ✕
                  </button>
                )}
              </label>

              {/* Product grid */}
              <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {isLoadingProducts ? (
                  <div className="col-span-full rounded-2xl border border-dashed border-white/10 bg-slate-800/60 p-6 text-sm text-slate-400">
                    ⏳ Loading products…
                  </div>
                ) : filteredProducts.length === 0 ? (
                  <div className="col-span-full rounded-2xl border border-dashed border-white/10 bg-slate-800/60 p-6 text-sm text-slate-400">
                    {query ? "❌ No matches — try a different search" : "📦 No products yet"}
                  </div>
                ) : (
                  filteredProducts.map((product) => (
                    <button
                      key={product.id}
                      onClick={() => addToCart(product)}
                      disabled={product.current_stock <= 0}
                      className={`rounded-2xl border border-white/10 bg-slate-800/80 p-4 text-left transition-all hover:-translate-y-0.5 hover:border-cyan-400/40 hover:bg-slate-700/80 ${
                        product.current_stock <= 0
                          ? "cursor-not-allowed opacity-40"
                          : "cursor-pointer"
                      }`}
                    >
                      <div className="flex items-center justify-between gap-3">
                        <span className="text-sm font-semibold text-white">
                          {product.name}
                        </span>
                        <span
                          className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${
                            product.current_stock <= 0
                              ? "bg-rose-500/20 text-rose-300"
                              : product.current_stock < 10
                                ? "bg-amber-500/15 text-amber-300"
                                : "bg-emerald-500/15 text-emerald-300"
                          }`}
                        >
                          {product.current_stock <= 0
                            ? "Out"
                            : `${Math.floor(product.current_stock)}`}
                        </span>
                      </div>
                      <p className="mt-2 text-xs uppercase tracking-[0.15em] text-slate-400">
                        {product.sku || product.barcode || "—"}
                      </p>
                      {product.category && (
                        <p className="mt-1 text-xs text-slate-500">
                          {product.category.name}
                        </p>
                      )}
                      <div className="mt-3 flex items-center justify-between">
                        <span className="text-lg font-bold text-cyan-300">
                          {CURRENCY.format(product.sale_price)}
                        </span>
                        <span className="text-xs text-slate-500">Tap +</span>
                      </div>
                    </button>
                  ))
                )}
              </div>
            </div>
          </div>

          {/* ── Right column: Cart & checkout ── */}
          <div className="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-xl font-semibold text-white">🛒 Cart</h2>
                <p className="text-sm text-slate-400">{cart.length} items</p>
              </div>
              <div className="rounded-2xl bg-emerald-500/10 px-3 py-2 text-sm font-medium text-emerald-300">
                {CURRENCY.format(subtotal)}
              </div>
            </div>

            {/* Customer name */}
            <div className="mt-4">
              <label className="text-xs font-medium text-slate-400">Customer name</label>
              <input
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                placeholder="Walk-in customer"
                className="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none placeholder:text-slate-500"
              />
            </div>

            {/* Cart items */}
            <div className="mt-3 space-y-3">
              {cart.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-white/10 bg-slate-800/60 p-4 text-sm text-slate-400">
                  🛒 Cart is empty — tap a product to add it
                </div>
              ) : (
                cart.map((item) => (
                  <div
                    key={item.product_id}
                    className="rounded-2xl border border-white/10 bg-slate-800/70 p-3"
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="truncate font-medium text-white">
                          {item.product_name}
                        </div>
                        <div className="mt-1 text-xs text-slate-400">
                          {CURRENCY.format(item.unit_price)} / each
                        </div>
                      </div>
                      <button
                        onClick={() => removeFromCart(item.product_id)}
                        className="shrink-0 text-sm text-rose-300 hover:text-rose-200"
                      >
                        ✕
                      </button>
                    </div>
                    <div className="mt-3 flex items-center justify-between">
                      <div className="flex items-center rounded-full border border-white/10 bg-slate-950/70 p-1">
                        <button
                          onClick={() => updateQty(item.product_id, -1)}
                          className="flex h-8 w-8 items-center justify-center rounded-full text-lg text-slate-200 hover:bg-slate-700"
                        >
                          −
                        </button>
                        <span className="flex min-w-10 items-center justify-center text-sm font-medium text-white">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() => updateQty(item.product_id, 1)}
                          className="flex h-8 w-8 items-center justify-center rounded-full text-lg text-slate-200 hover:bg-slate-700"
                        >
                          +
                        </button>
                      </div>
                      <div className="text-sm font-semibold text-cyan-300">
                        {CURRENCY.format(item.unit_price * item.quantity)}
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>

            {/* Totals */}
            <div className="mt-5 rounded-2xl border border-white/10 bg-slate-800/70 p-4">
              <div className="space-y-2 text-sm">
                <div className="flex justify-between text-slate-300">
                  <span>Subtotal</span>
                  <span>{CURRENCY.format(subtotal)}</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-slate-300">Discount</span>
                  <input
                    type="number"
                    min={0}
                    max={100}
                    value={discountPercent}
                    onChange={(e) =>
                      setDiscountPercent(Math.max(0, Math.min(100, Number(e.target.value) || 0)))
                    }
                    className="ml-auto w-16 rounded-lg border border-white/10 bg-slate-950/70 px-2 py-1 text-right text-sm text-white outline-none"
                  />
                  <span className="w-4 text-slate-400">%</span>
                  <span className="w-20 text-right text-slate-300">
                    −{CURRENCY.format(discountAmount)}
                  </span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Tax (8%)</span>
                  <span>{CURRENCY.format(tax)}</span>
                </div>
                <div className="flex justify-between border-t border-white/10 pt-2 text-base font-bold text-white">
                  <span>Total</span>
                  <span>{CURRENCY.format(total)}</span>
                </div>
              </div>
            </div>

            {/* Payment method */}
            <div className="mt-4">
              <p className="mb-2 text-sm font-medium text-slate-300">💳 Payment</p>
              <div className="flex flex-wrap gap-2">
                {PAYMENT_METHODS.map((m) => (
                  <button
                    key={m.id}
                    onClick={() => setPaymentMethod(m.id)}
                    className={`rounded-full px-3 py-2 text-sm transition-all ${
                      paymentMethod === m.id
                        ? "bg-cyan-500 text-white shadow-lg shadow-cyan-500/30"
                        : "bg-slate-800 text-slate-300 hover:bg-slate-700"
                    }`}
                  >
                    {m.icon} {m.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Checkout button */}
            <button
              onClick={handleCheckout}
              disabled={isCheckingOut || cart.length === 0}
              className="mt-5 w-full rounded-2xl bg-cyan-500 px-4 py-3 font-semibold text-white shadow-lg shadow-cyan-500/30 transition-all hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
            >
              {isCheckingOut
                ? "⏳ Processing…"
                : `✅ Complete Sale • ${PAYMENT_METHODS.find((m) => m.id === paymentMethod)?.label || paymentMethod}`}
            </button>

            {/* Receipt after sale */}
            {showReceipt && lastSale && (
              <div className="mt-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-4">
                <div className="text-center">
                  <p className="text-lg font-bold text-emerald-300">✅ Sale Complete!</p>
                  <p className="mt-1 text-sm text-emerald-200">
                    Invoice #{lastSale.invoice_number}
                  </p>
                  <p className="mt-1 text-2xl font-bold text-white">
                    {CURRENCY.format(lastSale.total_amount)}
                  </p>
                  <div className="mt-3 flex justify-center gap-3">
                    <button
                      onClick={openReceipt}
                      className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-400"
                    >
                      🧾 Print Receipt
                    </button>
                    <button
                      onClick={() => {
                        setShowReceipt(false);
                        setLastSale(null);
                      }}
                      className="rounded-xl bg-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-600"
                    >
                      New Sale
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </section>
      </div>
    </main>
  );
}

