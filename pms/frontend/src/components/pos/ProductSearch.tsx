import React, { useState, useRef } from 'react';
import { Search, Barcode, Scan } from 'lucide-react';
import { usePosStore } from '@/stores/pos.store';
import { api } from '@/lib/api';

interface Product {
  id: string;
  name: string;
  sellingPrice: number;
  purchasePrice: number;
  barcode?: string;
  activeIngredient?: string;
  aiScore?: number;
}

interface ProductSearchProps {
  onSearch: (query: string) => void;
  onProductSelect: (product: Product) => void;
}

export function ProductSearch({ onSearch, onProductSelect }: ProductSearchProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const searchTimeout = useRef<NodeJS.Timeout>();

  const handleSearch = async (value: string) => {
    setQuery(value);
    onSearch(value);

    if (searchTimeout.current) {
      clearTimeout(searchTimeout.current);
    }

    if (!value.trim()) {
      setResults([]);
      return;
    }

    searchTimeout.current = setTimeout(async () => {
      setLoading(true);
      try {
        const response = await api.get(`/pos/search?query=${value}&searchType=name`);
        setResults(response.data);
      } catch (error) {
        console.error('Search error:', error);
      } finally {
        setLoading(false);
      }
    }, 300);
  };

  const handleBarcodeSearch = async () => {
    if (!query) return;
    setLoading(true);
    try {
      const response = await api.get(`/pos/search?query=${query}&searchType=barcode`);
      if (response.data.length > 0) {
        onProductSelect(response.data[0]);
      }
    } catch (error) {
      console.error('Barcode search error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative">
      <div className="flex gap-2">
        <div className="flex-1 relative">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            value={query}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder="ابحث عن الدواء بالاسم أو المادة الفعالة..."
            className="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            autoFocus
          />
        </div>
        <button
          onClick={handleBarcodeSearch}
          className="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-2"
        >
          <Barcode className="w-5 h-5" />
          باركود
        </button>
        <button className="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-2">
          <Scan className="w-5 h-5" />
          مسح
        </button>
      </div>

      {/* Search Results Dropdown */}
      {results.length > 0 && (
        <div className="absolute top-full mt-2 w-full bg-white shadow-lg rounded-lg border max-h-96 overflow-y-auto z-50">
          {results.map((product) => (
            <div
              key={product.id}
              className="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-0 flex justify-between items-center"
              onClick={() => onProductSelect(product)}
            >
              <div>
                <p className="font-medium">{product.name}</p>
                {product.activeIngredient && (
                  <p className="text-sm text-gray-500">{product.activeIngredient}</p>
                )}
              </div>
              <div className="text-left">
                <p className="font-bold text-green-600">{product.sellingPrice} ر.س</p>
                {product.aiScore && (
                  <p className="text-xs text-gray-400">AI Score: {product.aiScore.toFixed(0)}</p>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {loading && (
        <div className="absolute top-full mt-2 w-full bg-white shadow-lg rounded-lg p-3">
          <p className="text-center text-gray-500">جاري البحث...</p>
        </div>
      )}
    </div>
  );
}