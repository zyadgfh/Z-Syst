# Auth Module

## Overview
The Auth Module handles all authentication, authorization, and user management operations for the Z-Syst application.

## Structure

### Domain Layer (`Domain/`)
- **Contracts/**: Interfaces defining domain contracts
  - `UserRepositoryInterface`: User data access contract
- **Events/**: Domain events
  - `UserRegistered`: Fired when a new user registers

### Application Layer (`Application/`)
- **Services/**: Business logic orchestration
  - `AuthService`: Authentication service with token management

### Infrastructure Layer (`Infrastructure/`)
- **Controllers/**: HTTP controllers
  - `AuthController`: Handles registration, login, logout, profile operations
  - `PasswordResetController`: Handles password resets and changes
- **Requests/**: Form request validation
  - `LoginRequest`: Login form validation
  - `RegisterRequest`: Registration form validation
- **Repositories/**: Data access implementations
  - `UserRepository`: User data access

### Database (`Database/`)
- **Migrations/**: Database schema changes
  - `create_auth_tables.php`: Creates 2FA and OAuth tables
- **Seeders/**: Database seeders

### Routes (`Routes/`)
- **api.php**: API endpoint definitions

### Configuration (`Config/`)
- **auth.php**: Module-specific configuration

## API Endpoints

### Public Routes
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password

### Protected Routes (Requires auth:sanctum)
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/profile` - Get user profile
- `PUT /api/v1/auth/profile` - Update user profile
- `POST /api/v1/auth/change-password` - Change password

## Usage

### Registering the Module
The Auth module is automatically loaded via `ModuleServiceProvider` in `app/Shared/Providers/ModuleServiceProvider.php`.

### Service Container Bindings
```php
// In app/Modules/Auth/AuthModule.php
$this->app->bind(
    \App\Modules\Auth\Domain\Contracts\UserRepositoryInterface::class,
    \App\Modules\Auth\Infrastructure\Repositories\UserRepository::class
);
```

### Using AuthService
```php
use App\Modules\Auth\Application\Services\AuthService;

$authService = app(AuthService::class);
$isValid = $authService->verifyCredentials($email, $password);
$token = $authService->createToken($user);
```

## Configuration

Module configuration is in `Config/auth.php`:
- `AUTH_ENABLE_2FA`: Enable two-factor authentication (default: true)
- `AUTH_ENABLE_OTP`: Enable OTP functionality (default: true)
- `PASSWORD_RESET_TIMEOUT`: Password reset link timeout in minutes (default: 60)

## Future Enhancements
- [ ] Two-factor authentication implementation
- [ ] OAuth2 integration
- [ ] Social login (Google, GitHub, etc.)
- [ ] Session management
- [ ] Audit logging for security events
