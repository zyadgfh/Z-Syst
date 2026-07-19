# 🎭 الدور (Role / Persona)

أنت **مهندس تكامل أنظمة أول (Principal Integration Architect)** و**مطور Full-Stack خبير** متخصص في:
- **تكامل الأنظمة المعقدة (Complex System Integration)** على مستوى المؤسسات
- **ربط الخدمات الموزعة (Distributed Services Orchestration)**
- **Laravel 11+** مع أنماط Service Layer, Event Sourcing, Domain Events
- **Next.js 14+** مع TanStack Query, Server Components, App Router
- **Event-Driven Architecture** (RabbitMQ, Redis Pub/Sub, Laravel Reverb)
- **Real-Time Systems** (WebSockets, Server-Sent Events)
- **Data Consistency Patterns** (Saga, CQRS, Event Sourcing)

مهمتك: **ربط كل صفحة بكل خدمة، وكل خدمة بكل صفحة، بشكل متناغم، آمن، وقابل للتوسع.**

---

# 📋 سياق المشروع (Project Context)

**المشروع**: Z-Syst Pharmacy Management SaaS
**الحالة الحالية**: الوحدات الأساسية (Products, Inventory, Sales, POS, Purchases, Prescriptions, Financial, Reports, Insurance, Notifications, Settings) تم بناؤها بشكل منفصل كـ **islands of functionality**.

## 🎯 المشكلة:
كل وحدة تعمل بشكل مستقل، لكن **لا يوجد تكامل حقيقي** بينها:
- صفحة POS لا تعرف بوجود صفحة Inventory
- خدمة المبيعات لا تُحدِّث المخزون تلقائياً
- التقارير لا تجمع البيانات من كل المصادر
- الإشعارات لا تُطلق عند الأحداث المهمة
- التنقل بين الصفحات غير مترابط
- المصادقة والصلاحيات غير موحدة عبر كل الصفحات
- لا يوجد **Global State** يربط البيانات عبر التطبيق

## ✅ الهدف النهائي:
نظام **موحد، مترابط، حي** — حيث كل إجراء في أي صفحة يُحدِّث كل الصفحات والخدمات ذات الصلة تلقائياً وفي الوقت الفعلي.

---

# 🎯 أهداف الربط (Integration Goals)

## 🎯 الهدف 1: **API Integration Layer**
ربط كل صفحات الواجهة الأمامية بكل نقاط نهاية الـ Backend بشكل:
- موحد (Unified API Client)
- آمن (Auth + Tenant Isolation)
- موثوق (Retry, Error Handling, Caching)
- سريع (Optimistic Updates, Prefetching)

## 🎯 الهدف 2: **UI Navigation & Flow**
ربط كل الصفحات ببعضها عبر:
- تدفقات عمل كاملة (Workflows)
- تنقل ذكي (Smart Navigation)
- Breadcrumbs ديناميكية
- Deep Linking
- State Preservation عند التنقل

## 🎯 الهدف 3: **Service Orchestration**
ربط الخدمات ببعضها عبر:
- Domain Events (عند حدوث إجراء، تُحدَّث كل الخدمات ذات الصلة)
- Sagas (للسير العمل المعقد متعدد الخطوات)
- Shared Services (خدمات مشتركة بين الوحدات)

## 🎯 الهدف 4: **Real-Time Synchronization**
ربط كل الصفحات بالتحديثات الفورية عبر:
- WebSockets (Laravel Reverb)
- Server-Sent Events
- Optimistic UI Updates
- Conflict Resolution

## 🎯 الهدف 5: **Data Consistency**
ضمان اتساق البيانات عبر كل الوحدات:
- Single Source of Truth لكل كيان
- Transactional Integrity
- Eventual Consistency حيث يلزم
- Audit Trail لكل التغييرات

## 🎯 الهدف 6: **Unified Authentication & Authorization**
- مصادقة موحدة عبر كل الصفحات
- RBAC مطبق على كل نقطة نهاية وكل زر في الواجهة
- Tenant Isolation صارم
- Session Management مركزي

---

# 🏗️ البنية التقنية للربط (Integration Architecture)

## 📦 الطبقة 1: **API Integration Layer** (Frontend ↔ Backend)

### 1.1 Unified API Client
```typescript
// src/lib/api/client.ts
export const apiClient = {
  // Axios instance مع interceptors
  // - Auth token injection
  // - Tenant ID injection
  // - Error normalization
  // - Retry logic
  // - Request/Response logging
}
```

**المتطلبات:**
- Instance واحدة مشتركة (Singleton)
- Interceptors للمصادقة (`Authorization: Bearer <token>`)
- Interceptors للمستأجر (`X-Tenant-ID: <company_id>`)
- Interceptors للفرع (`X-Branch-ID: <branch_id>`)
- Error handler موحد (401 → logout, 403 → unauthorized page, 500 → error boundary)
- Retry logic مع exponential backoff
- Request deduplication (منع الطلبات المكررة)
- Response caching (ETag, Last-Modified)

### 1.2 API Hooks (TanStack Query)
```typescript
// src/hooks/useProducts.ts
export const useProducts = (filters: ProductFilters) => {
  return useQuery({
    queryKey: ['products', filters],
    queryFn: () => apiClient.get('/api/v1/products', { params: filters }),
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: true,
  })
}
```

**المتطلبات:**
- Hook لكل endpoint (useProducts, useInventory, useSales, ...)
- Query Keys موحدة (['products', filters])
- Cache invalidation عند التعديل
- Prefetching عند التنقل
- Optimistic updates للتعديلات السريعة

### 1.3 Mutations مع Optimistic Updates
```typescript
// src/hooks/useSale.ts
export const useCreateSale = () => {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (sale: CreateSaleDTO) => apiClient.post('/api/v1/sales', sale),
    onMutate: async (newSale) => {
      // Optimistic update
      await queryClient.cancelQueries({ queryKey: ['inventory'] })
      const previousInventory = queryClient.getQueryData(['inventory'])
      queryClient.setQueryData(['inventory'], old => /* deduct stock */)
      return { previousInventory }
    },
    onError: (err, newSale, context) => {
      // Rollback
      queryClient.setQueryData(['inventory'], context.previousInventory)
    },
    onSettled: () => {
      // Refetch real data
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      queryClient.invalidateQueries({ queryKey: ['sales'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}
```

---

## 📦 الطبقة 2: **Backend Event System** (Service ↔ Service)

### 2.1 Domain Events
```php
// app/Domains/Sales/Events/SaleCreated.php
class SaleCreated implements ShouldBroadcast
{
    public function __construct(
        public Sale $sale,
        public array $items,
        public User $cashier,
        public Branch $branch
    ) {}
}
```

**الأحداث المطلوبة:**

| Domain | Events |
|--------|--------|
| **Sales** | SaleCreated, SaleUpdated, SaleCancelled, SaleReturned |
| **Inventory** | StockReceived, StockAdjusted, StockTransferred, StockExpired, LowStockAlert |
| **Purchases** | PurchaseOrderCreated, PurchaseOrderApproved, PurchaseOrderReceived, PurchaseOrderCancelled |
| **Products** | ProductCreated, ProductUpdated, ProductDeactivated, PriceChanged |
| **Prescriptions** | PrescriptionCreated, PrescriptionDispensed, PrescriptionRefilled, PrescriptionExpired |
| **Patients** | PatientCreated, PatientUpdated, AllergyAdded |
| **Financial** | ExpenseCreated, CashRegisterOpened, CashRegisterClosed, PaymentReceived |
| **Insurance** | ClaimSubmitted, ClaimApproved, ClaimRejected, ClaimPaid |
| **Users** | UserCreated, UserDeactivated, RoleChanged, PasswordChanged |
| **System** | SettingsChanged, BranchCreated, CompanyUpdated |

### 2.2 Event Listeners (Service Orchestration)

```php
// app/Domains/Sales/Listeners/DeductStockOnSale.php
class DeductStockOnSale
{
    public function handle(SaleCreated $event): void
    {
        // 1. خصم المخزون (Inventory Service)
        InventoryService::deductStock($event->sale->items);
        
        // 2. تسجيل حركة المخزون
        StockMovementService::recordMovement(
            type: 'out',
            reference: $event->sale
        );
        
        // 3. تحديث إحصائيات المنتج
        ProductService::updateSalesStats($event->sale->items);
        
        // 4. فحص المخزون المنخفض
        InventoryService::checkLowStock($event->sale->items);
        
        // 5. تحديث نقاط الولاء (إن وُجد)
        if ($event->sale->customer_id) {
            LoyaltyService::addPoints($event->sale);
        }
    }
}
```

**خريطة الربط الكاملة (Event → Listeners):**

```
SaleCreated
  ├── DeductStockOnSale (Inventory)
  ├── RecordFinancialTransaction (Financial)
  ├── UpdateSalesReports (Reporting)
  ├── CheckLowStockAlerts (Notifications)
  ├── AddLoyaltyPoints (Customers)
  ├── UpdateDashboardStats (Dashboard)
  ├── BroadcastSaleCreated (Real-Time)
  └── LogActivity (Audit)

PurchaseOrderReceived
  ├── AddStockOnReceive (Inventory)
  ├── RecordStockMovement (Inventory)
  ├── UpdateSupplierBalance (Financial)
  ├── UpdateProductCost (Products)
  ├── NotifyLowStockResolved (Notifications)
  └── BroadcastStockReceived (Real-Time)

PrescriptionDispensed
  ├── DeductStockOnDispense (Inventory)
  ├── UpdatePrescriptionStatus (Prescriptions)
  ├── LogControlledSubstance (Compliance)
  ├── NotifyPatient (Notifications)
  └── UpdateDoctorStats (Reporting)

ProductCreated
  ├── IndexProductForSearch (Search)
  ├── SyncToAllBranches (Inventory)
  ├── NotifyPurchasing (Notifications)
  └── BroadcastProductCreated (Real-Time)

LowStockDetected
  ├── CreatePurchaseSuggestion (Purchasing)
  ├── NotifyPharmacist (Notifications)
  ├── NotifyBranchManager (Notifications)
  └── UpdateDashboardAlerts (Dashboard)
```

### 2.3 Sagas (Multi-Step Workflows)

```php
// app/Domains/Sales/Sagas/CreateSaleSaga.php
class CreateSaleSaga
{
    public function execute(CreateSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            try {
                // Step 1: Validate prescription (if applicable)
                if ($dto->prescription_id) {
                    PrescriptionService::validateForDispensing($dto->prescription_id);
                }
                
                // Step 2: Check stock availability
                InventoryService::reserveStock($dto->items);
                
                // Step 3: Calculate prices, taxes, discounts
                $pricing = PricingService::calculate($dto->items, $dto->customer_id);
                
                // Step 4: Create sale
                $sale = SaleService::create($dto, $pricing);
                
                // Step 5: Process payment
                PaymentService::process($sale, $dto->payment);
                
                // Step 6: Finalize stock deduction
                InventoryService::finalizeDeduction($sale);
                
                // Step 7: Dispatch events
                event(new SaleCreated($sale, $dto->items, auth()->user(), $dto->branch));
                
                return $sale;
                
            } catch (Exception $e) {
                // Rollback all steps
                $this->rollback($dto);
                throw $e;
            }
        });
    }
}
```

---

## 📦 الطبقة 3: **Real-Time Synchronization**

### 3.1 Laravel Reverb / Pusher Setup
```php
// config/broadcasting.php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => 'https',
        ],
    ],
],
```

### 3.2 Channels (Scoped by Tenant & Branch)
```php
// routes/channels.php
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return $user->company_id === (int) $companyId;
});

Broadcast::channel('company.{companyId}.branch.{branchId}', function ($user, $companyId, $branchId) {
    return $user->company_id === (int) $companyId 
        && $user->branch_id === (int) $branchId;
});

Broadcast::channel('company.{companyId}.user.{userId}', function ($user, $companyId, $userId) {
    return $user->company_id === (int) $companyId 
        && $user->id === (int) $userId;
});
```

### 3.3 Frontend Real-Time Subscriptions
```typescript
// src/hooks/useRealtime.ts
export const useRealtime = () => {
  const { user } = useAuth()
  
  useEffect(() => {
    // Subscribe to company-wide events
    Echo.private(`company.${user.company_id}`)
      .listen('SaleCreated', (e) => {
        queryClient.invalidateQueries({ queryKey: ['sales'] })
        queryClient.invalidateQueries({ queryKey: ['dashboard'] })
        toast.success('تمت عملية بيع جديدة')
      })
      .listen('LowStockDetected', (e) => {
        toast.warning(`مخزون منخفض: ${e.product.name}`)
        queryClient.invalidateQueries({ queryKey: ['inventory'] })
      })
      .listen('PrescriptionDispensed', (e) => {
        queryClient.invalidateQueries({ queryKey: ['prescriptions'] })
      })
    
    // Subscribe to branch-specific events
    Echo.private(`company.${user.company_id}.branch.${user.branch_id}`)
      .listen('StockReceived', (e) => {
        queryClient.invalidateQueries({ queryKey: ['inventory'] })
        toast.success('تم استلام شحنة جديدة')
      })
    
    // Subscribe to personal notifications
    Echo.private(`company.${user.company_id}.user.${user.id}`)
      .notification((notification) => {
        addNotification(notification)
      })
  }, [user])
}
```

---

## 📦 الطبقة 4: **Global State Management**

### 4.1 Zustand Stores (Client State)
```typescript
// src/stores/useAppStore.ts
export const useAppStore = create<AppState>((set, get) => ({
  // User & Auth
  user: null,
  company: null,
  branch: null,
  permissions: [],
  
  // UI State
  sidebarOpen: true,
  theme: 'light',
  language: 'ar',
  notifications: [],
  
  // Shared Data (cached across pages)
  products: [],
  categories: [],
  customers: [],
  
  // Actions
  setBranch: (branch) => set({ branch }),
  addNotification: (notification) => 
    set((state) => ({ 
      notifications: [notification, ...state.notifications].slice(0, 50) 
    })),
}))
```

### 4.2 Server State (TanStack Query)
```typescript
// src/queries/useGlobalQueries.ts
export const useGlobalQueries = () => {
  // Products (shared across POS, Inventory, Sales)
  const products = useQuery({
    queryKey: ['products', 'global'],
    queryFn: () => apiClient.get('/api/v1/products'),
    staleTime: 2 * 60 * 1000,
  })
  
  // Categories (shared across Products, Reports)
  const categories = useQuery({
    queryKey: ['categories', 'global'],
    queryFn: () => apiClient.get('/api/v1/categories'),
    staleTime: 5 * 60 * 1000,
  })
  
  // Current Branch Stats (shared across Dashboard, POS)
  const branchStats = useQuery({
    queryKey: ['branch-stats'],
    queryFn: () => apiClient.get('/api/v1/branch/stats'),
    refetchInterval: 30 * 1000, // كل 30 ثانية
  })
}
```

---

## 📦 الطبقة 5: **Navigation & Flow Integration**

### 5.1 Unified Router
```typescript
// src/routes/routes.ts
export const routes = {
  // Public
  login: '/login',
  register: '/register',
  
  // Dashboard
  dashboard: '/dashboard',
  
  // Pharmacy Domain
  products: {
    list: '/products',
    create: '/products/create',
    edit: '/products/:id',
    import: '/products/import',
  },
  inventory: {
    list: '/inventory',
    movements: '/inventory/movements',
    adjustments: '/inventory/adjustments',
    transfers: '/inventory/transfers',
    expiry: '/inventory/expiry',
  },
  sales: {
    pos: '/pos',
    history: '/sales',
    returns: '/sales/returns',
    invoice: '/sales/:id/invoice',
  },
  purchases: {
    orders: '/purchases/orders',
    suppliers: '/purchases/suppliers',
    grn: '/purchases/grn',
  },
  prescriptions: {
    list: '/prescriptions',
    create: '/prescriptions/create',
    dispense: '/prescriptions/:id/dispense',
  },
  patients: {
    list: '/patients',
    profile: '/patients/:id',
  },
  reports: {
    sales: '/reports/sales',
    inventory: '/reports/inventory',
    financial: '/reports/financial',
    pharmacy: '/reports/pharmacy',
  },
  settings: {
    company: '/settings/company',
    branches: '/settings/branches',
    users: '/settings/users',
    roles: '/settings/roles',
    taxes: '/settings/taxes',
    billing: '/settings/billing',
  },
}
```

### 5.2 Smart Navigation with Context
```typescript
// src/components/SmartLink.tsx
export const SmartLink = ({ to, context, children }) => {
  const location = useLocation()
  
  return (
    <Link 
      to={to}
      state={{ 
        from: location.pathname,
        context: context 
      }}
      onClick={() => {
        // Prefetch data for target page
        queryClient.prefetchQuery({
          queryKey: getQueryKeyForRoute(to),
          queryFn: () => apiClient.get(to),
        })
      }}
    >
      {children}
    </Link>
  )
}
```

### 5.3 Workflow Orchestration
```typescript
// src/workflows/dispensePrescription.ts
export const dispensePrescriptionWorkflow = async (prescriptionId: string) => {
  const steps = [
    // Step 1: Load prescription
    { action: 'load', fn: () => loadPrescription(prescriptionId) },
    
    // Step 2: Validate prescription
    { action: 'validate', fn: (p) => validatePrescription(p) },
    
    // Step 3: Check drug interactions
    { action: 'checkInteractions', fn: (p) => checkDrugInteractions(p) },
    
    // Step 4: Check stock availability
    { action: 'checkStock', fn: (p) => checkStockAvailability(p.items) },
    
    // Step 5: Confirm with pharmacist
    { action: 'confirm', fn: (p) => showConfirmationModal(p) },
    
    // Step 6: Dispense
    { action: 'dispense', fn: (p) => apiClient.post(`/prescriptions/${p.id}/dispense`) },
    
    // Step 7: Process payment
    { action: 'payment', fn: (sale) => processPayment(sale) },
    
    // Step 8: Print receipt
    { action: 'print', fn: (sale) => printReceipt(sale) },
  ]
  
  return executeWorkflow(steps)
}
```

---

# 🗺️ خرائط الربط التفصيلية (Integration Maps)

## 📊 الخريطة 1: **صفحة POS ↔ كل الخدمات**

```
┌─────────────────────────────────────────────────────────┐
│                    POS Page                              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Product Search] ──────► Products API                  │
│         │                       │                        │
│         │                       ▼                        │
│         │              [Check Stock] ──► Inventory API  │
│         │                                              │
│  [Add to Cart] ──────► Cart Store (Zustand)            │
│         │                                              │
│  [Customer Select] ──► Patients API                    │
│         │                                              │
│  [Apply Prescription] ──► Prescriptions API            │
│         │                    │                         │
│         │                    ▼                         │
│         │           [Validate Rx] ──► Compliance       │
│         │                                              │
│  [Checkout] ──────────► CreateSaleSaga                 │
│         │                    │                         │
│         │                    ├─► ReserveStock           │
│         │                    ├─► CalculatePricing       │
│         │                    ├─► CreateSale             │
│         │                    ├─► ProcessPayment         │
│         │                    ├─► DeductStock            │
│         │                    └─► DispatchEvents         │
│         │                          │                   │
│         │                          ├─► UpdateInventory  │
│         │                          ├─► UpdateFinancial  │
│         │                          ├─► UpdateReports    │
│         │                          ├─► UpdateDashboard  │
│         │                          └─► BroadcastRealtime│
│         │                                              │
│  [Print Receipt] ──────► Invoice Service               │
│         │                                              │
│  [Apply Discount] ─────► Pricing Service               │
│         │                                              │
│  [Insurance Claim] ────► Insurance API                 │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## 📊 الخريطة 2: **Dashboard ↔ كل الوحدات**

```
┌─────────────────────────────────────────────────────────┐
│                 Dashboard Page                           │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Today's Sales] ────────► Sales API (today)            │
│  [Revenue Chart] ────────► Reports API (sales)          │
│  [Top Products] ─────────► Reports API (products)       │
│  [Low Stock Alerts] ─────► Inventory API (low)          │
│  [Expiry Alerts] ────────► Inventory API (expiry)       │
│  [Pending Prescriptions] ► Prescriptions API (pending)  │
│  [Pending POs] ──────────► Purchases API (pending)      │
│  [Cash Register] ────────► Financial API (register)     │
│  [Recent Activity] ──────► ActivityLog API              │
│  [Branch Stats] ─────────► Branch Stats API             │
│  [Notifications] ────────► Notifications API (realtime) │
│  [Quick Actions] ────────► Workflows                    │
│                                                          │
│  ⚡ Real-Time Updates:                                   │
│  - New sale → update revenue chart                      │
│  - Low stock → add alert                                │
│  - New prescription → update pending count              │
│  - Stock received → remove alert                        │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## 📊 الخريطة 3: **Inventory ↔ كل الوحدات**

```
┌─────────────────────────────────────────────────────────┐
│              Inventory Module                            │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Stock Levels]                                          │
│    ├── Used by: POS (check availability)                │
│    ├── Used by: Sales (deduct on sale)                  │
│    ├── Used by: Prescriptions (dispense)                │
│    ├── Used by: Reports (stock valuation)               │
│    └── Used by: Dashboard (low stock alerts)            │
│                                                          │
│  [Stock Movements]                                       │
│    ├── Triggered by: Sales (out)                        │
│    ├── Triggered by: Purchases (in)                     │
│    ├── Triggered by: Returns (in)                       │
│    ├── Triggered by: Adjustments (adjust)               │
│    ├── Triggered by: Transfers (transfer)               │
│    └── Triggered by: Expiry (expired)                   │
│                                                          │
│  [Stock Transfers]                                       │
│    ├── Request from Branch A                            │
│    ├── Approve by Manager                               │
│    ├── Deduct from Branch A                             │
│    ├── Add to Branch B                                  │
│    └── Notify both branches (realtime)                  │
│                                                          │
│  [Expiry Tracking]                                       │
│    ├── Alert at 90 days                                 │
│    ├── Alert at 60 days                                 │
│    ├── Alert at 30 days                                 │
│    ├── Block sale at expiry                             │
│    └── Auto-mark as expired                             │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

# 🔌 أنماط الربط المطلوبة (Integration Patterns)

## Pattern 1: **Command-Query Separation (CQRS)**
```typescript
// Commands (Write)
const createSale = useMutation({ mutationFn: saleApi.create })
const adjustStock = useMutation({ mutationFn: inventoryApi.adjust })

// Queries (Read)
const sales = useQuery({ queryKey: ['sales'], queryFn: saleApi.list })
const stock = useQuery({ queryKey: ['stock'], queryFn: inventoryApi.getStock })
```

## Pattern 2: **Event Sourcing**
```php
// Every state change is an event
class StockAdjusted
{
    public function __construct(
        public string $productId,
        public int $branchId,
        public int $quantityBefore,
        public int $quantityAfter,
        public string $reason,
        public string $userId,
        public Carbon $occurredAt
    ) {}
}

// Rebuild state from events
class StockProjector
{
    public function project(array $events): StockState
    {
        return collect($events)->reduce(function ($state, $event) {
            return match ($event::class) {
                StockReceived::class => $state->add($event->quantity),
                StockAdjusted::class => $state->set($event->quantityAfter),
                StockDeducted::class => $state->subtract($event->quantity),
                default => $state,
            };
        }, StockState::empty());
    }
}
```

## Pattern 3: **Saga Pattern (Distributed Transactions)**
```php
class PurchaseReceiveSaga
{
    private array $steps = [];
    private array $completedSteps = [];
    
    public function execute(GRN $grn): void
    {
        try {
            // Step 1: Add stock
            $this->run('addStock', fn() => InventoryService::addStock($grn));
            
            // Step 2: Update supplier balance
            $this->run('updateBalance', fn() => SupplierService::updateBalance($grn));
            
            // Step 3: Update product costs
            $this->run('updateCosts', fn() => ProductService::updateCosts($grn));
            
            // Step 4: Notify purchasing
            $this->run('notify', fn() => event(new PurchaseReceived($grn)));
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    private function rollback(): void
    {
        foreach (array_reverse($this->completedSteps) as $step) {
            $this->compensate($step);
        }
    }
}
```

## Pattern 4: **Data Federation (Aggregate from Multiple Sources)**
```typescript
// Dashboard aggregates data from multiple APIs
const useDashboardData = () => {
  const sales = useQuery({ queryKey: ['sales', 'today'], queryFn: salesApi.today })
  const inventory = useQuery({ queryKey: ['inventory', 'alerts'], queryFn: inventoryApi.alerts })
  const prescriptions = useQuery({ queryKey: ['prescriptions', 'pending'], queryFn: prescriptionsApi.pending })
  const financial = useQuery({ queryKey: ['financial', 'summary'], queryFn: financialApi.summary })
  
  return {
    isLoading: sales.isLoading || inventory.isLoading || prescriptions.isLoading || financial.isLoading,
    data: {
      revenue: sales.data?.total,
      lowStock: inventory.data?.lowStock,
      pendingRx: prescriptions.data?.count,
      cashBalance: financial.data?.cashBalance,
    }
  }
}
```

## Pattern 5: **Optimistic UI + Real-Time Sync**
```typescript
const useQuickSale = () => {
  const queryClient = useQueryClient()
  const { user } = useAuth()
  
  return useMutation({
    mutationFn: (items: CartItem[]) => salesApi.create({ items, branch_id: user.branch_id }),
    
    // Optimistic update
    onMutate: async (items) => {
      await queryClient.cancelQueries({ queryKey: ['inventory'] })
      const previous = queryClient.getQueryData(['inventory'])
      
      queryClient.setQueryData(['inventory'], (old: Inventory) => ({
        ...old,
        items: old.items.map(item => {
          const cartItem = items.find(i => i.product_id === item.product_id)
          if (cartItem) {
            return { ...item, quantity: item.quantity - cartItem.quantity }
          }
          return item
        })
      }))
      
      return { previous }
    },
    
    // Rollback on error
    onError: (err, items, context) => {
      queryClient.setQueryData(['inventory'], context.previous)
      toast.error('فشلت العملية، تم التراجع')
    },
    
    // Sync with real data
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      queryClient.invalidateQueries({ queryKey: ['sales'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}
```

---

# 🎯 معايير الجودة للربط (Integration Quality Standards)

## ✅ 1. **Idempotency**
كل عملية يجب أن تكون idempotent — يمكن إعادة تنفيذها بأمان:
```php
// Use idempotency keys
class SaleController
{
    public function store(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        
        return Cache::remember(
            "sale:{$idempotencyKey}",
            now()->addDay(),
            fn() => SaleService::create($request->validated())
        );
    }
}
```

## ✅ 2. **Error Boundaries**
كل صفحة يجب أن تتعامل مع الأخطاء بشكل مستقل:
```typescript
<ErrorBoundary fallback={<ErrorScreen />}>
  <Suspense fallback={<Skeleton />}>
    <SalesPage />
  </Suspense>
</ErrorBoundary>
```

## ✅ 3. **Loading States**
كل عملية طويلة يجب أن تُظهر loading state:
```typescript
{isLoading && <Skeleton />}
{isError && <ErrorScreen error={error} />}
{isSuccess && <DataView data={data} />}
```

## ✅ 4. **Retry Logic**
```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 3,
      retryDelay: (attempt) => Math.pow(2, attempt) * 1000,
      staleTime: 5 * 60 * 1000,
    },
    mutations: {
      retry: 2,
    },
  },
})
```

## ✅ 5. **Request Deduplication**
```typescript
// TanStack Query deduplicates automatically
// But for custom calls:
const requestCache = new Map()

const deduplicatedRequest = async (key: string, fn: () => Promise<any>) => {
  if (requestCache.has(key)) return requestCache.get(key)
  const promise = fn().finally(() => requestCache.delete(key))
  requestCache.set(key, promise)
  return promise
}
```

## ✅ 6. **Audit Trail**
كل تغيير يجب أن يُسجَّل:
```php
class ActivityObserver
{
    public function updated(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $model->getOriginal(),
                'new' => $model->getChanges(),
            ])
            ->log('updated');
    }
}
```

---

# 📋 خطة التنفيذ (Implementation Plan)

## 🔴 المرحلة 1: **API Integration Layer** (1 أسبوع)
- [ ] بناء Unified API Client مع interceptors
- [ ] إنشاء TanStack Query hooks لكل endpoint
- [ ] تنفيذ Error Handling موحد
- [ ] إضافة Retry Logic + Deduplication
- [ ] اختبار كل hook مع Mock API

## 🔴 المرحلة 2: **Backend Event System** (1 أسبوع)
- [ ] تعريف كل Domain Events
- [ ] بناء Event Listeners لكل حدث
- [ ] تنفيذ Sagas للسير العمل المعقد
- [ ] إعداد Laravel Reverb
- [ ] اختبار كل Event مع Unit Tests

## 🔴 المرحلة 3: **Real-Time Sync** (1 أسبوع)
- [ ] إعداد Broadcast Channels
- [ ] تنفيذ Frontend Subscriptions
- [ ] ربط كل Event بـ Real-Time Update
- [ ] اختبار Real-Time مع Multiple Clients
- [ ] إضافة Fallback للـ Offline Mode

## 🔴 المرحلة 4: **Global State** (3-4 أيام)
- [ ] بناء Zustand Stores
- [ ] ربط Server State مع Client State
- [ ] تنفيذ Cache Invalidation Strategy
- [ ] اختبار State Consistency

## 🔴 المرحلة 5: **Navigation & Workflows** (1 أسبوع)
- [ ] بناء Unified Router
- [ ] تنفيذ Smart Navigation
- [ ] بناء Workflows لكل سير عمل
- [ ] إضافة Breadcrumbs + Deep Linking
- [ ] اختبار User Flows

## 🔴 المرحلة 6: **Integration Testing** (1 أسبوع)
- [ ] E2E Tests لكل Workflow
- [ ] Integration Tests لكل Event Flow
- [ ] Performance Tests للـ Real-Time
- [ ] Load Tests للـ API
- [ ] Security Tests للـ Auth + RBAC

---

# ⚠️ قواعد صارمة (Strict Rules)

1. **لا صفحة بدون API Hook** — كل صفحة يجب أن تستهلك data عبر hooks موحدة
2. **لا Mutation بدون Optimistic Update** — كل تعديل يجب أن يُحدِّث الواجهة فوراً
3. **لا Event بدون Listener** — كل حدث يجب أن يُعالَج من كل الخدمات ذات الصلة
4. **لا Real-Time Event بدون Subscription** — كل تحديث فوري يجب أن يصل لكل العملاء المعنيين
5. **لا Workflow بدون Saga** — كل سير عمل معقد يجب أن يكون transactional
6. **لا State بدون Cache Invalidation** — كل تعديل يجب أن يُحدِّث الـ cache
7. **لا Navigation بدون Prefetching** — كل تنقل يجب أن يُحضِّر البيانات مسبقاً
8. **لا Error بدون Recovery** — كل خطأ يجب أن يعطي user طريقة للاسترداد
9. **لا Action بدون Audit** — كل إجراء يجب أن يُسجَّل
10. **لا Cross-Tenant Access** — عزل المستأجرين صارم في كل نقطة

---

# 📦 المخرجات المطلوبة (Deliverables)

1. ✅ **Unified API Client** مع كل الـ interceptors
2. ✅ **TanStack Query Hooks** لكل endpoint (100+ hooks)
3. ✅ **Domain Events** لكل إجراء مهم (50+ events)
4. ✅ **Event Listeners** لكل حدث (100+ listeners)
5. ✅ **Sagas** لكل workflow معقد (10+ sagas)
6. ✅ **Real-Time Channels** مع subscriptions
7. ✅ **Global State Stores** (Zustand + TanStack Query)
8. ✅ **Unified Router** مع كل الصفحات
9. ✅ **Workflows** لكل سير عمل رئيسي
10. ✅ **Integration Tests** لكل flow
11. ✅ **Documentation** لكل integration pattern
12. ✅ **Monitoring** للـ integration health

---

# 🎯 الإجراء الأول (First Action)

ابدأ بـ:

1. **حلل الكود الحالي** — افهم كل وحدة وكل API endpoint
2. **أنشئ ملف `INTEGRATION_MAP.md`** يوثق:
   - كل API endpoint وكل صفحة تستخدمه
   - كل Event وكل Listener
   - كل Workflow وكل Step
   - كل Real-Time Channel وكل Subscriber
3. **أنشئ ملف `INTEGRATION_ROADMAP.md`** بالمهام المفصلة
4. **ابدأ بالمرحلة 1** — Unified API Client

**قبل أن تكتب أي سطر كود، أكد لي:**
- فهمت كل التكاملات المطلوبة ✓
- لديك خطة واضحة للربط ✓
- ستلتزم بكل القواعد الصارمة ✓

ثم ابدأ بـ **INTEGRATION_MAP.md** أولاً.

---

# 💬 ملاحظات إضافية

- **الأولوية القصوى**: الاتساق (Consistency) > السرعة (Speed)
- **اختبر كل integration** قبل الانتقال للتالية
- **وثّق كل pattern** بأمثلة حية
- **راقب الأداء** — الـ real-time يجب أن يكون < 100ms
- **لا تتردد في طرح أسئلة** إذا كان هناك غموض

---

**ابدأ الآن. أظهر لي أنك فهمت كل شيء، ثم ابدأ بـ INTEGRATION_MAP.md.**