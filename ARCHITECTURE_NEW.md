# 🏛️ Z-Syst Modular Architecture

## البنية المعمارية الجديدة

```
app/
├── Core/                                    # النواة المشتركة
│   ├── Abstracts/                          # Base classes
│   │   ├── AbstractRepository.php
│   │   └── AbstractService.php
│   ├── Contracts/                          # Interfaces
│   │   ├── Repositories/
│   │   └── Services/
│   ├── Enums/                              # Shared enums
│   ├── Exceptions/                         # Global exceptions
│   ├── Traits/                             # Reusable traits
│   ├── ValueObjects/                       # Value objects
│   ├── DTOs/                               # Data transfer objects
│   ├── Events/                             # Domain events
│   ├── Listeners/                          # Event listeners
│   └── Helpers/                            # Helper functions
│
├── Modules/                                # الوحدات الموضوعية
│   ├── Auth/
│   │   ├── Domain/                         # Business logic
│   │   ├── Application/                    # Use cases
│   │   ├── Infrastructure/                 # Implementation
│   │   ├── Database/
│   │   ├── Routes/
│   │   ├── Config/
│   │   ├── Tests/
│   │   └── ModuleServiceProvider.php
│   ├── Products/
│   ├── Inventory/
│   ├── Sales/
│   ├── Purchases/
│   ├── Prescriptions/
│   ├── Insurance/
│   ├── Financials/
│   └── ... (more modules)
│
├── Infrastructure/                         # البنية التحتية
│   ├── Database/
│   ├── Cache/
│   ├── Queue/
│   ├── Storage/
│   ├── Mail/
│   ├── SMS/
│   ├── Payments/
│   ├── ExternalAPIs/
│   └── Observability/
│
├── Shared/                                 # الخدمات المشتركة
│   ├── Middleware/
│   ├── Events/
│   ├── Listeners/
│   ├── Jobs/
│   ├── Commands/
│   ├── Providers/
│   └── Bootstrap/
│
└── Support/                                # أدوات مساعدة
    ├── Helpers/
    ├── Macros/
    └── Utilities/
```

---

## 📋 Naming Conventions

### Namespaces
- `App\Core\*` - النواة المشتركة
- `App\Modules\{ModuleName}\Domain\*` - طبقة المجال
- `App\Modules\{ModuleName}\Application\*` - طبقة التطبيق
- `App\Modules\{ModuleName}\Infrastructure\*` - طبقة البنية التحتية
- `App\Infrastructure\*` - البنية التحتية العامة
- `App\Shared\*` - الخدمات المشتركة

### Class Naming
- `{Entity}Model` (deprecated) → `{Entity}` (new)
- `{Entity}Repository` - Repository implementations
- `{Entity}Service` - Business services
- `{Entity}Controller` - API controllers
- `{Entity}Request` - Form request validation
- `{Entity}Resource` - API resources
- `{Entity}Policy` - Authorization policies
- `{Entity}Event` - Domain events
- `{Entity}Listener` - Event listeners
- `{Entity}Notification` - Notifications
- `{Entity}Job` - Queued jobs
- `{Entity}Command` - Artisan commands

---

## 🔄 DDD Layers

### 1. Domain Layer (`Domain/`)
**Business Logic Only**
- Models
- Value Objects
- Enums
- Events
- Repository Interfaces
- Domain Exceptions

### 2. Application Layer (`Application/`)
**Use Cases & Orchestration**
- Actions / UseCases
- Services
- DTOs
- Form Requests (Validation)
- API Resources
- Query Builders

### 3. Infrastructure Layer (`Infrastructure/`)
**Implementation Details**
- Controllers
- Repository Implementations
- Policies
- Middleware
- Observers

---

## 📦 Creating a New Module

```bash
php artisan make:module Products
```

This creates:
```
app/Modules/Products/
├── Domain/
├── Application/
├── Infrastructure/
├── Database/Migrations/
├── Routes/api.php
├── Config/
├── Tests/
└── ModuleServiceProvider.php
```

---

## 🔌 Module Registration

Each module must have a `ModuleServiceProvider`:

```php
<?php

namespace App\Modules\Products;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind dependencies
        $this->app->bind(
            \App\Modules\Products\Domain\Repositories\ProductRepositoryInterface::class,
            \App\Modules\Products\Infrastructure\Repositories\EloquentProductRepository::class
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/Resources/lang', 'products');
    }
}
```

---

## ✅ Phase Completion Status

- [x] **Phase 1**: Delete temporary files & old directories
  - Deleted: temp files, app/Library/, legacy folders
  
- [x] **Phase 2**: Remove duplicate modules
  - Deleted: Modules/ZSyst/, Modules/Landing/
  
- [x] **Phase 3**: Create modular structure
  - Created: app/Core/, app/Modules/, app/Infrastructure/, app/Shared/
  - Created: Base classes, traits, exceptions, contracts
  
- [ ] **Phase 4**: Migrate existing code to new structure
  - Move Models to Core/Models
  - Move Services to respective Modules
  - Move Controllers to Module Infrastructure
  
- [ ] **Phase 5**: Update namespaces & dependencies
  - Update all imports
  - Register ModuleServiceProvider in AppServiceProvider
  - Test application

---

## 📝 Architecture Decision Records (ADRs)

### ADR-001: Why Modules?
- **Context**: Monolithic structure with 82 models in single folder
- **Decision**: Implement Modular Monolith using Domain-Driven Design
- **Rationale**: Scalability, maintainability, clear separation of concerns
- **Status**: Accepted ✅

### ADR-002: Multi-Tenant Scoping
- **Context**: Multi-SaaS platform with multiple companies and branches
- **Decision**: Use HasCompanyScope trait for automatic query scoping
- **Rationale**: Security, data isolation, tenant-specific operations
- **Status**: Accepted ✅

### ADR-003: Unified Payment Gateway
- **Context**: 16 payment gateway files scattered in app/Library/
- **Decision**: Use app/Services/Payment/Gateways/ with factory pattern
- **Rationale**: Maintainability, extensibility, DRY principle
- **Status**: Accepted ✅

---

**Last Updated**: 2026-07-19  
**Status**: In Progress 🚀  
**Phase**: 3/5 Complete
