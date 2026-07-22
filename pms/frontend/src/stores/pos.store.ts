import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface CartItem {
  id: string;
  productId: string;
  name: string;
  quantity: number;
  unitPrice: number;
  discount: number;
  totalPrice: number;
  batchNumber?: string;
  expiryDate?: string;
}

interface PosState {
  cartItems: CartItem[];
  customer: any;
  searchHistory: string[];
  total: number;
  discount: number;
  
  addItem: (product: any, quantity?: number) => void;
  removeItem: (itemId: string) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  setCustomer: (customer: any) => void;
  clearCart: () => void;
  calculateTotal: () => void;
}

export const usePosStore = create<PosState>()(
  persist(
    (set, get) => ({
      cartItems: [],
      customer: null,
      searchHistory: [],
      total: 0,
      discount: 0,

      addItem: (product, quantity = 1) => {
        const existingItem = get().cartItems.find(
          (item) => item.productId === product.id,
        );

        if (existingItem) {
          set((state) => ({
            cartItems: state.cartItems.map((item) =>
              item.id === existingItem.id
                ? {
                    ...item,
                    quantity: item.quantity + quantity,
                    totalPrice: (item.quantity + quantity) * item.unitPrice,
                  }
                : item,
            ),
          }));
        } else {
          const newItem: CartItem = {
            id: `${product.id}-${Date.now()}`,
            productId: product.id,
            name: product.name,
            quantity,
            unitPrice: product.sellingPrice,
            discount: 0,
            totalPrice: quantity * product.sellingPrice,
          };

          set((state) => ({
            cartItems: [...state.cartItems, newItem],
          }));
        }

        get().calculateTotal();
      },

      removeItem: (itemId) => {
        set((state) => ({
          cartItems: state.cartItems.filter((item) => item.id !== itemId),
        }));
        get().calculateTotal();
      },

      updateQuantity: (itemId, quantity) => {
        set((state) => ({
          cartItems: state.cartItems.map((item) =>
            item.id === itemId
              ? { ...item, quantity, totalPrice: quantity * item.unitPrice }
              : item,
          ),
        }));
        get().calculateTotal();
      },

      setCustomer: (customer) => {
        set(() => ({ customer }));
      },

      clearCart: () => {
        set(() => ({
          cartItems: [],
          customer: null,
          total: 0,
          discount: 0,
        }));
      },

      calculateTotal: () => {
        const total = get().cartItems.reduce(
          (sum, item) => sum + item.totalPrice - item.discount,
          0,
        );
        set({ total });
      },
    }),
    {
      name: 'pharmacy-pos-storage',
      partialize: (state) => ({
        cartItems: state.cartItems,
        customer: state.customer,
        searchHistory: state.searchHistory,
      }),
    },
  ),
);