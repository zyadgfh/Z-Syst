# Z-Syst API Guidelines

## 🌐 معايير API العامة

### RESTful API Principles
- استخدام HTTP methods بشكل صحيح
- Resource-based URLs
- Proper status codes
- Consistent response format
- Versioning strategy

---

## 📡 HTTP Methods

### استخدام صحيح للـ HTTP Methods

#### GET - Retrieve Resources
```php
// ✅ Good
GET /api/products              // List all products
GET /api/products/{id}         // Get specific product
GET /api/products?category=1   // Filter products

// ❌ Bad
GET /api/getProducts
GET /api/products/delete/{id}
```

#### POST - Create Resources
```php
// ✅ Good
POST /api/products             // Create new product
POST /api/sales                // Create new sale

// ❌ Bad
POST /api/products/create
POST /api/products/1/update
```

#### PUT/PATCH - Update Resources
```php
// ✅ Good
PUT /api/products/{id}         // Full update
PATCH /api/products/{id}       // Partial update

// ❌ Bad
POST /api/products/{id}/update
```

#### DELETE - Delete Resources
```php
// ✅ Good
DELETE /api/products/{id}      // Delete product

// ❌ Bad
GET /api/products/{id}/delete
POST /api/products/{id}/delete
```

---

## 🔗 URL Structure

### Resource-Based URLs
```php
// ✅ Good - Plural nouns
/api/products
/api/products/{id}
/api/products/{id}/sales
/api/users/{id}/permissions

// ❌ Bad - Verbs in URL
/api/getProducts
/api/createProduct
/api/deleteProduct/{id}
```

### Nested Resources
```php
// ✅ Good - Shallow nesting (max 2 levels)
/api/businesses/{id}/products
/api/products/{id}/stock

// ❌ Bad - Deep nesting
/api/businesses/{id}/warehouses/{id}/products/{id}/stock
```

### Query Parameters
```php
// ✅ Good - Use query parameters for filtering
GET /api/products?category=1&status=active&sort=name
GET /api/products?page=1&per_page=15

// ❌ Bad - Custom URL patterns
GET /api/products/category/1/status/active
```

---

## 📊 Response Format

### Success Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Product Name",
        "price": 10.00
    },
    "message": "Product created successfully",
    "meta": {
        "timestamp": "2026-08-07T00:00:00Z"
    }
}
```

### Collection Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Product 1"
        },
        {
            "id": 2,
            "name": "Product 2"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email must be a valid email address."]
    }
}
```

### Validation Error Response
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Error message 1",
            "Error message 2"
        ]
    }
}
```

---

## 🔢 HTTP Status Codes

### Success Codes
- **200 OK:** Successful GET, PUT, PATCH
- **201 Created:** Successful POST
- **204 No Content:** Successful DELETE

### Client Error Codes
- **400 Bad Request:** Invalid request
- **401 Unauthorized:** Not authenticated
- **403 Forbidden:** Authenticated but no permission
- **404 Not Found:** Resource not found
- **422 Unprocessable Entity:** Validation error
- **429 Too Many Requests:** Rate limit exceeded

### Server Error Codes
- **500 Internal Server Error:** Server error
- **503 Service Unavailable:** Maintenance mode

### Usage Examples
```php
// 200 OK
return response()->json(['success' => true, 'data' => $product], 200);

// 201 Created
return response()->json(['success' => true, 'data' => $product], 201);

// 404 Not Found
return response()->json(['success' => false, 'message' => 'Not found'], 404);

// 422 Validation Error
return response()->json([
    'success' => false,
    'message' => 'Validation error',
    'errors' => $validator->errors()
], 422);
```

---

## 📄 Pagination

### Standard Pagination
```php
// Controller
public function index(Request $request)
{
    $products = Product::paginate($request->per_page ?? 15);

    return response()->json([
        'success' => true,
        'data' => $products->items(),
        'meta' => [
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'last_page' => $products->lastPage(),
        ],
    ]);
}
```

### Cursor Pagination (for large datasets)
```php
public function index(Request $request)
{
    $products = Product::cursorPaginate($request->per_page ?? 15);

    return response()->json([
        'success' => true,
        'data' => $products->items(),
        'meta' => [
            'next_cursor' => $products->nextCursor()?->encode(),
            'prev_cursor' => $products->previousCursor()?->encode(),
        ],
    ]);
}
```

---

## 🔍 Filtering & Sorting

### Filtering
```php
// URL: /api/products?category=1&status=active&min_price=10
public function index(Request $request)
{
    $query = Product::query();

    if ($request->has('category')) {
        $query->where('category_id', $request->category);
    }

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    if ($request->has('min_price')) {
        $query->where('price', '>=', $request->min_price);
    }

    return $query->paginate();
}
```

### Sorting
```php
// URL: /api/products?sort=name&order=desc
public function index(Request $request)
{
    $sortBy = $request->sort ?? 'created_at';
    $order = $request->order ?? 'desc';

    $products = Product::orderBy($sortBy, $order)->paginate();

    return response()->json(['success' => true, 'data' => $products]);
}
```

---

## 🔐 Authentication

### Sanctum Authentication
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Token Generation
```php
// Generate token for user
$token = $user->createToken('api-token')->plainTextToken;

return response()->json([
    'success' => true,
    'token' => $token,
]);
```

### Token Revocation
```php
// Revoke current token
$request->user()->currentAccessToken()->delete();

// Revoke all tokens
$request->user()->tokens()->delete();
```

---

## 🛡️ Authorization

### Permission-Based Authorization
```php
public function store(Request $request)
{
    if (!$request->user()->can('products-create')) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // Create product
}
```

### Role-Based Authorization
```php
public function index(Request $request)
{
    if (!$request->user()->hasRole('admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Admin access required'
        ], 403);
    }

    // Return admin data
}
```

---

## 📝 Validation

### Request Validation
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products-create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.numeric' => 'Price must be a number',
        ];
    }
}
```

### Validation Error Response
```php
public function store(StoreProductRequest $request)
{
    try {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product created successfully'
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating product',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

---

## 🚀 Rate Limiting

### Configure Rate Limiting
```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::apiResource('products', ProductController::class);
});

// Custom rate limiter
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### Rate Limit Response
```json
{
    "success": false,
    "message": "Too many attempts. Please try again later.",
    "retry_after": 60
}
```

---

## 📦 Resource Transformation

### API Resources
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### Using Resources
```php
public function show(Product $product)
{
    return response()->json([
        'success' => true,
        'data' => new ProductResource($product)
    ]);
}

public function index()
{
    $products = Product::paginate();

    return ProductResource::collection($products)->additional([
        'success' => true,
        'meta' => [
            'total' => $products->total(),
        ],
    ]);
}
```

---

## 🔄 Versioning

### URL Versioning
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('products', ProductV2Controller::class);
});
```

### Header Versioning
```php
// Middleware
public function handle($request, Closure $next)
{
    $version = $request->header('API-Version', 'v1');
    $request->attributes->set('api_version', $version);

    return $next($request);
}
```

---

## 📊 API Documentation

### OpenAPI/Swagger
```yaml
openapi: 3.0.0
info:
  title: Z-Syst API
  version: 1.0.0
paths:
  /api/products:
    get:
      summary: List products
      responses:
        '200':
          description: Successful response
    post:
      summary: Create product
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                name:
                  type: string
                price:
                  type: number
```

---

## 🧪 API Testing

### API Test Example
```php
public function test_user_can_create_product()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->postJson('/api/products', [
        'name' => 'Test Product',
        'price' => 10.00,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'price'],
            'message',
        ]);
}
```

---

## 🚨 Error Handling

### Global Exception Handler
```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 500);
    }

    return parent::render($request, $exception);
}
```

### Custom API Exceptions
```php
<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $statusCode;

    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
```

---

## 📚 Resources

### API Design Resources
- [REST API Tutorial](https://restfulapi.net/)
- [JSON API Specification](https://jsonapi.org/)
- [Laravel API Resources](https://laravel.com/docs/eloquent-resources)

### API Documentation Tools
- [Swagger/OpenAPI](https://swagger.io/)
- [Postman](https://www.postman.com/)
- [Insomnia](https://insomnia.rest/)

---

**آخر تحديث:** 2026-08-07
