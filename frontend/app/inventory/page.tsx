"use client";

import { useEffect, useState } from "react";

type StockItem = {
  id: number;
  productId: number;
  branchId: number;
  quantity: number;
  batchNumber: string | null;
  expiryDate: string | null;
  product: {
    id: number;
    name: string;
    sku: string;
    price: number;
  };
};

type Product = {
  id: number;
  name: string;
  sku: string;
  price: number;
};

export default function InventoryScreen() {
  const [stocks, setStocks] = useState<StockItem[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [message, setMessage] = useState("");
  const [showAddStock, setShowAddStock] = useState(false);
  
  // Form state
  const [selectedProduct, setSelectedProduct] = useState<number | null>(null);
  const [quantity, setQuantity] = useState(0);
  const [batchNumber, setBatchNumber] = useState("");
  const [expiryDate, setExpiryDate] = useState("");
  const [branchId] = useState(1);
  const [token, setToken] = useState<string | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) return;
    loadStocks();
    loadProducts();
  }, [isAuthenticated]);

  const loadStocks = async () => {
    setIsLoading(true);
    try {
      const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost:3001";
      const response = await fetch(`${apiBase}/inventory/branch/${branchId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      if (response.ok) {
        const data = await response.json();
        setStocks(data);
      } else {
        setMessage("Failed to load inventory data");
      }
    } catch {
      setMessage("Error loading inventory");
    } finally {
      setIsLoading(false);
    }
  };

  const loadProducts = async () => {
    try {
      const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost:3001";
      const response = await fetch(`${apiBase}/products/branch/${branchId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      if (response.ok) {
        const data = await response.json();
        setProducts(data);
      }
    } catch {
      // Fallback demo products
      setProducts([
        { id: 1, name: "Paracetamol", sku: "MED-001", price: 5.99 },
        { id: 2, name: "Amoxicillin", sku: "MED-002", price: 12.5 },
        { id: 3, name: "Ibuprofen", sku: "MED-003", price: 8.25 },
      ]);
    }
  };

  const handleAddStock = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct || quantity <= 0) {
      setMessage("Please select a product and enter quantity");
      return;
    }

    try {
      const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost:3001";
      const response = await fetch(`${apiBase}/inventory/stock`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          productId: selectedProduct,
          branchId,
          quantity,
          batchNumber: batchNumber || undefined,
          expiryDate: expiryDate || undefined,
        }),
      });

      if (response.ok) {
        setMessage("Stock added successfully");
        setShowAddStock(false);
        setSelectedProduct(null);
        setQuantity(0);
        setBatchNumber("");
        setExpiryDate("");
        loadStocks();
      } else {
        const error = await response.json();
        setMessage(error.message || "Failed to add stock");
      }
    } catch {
      setMessage("Error adding stock");
    }
  };

  if (!isAuthenticated) {
    return (
      <main className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 px-4 py-8 text-slate-100">
        <div className="mx-auto max-w-2xl">
          <h1 className="mb-6 text-3xl font-bold">Inventory Management</h1>
          <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
            <p className="mb-4 text-slate-300">Please sign in to access inventory</p>
            <button
              onClick={() => {
                // Demo login
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

  return (
    <main className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 px-4 py-8 text-slate-100">
      <div className="mx-auto max-w-7xl">
        <header className="mb-6 flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold">Inventory Management</h1>
            <p className="text-slate-400">Manage stock levels and track inventory</p>
          </div>
          <button
            onClick={() => setShowAddStock(true)}
            className="rounded-xl bg-cyan-500 px-4 py-2 font-semibold text-white hover:bg-cyan-400"
          >
            Add Stock
          </button>
        </header>

        {message && (
          <div className="mb-4 rounded-xl border border-cyan-400/30 bg-cyan-500/10 px-4 py-3 text-cyan-200">
            {message}
          </div>
        )}

        <div className="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <h2 className="mb-4 text-xl font-semibold">Current Stock</h2>
          {isLoading ? (
            <div className="text-center py-8 text-slate-400">Loading inventory...</div>
          ) : stocks.length === 0 ? (
            <div className="text-center py-8 text-slate-400">No stock items found</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-white/10">
                    <th className="px-4 py-2 text-left font-medium">Product</th>
                    <th className="px-4 py-2 text-left font-medium">SKU</th>
                    <th className="px-4 py-2 text-left font-medium">Quantity</th>
                    <th className="px-4 py-2 text-left font-medium">Batch</th>
                    <th className="px-4 py-2 text-left font-medium">Expiry Date</th>
                  </tr>
                </thead>
                <tbody>
                  {stocks.map((stock) => (
                    <tr key={stock.id} className="border-b border-white/5">
                      <td className="px-4 py-2">{stock.product?.name || "N/A"}</td>
                      <td className="px-4 py-2 text-slate-400">{stock.product?.sku || "N/A"}</td>
                      <td className="px-4 py-2">
                        <span className={`font-semibold ${stock.quantity < 10 ? 'text-rose-400' : stock.quantity < 50 ? 'text-amber-400' : 'text-emerald-400'}`}>
                          {stock.quantity}
                        </span>
                      </td>
                      <td className="px-4 py-2">{stock.batchNumber || "-"}</td>
                      <td className="px-4 py-2">
                        {stock.expiryDate ? new Date(stock.expiryDate).toLocaleDateString() : "-"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Add Stock Modal */}
      {showAddStock && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-md rounded-2xl border border-white/10 bg-slate-900 p-6">
            <h3 className="mb-4 text-xl font-semibold">Add New Stock</h3>
            <form onSubmit={handleAddStock} className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Product</label>
                <select
                  value={selectedProduct || ""}
                  onChange={(e) => setSelectedProduct(Number(e.target.value))}
                  className="w-full rounded-xl border border-white/10 bg-slate-800 px-3 py-2 text-white"
                  required
                >
                  <option value="">Select a product</option>
                  {products.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} ({p.sku})
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Quantity</label>
                <input
                  type="number"
                  value={quantity}
                  onChange={(e) => setQuantity(Number(e.target.value))}
                  className="w-full rounded-xl border border-white/10 bg-slate-800 px-3 py-2 text-white"
                  min="1"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Batch Number (Optional)</label>
                <input
                  type="text"
                  value={batchNumber}
                  onChange={(e) => setBatchNumber(e.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-slate-800 px-3 py-2 text-white"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Expiry Date (Optional)</label>
                <input
                  type="date"
                  value={expiryDate}
                  onChange={(e) => setExpiryDate(e.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-slate-800 px-3 py-2 text-white"
                />
              </div>
              <div className="flex gap-3 pt-2">
                <button
                  type="submit"
                  className="flex-1 rounded-xl bg-cyan-500 px-4 py-2 font-semibold text-white hover:bg-cyan-400"
                >
                  Add Stock
                </button>
                <button
                  type="button"
                  onClick={() => setShowAddStock(false)}
                  className="rounded-xl bg-slate-700 px-4 py-2 font-semibold text-white hover:bg-slate-600"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </main>
  );
}