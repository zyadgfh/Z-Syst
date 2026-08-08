# Z-Syst Coding Standards

## 📝 معايير البرمجة العامة

### PHP Standards (PSR)
- اتباع **PSR-12** (Extended Coding Style)
- اتباع **PSR-4** (Autoloading Standard)
- استخدام **Type Hints** للfunction parameters و return types
- استخدام **Strict Types** عند الحاجة

---

## 🎯 نمط الكود (Code Style)

### Naming Conventions

#### Classes
```php
// ✅ Good
class ProductService
class AuditLogController
class UserManagementService

// ❌ Bad
class product_service
class auditLogController
class usermanagement
```

#### Methods
```php
// ✅ Good
public function createSale(array $data): Sale
public function getUserStatistics(int $businessId): array
protected function generateReceiptNumber(): string

// ❌ Bad
public function CreateSale(array $data) // camelCase
public function get_user_statistics() // snake_case
public function get() // Too generic
```

#### Variables
```php
// ✅ Good
$businessId = 1;
$totalRevenue = 1500.50;
$userPermissions = ['read', 'write'];

// ❌ Bad
$bid = 1; // Too short
$tr = 1500.50; // Unclear
$perms = ['read', 'write']; // Abbreviated
```

#### Constants
```php
// ✅ Good
const MAX_RETRIES = 3;
const DEFAULT_PAGE_SIZE = 15;
const CACHE_TTL = 3600;

// ❌ Bad
const maxRetries = 3; // Not UPPER_CASE
const MAX = 3; // Too generic
```

---

## 📏 Structure & Formatting

### Class Structure
```php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    // 1. Constants
    const DEFAULT_PAGE_SIZE = 15;
    const CACHE_TTL = 3600;

    // 2. Properties
    protected array $cache = [];

    // 3. Constructor
    public function __construct(
        protected AuditService $auditService
    ) {}

    // 4. Public methods
    public function createProduct(array $data): Product
    {
        // Implementation
    }

    // 5. Protected methods
    protected function validateProduct(array $data): bool
    {
        // Implementation
    }

    // 6. Private methods
    private function cacheKey(int $businessId): string
    {
        return "products:{$businessId}";
    }
}
```

### Method Length
- **Max 50 lines** per method
- Split complex methods into smaller ones
- Use helper methods for readability

### Indentation
- **4 spaces** for indentation (no tabs)
- No trailing whitespace
- Maximum line length: **120 characters**

---

## 🏗️ SOLID Principles

### Single Responsibility Principle
```php
// ❌ Bad - Multiple responsibilities
class UserService
{
    public function createUser(array $data)
    {
        // Create user
        // Send email
        // Create audit log
        // Update statistics
    }
}

// ✅ Good - Single responsibility
class UserService
{
    public function createUser(array $data): User
    {
        return User::create($data);
    }
}

class EmailService
{
    public function sendWelcomeEmail(User $user): void
    {
        // Send email
    }
}

class AuditService
{
    public function logCreated(Model $model): AuditLog
    {
        // Create audit log
    }
}
```

### Dependency Inversion Principle
```php
// ❌ Bad - Tight coupling
class OrderService
{
    public function processOrder(array $data)
    {
        $payment = new StripePayment(); // Tight coupling
        $payment->charge($data['amount']);
    }
}

// ✅ Good - Dependency injection
class OrderService
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}

    public function processOrder(array $data)
    {
        $this->paymentGateway->charge($data['amount']);
    }
}
```

---

## 🔤 Type Hints & Return Types

### Always Use Type Hints
```php
// ✅ Good
public function getProduct(int $id): ?Product
{
    return Product::find($id);
}

public function createSale(array $data): Sale
{
    return Sale::create($data);
}

public function getStatistics(int $businessId): array
{
    return ['total' => 100];
}

// ❌ Bad
public function getProduct($id) // No type hints
{
    return Product::find($id);
}
```

### Use Nullable Types
```php
// ✅ Good
public function findUser(int $id): ?User
{
    return User::find($id);
}

// ❌ Bad
public function findUser(int $id) // Returns User|null
{
    return User::find($id);
}
```

---

## 🎯 Comments & Documentation

### When to Comment
- **WHY** not **WHAT** - Explain reasoning, not obvious code
- Complex algorithms
- Business rules
- Workarounds for bugs

### DocBlocks
```php
<?php

/**
 * Create a new sale with inventory deduction
 *
 * @param array $data Sale data including items
 * @return Sale The created sale
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
 * @throws \Exception If transaction fails
 */
public function createSale(array $data): Sale
{
    return DB::transaction(function () use ($data) {
        // Implementation
    });
}
```

### Inline Comments
```php
// ✅ Good - Explains WHY
// Using FEFO (First Expired First Out) for pharmaceutical safety
$products = Product::orderBy('expiry_date', 'asc')->get();

// ❌ Bad - Explains WHAT (obvious)
// Get products ordered by expiry date
$products = Product::orderBy('expiry_date', 'asc')->get();
```

---

## 🔢 Magic Numbers & Strings

### Avoid Magic Numbers
```php
// ❌ Bad
if ($stock < 10) {
    // Low stock
}

// ✅ Good
const LOW_STOCK_THRESHOLD = 10;

if ($stock < self::LOW_STOCK_THRESHOLD) {
    // Low stock
}
```

### Avoid Magic Strings
```php
// ❌ Bad
$user->role = 'admin';

// ✅ Good
class UserRole
{
    const ADMIN = 'admin';
    const STAFF = 'staff';
    const SUPER_ADMIN = 'superadmin';
}

$user->role = UserRole::ADMIN;
```

---

## 🔄 DRY (Don't Repeat Yourself)

### Extract Repeated Code
```php
// ❌ Bad - Repeated logic
public function createSale(array $data)
{
    $sale = Sale::create($data);
    $this->auditService->log('created', $sale);
    return $sale;
}

public function createPurchase(array $data)
{
    $purchase = Purchase::create($data);
    $this->auditService->log('created', $purchase);
    return $purchase;
}

// ✅ Good - Extracted method
protected function createWithAudit(Model $model, array $data): Model
{
    $instance = $model::create($data);
    $this->auditService->log('created', $instance);
    return $instance;
}
```

---

## 🎨 Clean Code Practices

### Meaningful Names
```php
// ❌ Bad
$d = $data;
$x = $user->id;
$y = $user->name;

// ✅ Good
$userData = $data;
$userId = $user->id;
$userName = $user->name;
```

### Boolean Variables
```php
// ✅ Good - Start with is/has/can
$isActive = true;
$hasPermission = false;
$canDelete = true;

// ❌ Bad
$active = true;
$permission = false;
$delete = true;
```

### Small Functions
```php
// ❌ Bad - Too long
public function processOrder(array $data)
{
    // 100 lines of code
}

// ✅ Good - Split into smaller functions
public function processOrder(array $data): Order
{
    $this->validateOrder($data);
    $order = $this->createOrder($data);
    $this->processPayment($order);
    $this->updateInventory($order);
    $this->sendConfirmation($order);
    
    return $order;
}
```

---

## 🧪 Error Handling

### Use Exceptions
```php
// ✅ Good
public function getProduct(int $id): Product
{
    $product = Product::find($id);
    
    if (!$product) {
        throw new ModelNotFoundException("Product not found: {$id}");
    }
    
    return $product;
}

// ❌ Bad
public function getProduct(int $id)
{
    return Product::find($id) ?? null; // Silent failure
}
```

### Custom Exceptions
```php
<?php

namespace App\Exceptions;

class InsufficientStockException extends \Exception
{
    public function __construct(
        int $productId,
        int $requested,
        int $available
    ) {
        parent::__construct(
            "Insufficient stock for product {$productId}. " .
            "Requested: {$requested}, Available: {$available}"
        );
    }
}
```

---

## 🗄️ Database Queries

### Use Eloquent Over Raw SQL
```php
// ✅ Good
$products = Product::where('business_id', $businessId)
    ->where('stock', '>', 0)
    ->get();

// ❌ Bad
$products = DB::select(
    "SELECT * FROM products WHERE business_id = ? AND stock > 0",
    [$businessId]
);
```

### Eager Loading
```php
// ✅ Good - Prevents N+1 queries
$sales = Sale::with(['items.product', 'customer'])->get();

// ❌ Bad - N+1 queries
$sales = Sale::get();
foreach ($sales as $sale) {
    $sale->items; // Query for each sale
}
```

---

## 🔐 Security Best Practices

### Input Validation
```php
// ✅ Good
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ]);
    
    User::create($validated);
}

// ❌ Bad - No validation
public function store(Request $request)
{
    User::create($request->all());
}
```

### Mass Assignment Protection
```php
// ✅ Good - Use fillable
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}

// ❌ Bad - No protection
class User extends Model
{
    protected $guarded = []; // DANGEROUS
}
```

---

## 📊 Performance Best Practices

### Database Indexing
```php
// Add indexes for frequently queried columns
Schema::table('products', function (Blueprint $table) {
    $table->index(['business_id', 'is_active']);
    $table->index('expiry_date');
});
```

### Caching
```php
// ✅ Good - Cache expensive queries
$products = Cache::remember("products:{$businessId}", 3600, function () use ($businessId) {
    return Product::where('business_id', $businessId)->get();
});

// ✅ Good - Invalidate cache on update
$product->update($data);
Cache::forget("products:{$businessId}");
```

---

## 🧪 Testing Standards

### Test Naming
```php
// ✅ Good - Descriptive
public function test_user_can_create_product()
public function test_product_stock_decrements_on_sale()
public function test_insufficient_stock_throws_exception()

// ❌ Bad - Vague
public function test_product()
public function test_1()
```

### Arrange-Act-Assert
```php
public function test_user_can_create_product()
{
    // Arrange
    $user = User::factory()->create();
    $data = [
        'name' => 'Test Product',
        'price' => 10.00,
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('products.store'), $data);

    // Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('products', $data);
}
```

---

## 📚 Resources

### PHP Standards
- [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)
- [PSR-4: Autoloading Standard](https://www.php-fig.org/psr/psr-4/)

### Laravel Best Practices
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Clean Code in Laravel](https://github.com/robertbasic/clean-code-in-laravel)

### Clean Code
- [Clean Code by Robert C. Martin](https://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)

---

**آخر تحديث:** 2026-08-07
