import React, { useState, useEffect } from 'react';
import { Search, ShoppingCart, AlertCircle, Camera, CreditCard, Smartphone } from 'lucide-react';
import { usePosStore } from '@/stores/pos.store';
import { ProductSearch } from './ProductSearch';
import { CartTable } from './CartTable';
import { PaymentModal } from './PaymentModal';
import { DDIAlert } from './DDIAlert';
import { PrescriptionUploader } from './PrescriptionUploader';

export function PosScreen() {
  const [searchQuery, setSearchQuery] = useState('');
  const [showPayment, setShowPayment] = useState(false);
  const [ddiAlert, setDdiAlert] = useState(null);
  
  const { cartItems, total, addItem, removeItem, clearCart } = usePosStore();

  return (
    <div className="flex h-screen bg-gray-50" dir="rtl">
      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <header className="bg-white shadow-sm p-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-800">نقطة البيع الذكية</h1>
          <div className="flex items-center gap-4">
            <button className="p-2 bg-gray-100 rounded-lg hover:bg-gray-200">
              <Camera className="w-5 h-5" />
            </button>
            <div className="text-left">
              <p className="text-sm text-gray-500">الإجمالي</p>
              <p className="text-2xl font-bold text-green-600">{total.toFixed(2)} ر.س</p>
            </div>
          </div>
        </header>

        {/* Product Search */}
        <div className="p-4 bg-white">
          <ProductSearch onSearch={setSearchQuery} onProductSelect={addItem} />
        </div>

        {/* Cart Section */}
        <div className="flex-1 p-4 overflow-auto">
          <CartTable items={cartItems} onRemove={removeItem} />
        </div>

        {/* Action Buttons */}
        <div className="p-4 bg-white border-t">
          <div className="flex gap-4">
            <button
              onClick={() => setShowPayment(true)}
              disabled={cartItems.length === 0}
              className="flex-1 py-3 px-6 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 disabled:opacity-50"
            >
              <CreditCard className="inline-block w-5 h-5 mr-2" />
              دفع
            </button>
            <button
              onClick={() => setShowPayment(true)}
              disabled={cartItems.length === 0}
              className="flex-1 py-3 px-6 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
            >
              <Smartphone className="inline-block w-5 h-5 mr-2" />
              المحفظة الإلكترونية
            </button>
          </div>
        </div>
      </div>

      {/* Right Sidebar - Quick Actions */}
      <div className="w-80 bg-white shadow-lg p-4 overflow-y-auto">
        <h2 className="text-lg font-semibold mb-4">إجراءات سريعة</h2>
        
        <div className="space-y-3">
          {/* Prescription Upload */}
          <PrescriptionUploader />
          
          {/* Quick Actions */}
          <button className="w-full p-3 bg-purple-50 rounded-lg hover:bg-purple-100 text-right">
            <p className="font-medium">روشتة عبر الواتساب</p>
            <p className="text-sm text-gray-500">أرسل صورة الروشتة وسيتم معالجتها تلقائياً</p>
          </button>
          
          <button className="w-full p-3 bg-orange-50 rounded-lg hover:bg-orange-100 text-right">
            <p className="font-medium">البدائل الذكية</p>
            <p className="text-sm text-gray-500">الدواء الأرخص أو الأكثر ربحية</p>
          </button>
        </div>
      </div>

      {/* Payment Modal */}
      {showPayment && (
        <PaymentModal
          total={total}
          onClose={() => setShowPayment(false)}
          onPaymentComplete={clearCart}
        />
      )}

      {/* DDI Alert */}
      {ddiAlert && (
        <DDIAlert
          alert={ddiAlert}
          onClose={() => setDdiAlert(null)}
        />
      )}
    </div>
  );
}