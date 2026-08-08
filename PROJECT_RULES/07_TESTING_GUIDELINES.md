# Z-Syst Testing Guidelines

## 🧪 معايير الاختبار

### Testing Principles
1. **Test Early, Test Often:** اكتب الاختبارات مبكراً
2. **Independent Tests:** كل test مستقل
3. **Repeatable:** الاختبارات قابلة للتكرار
4. **Fast:** الاختبارات سريعة
5. **Maintainable:** الاختبارات سهلة الصيانة

---

## 📁 هيكل الاختبارات

### Directory Structure
```
tests/
├── Unit/
│   ├── Services/
│   │   ├── ProductServiceTest.php
│   │   └── AuditServiceTest.php
│   └── Models/
│       ├── ProductTest.php
│       └── UserTest.php
├── Feature/
│   ├── Api/
│   │   ├── ProductApiTest.php
│   │   └── AuthApiTest.php
│   └── Admin/
│       ├── ProductControllerTest.php
│       └── UserControllerTest.php
└── TestCase.php
```

---

## 🎯 Unit Testing

### Service Layer Testing
```php
<?php

namespace Tests\Unit\Services;

use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
    }

    public function test_can_create_product()
    {
        $data = [
            'name' => 'Test Product',
            'price' => 10.00,
            'stock' => 100,
            'business_id' => 1,
        ];

        $product = $this->productService->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(10.00, $product->price);
        $this->assertDatabaseHas('products', $data);
    }

    public function test_cannot_create_product_with_invalid_data()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $data = [
            'name' => '', // Invalid: empty name
            'price' => -10, // Invalid: negative price
        ];

        $this->productService->createProduct($data);
    }

    public function test_decrements_stock_on_sale()
    {
        $product = Product::factory()->create(['stock' => 100]);

        $this->productService->decrementStock($product->id, 10);

        $product->refresh();
        $this->assertEquals(90, $product->stock);
    }
}
```

### Model Testing
```php
<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_business()
    {
        $business = Business::factory()->create();
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->assertInstanceOf(Business::class, $product->business);
        $this->assertEquals($business->id, $product->business->id);
    }

    public function test_active_scope_filters_active_products()
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertTrue($activeProducts->first()->is_active);
    }

    public function test_low_stock_scope_filters_low_stock_products()
    {
        Product::factory()->create(['stock' => 5]);
        Product::factory()->create(['stock' => 20]);

        $lowStockProducts = Product::lowStock()->get();

        $this->assertCount(1, $lowStockProducts);
        $this->assertTrue($lowStockProducts->first()->stock <= 10);
    }
}
```

---

## 🌐 Feature Testing

### API Testing
```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_products()
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'price', 'stock'],
                ],
            ]);
    }

    public function test_user_can_create_product()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-create');

        $data = [
            'name' => 'Test Product',
            'price' => 10.00,
            'stock' => 100,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Product',
                    'price' => 10.00,
                ],
            ]);

        $this->assertDatabaseHas('products', $data);
    }

    public function test_unauthorized_user_cannot_create_product()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'price' => 10.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_fails_with_invalid_data()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-create');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'name' => '', // Invalid
                'price' => 'invalid', // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }
}
```

### Controller Testing
```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_products()
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.products.index'));

        $response->assertStatus(200)
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    public function test_store_creates_product()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-create');

        $data = [
            'name' => 'Test Product',
            'price' => 10.00,
            'stock' => 100,
        ];

        $response = $this->actingAs($user)
            ->post(route('admin.products.store'), $data);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', $data);
    }

    public function test_update_modifies_product()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-update');
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'Updated Product',
                'price' => 20.00,
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 20.00,
        ]);
    }

    public function test_destroy_deletes_product()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-delete');
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

---

## 🔐 Security Testing

### Authentication Testing
```php
public function test_unauthenticated_user_cannot_access_protected_route()
{
    $response = $this->getJson('/api/products');

    $response->assertStatus(401);
}

public function test_authenticated_user_can_access_protected_route()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/products');

    $response->assertStatus(200);
}
```

### Authorization Testing
```php
public function test_user_without_permission_cannot_delete_product()
{
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('admin.products.destroy', $product));

    $response->assertStatus(403);
}

public function test_user_with_permission_can_delete_product()
{
    $user = User::factory()->create();
    $user->givePermissionTo('products-delete');
    $product = Product::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('admin.products.destroy', $product));

    $response->assertRedirect();
}
```

### Tenant Isolation Testing
```php
public function test_user_cannot_access_other_tenant_data()
{
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();
    
    $user1 = User::factory()->create(['business_id' => $business1->id]);
    $product1 = Product::factory()->create(['business_id' => $business1->id]);
    $product2 = Product::factory()->create(['business_id' => $business2->id]);

    $response = $this->actingAs($user1)
        ->getJson("/api/products/{$product2->id}");

    $response->assertStatus(404);
}
```

---

## 🏢 Tenancy Testing

### Tenant Context Testing
```php
<?php

namespace Tests\Unit;

use App\Services\TenantResolver;
use App\Models\User;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_resolver_returns_correct_business_id()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        auth()->login($user);

        $resolver = new TenantResolver();
        $businessId = $resolver->resolve();

        $this->assertEquals($business->id, $businessId);
    }

    public function test_global_scope_filters_by_tenant()
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $user1 = User::factory()->create(['business_id' => $business1->id]);
        Product::factory()->count(5)->create(['business_id' => $business1->id]);
        Product::factory()->count(3)->create(['business_id' => $business2->id]);

        auth()->login($user1);

        $products = Product::all();

        $this->assertCount(5, $products);
        $products->each(function ($product) use ($business1) {
            $this->assertEquals($business1->id, $product->business_id);
        });
    }
}
```

---

## 📊 Database Testing

### Using Factories
```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'price' => fake()->randomFloat(2, 1, 100),
            'stock' => fake()->numberBetween(0, 100),
            'business_id' => Business::factory(),
            'is_active' => true,
        ];
    }
}
```

### Using Factories in Tests
```php
public function test_product_creation()
{
    $product = Product::factory()->create([
        'name' => 'Custom Name',
        'price' => 50.00,
    ]);

    $this->assertEquals('Custom Name', $product->name);
    $this->assertEquals(50.00, $product->price);
}

public function test_bulk_product_creation()
{
    $products = Product::factory()->count(10)->create();

    $this->assertCount(10, $products);
    $this->assertDatabaseCount('products', 10);
}
```

---

## 🔄 Transaction Testing

### Database Transactions
```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SaleServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sale_creation_rolls_back_on_error()
    {
        $this->expectException(\Exception::class);

        $service = new SaleService();
        $service->createSale([
            'invalid_data' => true, // Will cause error
        ]);

        // Transaction should rollback
        $this->assertDatabaseCount('sales', 0);
    }
}
```

---

## 🎭 Mocking & Faking

### Mocking External Services
```php
public function test_email_sending_on_product_creation()
{
    Mail::fake();

    $product = Product::factory()->create();

    Mail::assertSent(ProductCreatedMail::class, function ($mail) use ($product) {
        return $mail->hasTo($product->business->email);
    });
}
```

### Faking Queues
```php
public function test_product_export_job_is_queued()
{
    Queue::fake();

    $this->post(route('products.export'), ['format' => 'csv']);

    Queue::assertPushed(ExportProductsJob::class);
}
```

### Faking Storage
```php
public function test_product_image_upload()
{
    Storage::fake('s3');

    $file = UploadedFile::fake()->image('product.jpg');

    $product = Product::factory()->create();
    $product->addMedia($file)->toMediaCollection('images');

    Storage::disk('s3')->assertExists('images/' . $file->hashName());
}
```

---

## 📈 Performance Testing

### Response Time Testing
```php
public function test_api_response_time()
{
    $startTime = microtime(true);

    $this->getJson('/api/products');

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // Convert to ms

    $this->assertLessThan(200, $duration, 'Response time should be less than 200ms');
}
```

### Database Query Count
```php
public function test_product_index_does_not_cause_n_plus_one()
{
    DB::enableQueryLog();

    $this->getJson('/api/products');

    $queryCount = count(DB::getQueryLog());

    $this->assertLessThan(10, $queryCount, 'Should not exceed 10 queries');
}
```

---

## 🧪 Test Data Management

### Using Seeders
```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(BusinessSeeder::class);
    $this->seed(UserSeeder::class);
}
```

### Custom Test Helpers
```php
// tests/Helpers/AuthHelper.php
trait AuthHelper
{
    protected function actingAsAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        return $this->actingAs($admin);
    }
}

// Use in test
class ProductControllerTest extends TestCase
{
    use AuthHelper;

    public function test_admin_can_create_product()
    {
        $this->actingAsAdmin()
            ->post(route('products.store'), $data);
    }
}
```

---

## 📊 Code Coverage

### Enable Coverage
```bash
php artisan test --coverage
```

### Coverage Configuration
```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Http/Middleware</directory>
        <directory>./app/Exceptions</directory>
    </exclude>
</coverage>
```

---

## 🚨 Common Testing Pitfalls

### ❌ Bad Practices
```php
// Hard-coded test data
public function test_product_creation()
{
    $product = new Product();
    $product->name = 'Test Product';
    $product->price = 10.00;
    $product->save();
    
    $this->assertEquals('Test Product', $product->name);
}

// Testing implementation details
public function test_product_name_is_stored_in_database()
{
    Product::factory()->create(['name' => 'Test']);
    
    $this->assertDatabaseHas('products', ['name' => 'Test']);
}

// Dependent tests
public function test_one()
{
    // Creates product
}

public function test_two()
{
    // Depends on test_one creating product
}
```

### ✅ Good Practices
```php
// Use factories
public function test_product_creation()
{
    $product = Product::factory()->create(['name' => 'Test Product']);
    
    $this->assertEquals('Test Product', $product->name);
}

// Test behavior, not implementation
public function test_user_can_create_product()
{
    $user = User::factory()->create();
    $data = ['name' => 'Test Product', 'price' => 10.00];
    
    $response = $this->actingAs($user)
        ->post(route('products.store'), $data);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('products', $data);
}

// Independent tests
public function test_one()
{
    // Creates and uses its own data
}

public function test_two()
{
    // Creates and uses its own data
}
```

---

## 📚 Resources

### Testing Resources
- [Laravel Testing](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Laravel](https://testing-laravel.com/)

### Testing Best Practices
- [Laravel Best Practices - Testing](https://github.com/alexeymezenin/laravel-best-practices#testing)
- [Clean Code Tests](https://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)

---

**آخر تحديث:** 2026-08-07
