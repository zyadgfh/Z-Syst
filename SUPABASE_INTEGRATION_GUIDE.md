# Supabase Integration Guide for Z-Syst Pharmacy Management System

## Overview
Comprehensive guide for integrating Supabase with the Z-Syst Pharmacy Management System for authentication, real-time subscriptions, and storage.

## Features Implemented

### 1. Supabase Authentication ✅
- User registration with Supabase Auth
- Login with Supabase Auth
- Token management and refresh
- Password reset via Supabase
- User profile management
- Laravel-Supabase user linking

### 2. Supabase Storage ✅
- File upload/download
- Multiple file upload
- Public and signed URLs
- File deletion
- Folder management
- Integration with Laravel's storage system

### 3. Real-time Subscriptions ✅
- Real-time database changes
- Sales updates
- Inventory notifications
- System notifications

### 4. Database Integration ✅
- Supabase PostgreSQL connection
- Raw SQL execution
- Custom function calls
- Transaction support

## Installation Steps

### 1. Install Supabase PHP SDK
```bash
composer require supabase/supabase-php --with-all-dependencies
```

### 2. Configure Environment Variables
Add to `.env`:
```env
# Supabase Configuration
SUPABASE_URL=your-supabase-project-url
SUPABASE_KEY=your-supabase-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-supabase-service-role-key
SUPABASE_DB_HOST=your-supabase-db-host
SUPABASE_DB_PORT=5432
SUPABASE_DB_DATABASE=your-database-name
SUPABASE_DB_USERNAME=postgres
SUPABASE_DB_PASSWORD=your-database-password
```

### 3. Run Database Migration
```bash
php artisan migrate
```

### 4. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

## Configuration Files

### config/supabase.php
The main configuration file for Supabase integration includes:
- Database connection settings
- Storage configuration
- Real-time settings
- Authentication configuration
- Edge functions setup

## Services Created

### 1. SupabaseService
Location: `app/Services/SupabaseService.php`

**Methods:**
- `insert()` - Insert data into Supabase tables
- `update()` - Update data in Supabase tables
- `delete()` - Delete data from Supabase tables
- `select()` - Select data from Supabase tables
- `subscribe()` - Real-time subscription to tables
- `uploadFile()` - Upload files to Supabase Storage
- `getPublicUrl()` - Get public URLs for files
- `deleteFile()` - Delete files from storage
- `executeRaw()` - Execute raw SQL queries
- `callFunction()` - Call Supabase functions
- `healthCheck()` - Check Supabase connection

### 2. SupabaseAuthService
Location: `app/Services/SupabaseAuthService.php`

**Methods:**
- `register()` - Register new user with Supabase Auth
- `login()` - Login user with Supabase Auth
- `logout()` - Logout from Supabase Auth
- `refreshSession()` - Refresh Supabase session
- `getCurrentUser()` - Get current user from Supabase
- `updateUser()` - Update user metadata
- `sendPasswordReset()` - Send password reset email
- `updatePassword()` - Update user password
- `linkWithLaravelUser()` - Link Supabase and Laravel users
- `verifyToken()` - Verify Supabase JWT token

### 3. SupabaseStorageService
Location: `app/Services/SupabaseStorageService.php`

**Methods:**
- `upload()` - Upload single file
- `uploadMultiple()` - Upload multiple files
- `getPublicUrl()` - Get public URL for file
- `delete()` - Delete single file
- `deleteMultiple()` - Delete multiple files
- `listFiles()` - List files in folder
- `download()` - Download file
- `getMetadata()` - Get file metadata
- `createSignedUrl()` - Create signed URL
- `move()` - Move file within storage
- `copy()` - Copy file within storage

## API Endpoints

### Authentication Endpoints

#### Register
```http
POST /api/v1/supabase/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "phone": "01012345678",
  "business_id": 1
}
```

#### Login
```http
POST /api/v1/supabase/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Logout
```http
POST /api/v1/supabase/logout
Authorization: Bearer {token}
```

#### Refresh Session
```http
POST /api/v1/supabase/refresh
Authorization: Bearer {token}
```

#### Get Current User
```http
GET /api/v1/supabase/me
Authorization: Bearer {token}
```

#### Forgot Password
```http
POST /api/v1/supabase/forgot-password
Content-Type: application/json

{
  "email": "john@example.com"
}
```

#### Reset Password
```http
POST /api/v1/supabase/reset-password
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "reset_token",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### Storage Endpoints

#### Upload File
```http
POST /api/v1/supabase/storage/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: (binary)
path: "uploads/profiles/user_123.jpg"
upsert: false
```

#### Upload Multiple Files
```http
POST /api/v1/supabase/storage/upload-multiple
Authorization: Bearer {token}
Content-Type: multipart/form-data

files[]: (binary files)
prefix: "uploads/products"
```

#### Delete File
```http
POST /api/v1/supabase/storage/delete
Authorization: Bearer {token}
Content-Type: application/json

{
  "path": "uploads/profiles/user_123.jpg"
}
```

#### List Files
```http
GET /api/v1/supabase/storage/list?prefix=uploads&limit=50&offset=0
Authorization: Bearer {token}
```

#### Download File
```http
GET /api/v1/supabase/storage/download?path=uploads/profiles/user_123.jpg
Authorization: Bearer {token}
```

#### Create Signed URL
```http
POST /api/v1/supabase/storage/signed-url
Authorization: Bearer {token}
Content-Type: application/json

{
  "path": "uploads/profiles/user_123.jpg",
  "expires_in": 3600
}
```

## Database Migration

### Migration File
Location: `database/migrations/2026_08_09_050000_add_supabase_fields_to_users_table.php`

**New Fields Added to Users Table:**
- `supabase_id` - Unique identifier from Supabase
- `supabase_access_token` - Current access token
- `supabase_refresh_token` - Refresh token for session renewal
- `supabase_token_expires_at` - Token expiration timestamp

## User Model Updates

### Fillable Fields
Added Supabase-related fields to the fillable array:
```php
protected $fillable = [
    // ... existing fields
    'supabase_id',
    'supabase_access_token',
    'supabase_refresh_token',
    'supabase_token_expires_at',
];
```

### Hidden Fields
Added sensitive Supabase tokens to hidden array:
```php
protected $hidden = [
    'password',
    'remember_token',
    'supabase_access_token',
    'supabase_refresh_token',
];
```

### Casts
Added datetime cast for token expiration:
```php
protected $casts = [
    // ... existing casts
    'supabase_token_expires_at' => 'datetime',
];
```

## Usage Examples

### Authentication
```php
use App\Services\SupabaseAuthService;

$supabaseAuth = new SupabaseAuthService();

// Register user
$result = $supabaseAuth->register('user@example.com', 'password123', [
    'name' => 'John Doe',
    'phone' => '01012345678'
]);

// Login user
$result = $supabaseAuth->login('user@example.com', 'password123');

// Logout user
$supabaseAuth->logout($accessToken);
```

### Storage
```php
use App\Services\SupabaseStorageService;

$storageService = new SupabaseStorageService();

// Upload file
$url = $storageService->upload($file, 'uploads/profiles/user_123.jpg');

// Get public URL
$publicUrl = $storageService->getPublicUrl('uploads/profiles/user_123.jpg');

// Delete file
$storageService->delete('uploads/profiles/user_123.jpg');

// Upload multiple files
$uploadedFiles = $storageService->uploadMultiple($files, 'uploads/products');
```

### Database Operations
```php
use App\Services\SupabaseService;

$supabaseService = new SupabaseService();

// Insert data
$result = $supabaseService->insert('products', [
    'name' => 'Medicine Name',
    'price' => 100.50,
    'stock' => 50
]);

// Select data
$products = $supabaseService->select('products', [
    'category' => 'medicine'
], ['id', 'name', 'price']);

// Update data
$supabaseService->update('products', 123, [
    'price' => 150.00,
    'stock' => 45
]);

// Delete data
$supabaseService->delete('products', 123);
```

### Real-time Subscriptions
```php
use App\Services\SupabaseService;

$supabaseService = new SupabaseService();

// Subscribe to sales changes
$supabaseService->subscribe('sales', function ($payload) {
    echo "New sale: " . json_encode($payload);
});

// Subscribe to inventory changes
$supabaseService->subscribe('inventory', function ($payload) {
    echo "Inventory update: " . json_encode($payload);
});
```

## Security Considerations

### 1. API Keys
- **Anon Key**: Used for client-side operations (limited permissions)
- **Service Role Key**: Used for server-side operations (full access) - MUST be kept secret

### 2. Token Management
- Access tokens are stored in the database
- Refresh tokens allow session renewal
- Tokens expire automatically for security

### 3. File Upload Security
- File size limits enforced (max 10MB)
- File type validation
- Path sanitization
- Signed URLs for temporary access

### 4. Database Security
- All database operations use service role key
- SQL injection protection through parameterized queries
- Row-level security through Supabase policies

## Best Practices

### 1. Authentication Flow
1. User registers/logs in via Supabase Auth
2. Supabase returns access token and refresh token
3. Laravel creates/updates user record with Supabase tokens
4. Laravel issues Sanctum token for API access
5. Both tokens are managed simultaneously

### 2. Storage Management
1. Use organized folder structure (e.g., `uploads/{user_id}/{type}/`)
2. Implement file validation before upload
3. Use signed URLs for temporary access
4. Clean up unused files regularly

### 3. Real-time Subscriptions
1. Subscribe only to relevant tables
2. Implement proper error handling
3. Use rate limiting for subscriptions
4. Handle connection drops gracefully

### 4. Error Handling
- All services include comprehensive error logging
- API responses include error details
- Graceful degradation when Supabase is unavailable
- Fallback to Laravel-only operations when needed

## Troubleshooting

### Common Issues

#### 1. Connection Issues
**Problem**: Cannot connect to Supabase
**Solution**: 
- Verify SUPABASE_URL and keys in `.env`
- Check network connectivity
- Verify Supabase project status

#### 2. Authentication Failures
**Problem**: Login/registration fails
**Solution**:
- Check email configuration
- Verify Supabase Auth settings
- Check user model fillable fields

#### 3. Upload Failures
**Problem**: File upload fails
**Solution**:
- Check file size limits
- Verify storage bucket exists
- Check file permissions

#### 4. Real-time Issues
**Problem**: Real-time subscriptions not working
**Solution**:
- Verify real-time is enabled in Supabase
- Check network connectivity
- Verify channel configuration

## Performance Optimization

### 1. Caching
- Cache Supabase responses when appropriate
- Use Laravel's cache for frequently accessed data
- Implement cache invalidation strategies

### 2. Batch Operations
- Use batch upload for multiple files
- Batch database operations when possible
- Implement queue workers for heavy operations

### 3. Connection Pooling
- Reuse Supabase client instances
- Implement connection pooling
- Monitor connection usage

## Monitoring

### 1. Health Checks
```php
$supabaseService = new SupabaseService();
$isHealthy = $supabaseService->healthCheck();
```

### 2. Logging
All Supabase operations include comprehensive logging:
- Error logging with context
- Success logging for debugging
- Performance logging for optimization

### 3. Metrics
Monitor:
- API response times
- Authentication success rates
- Upload/download success rates
- Real-time subscription count

## Migration from Current System

### Phase 1: Setup (1-2 days)
1. Install Supabase SDK
2. Configure environment variables
3. Run database migrations
4. Test basic connectivity

### Phase 2: Authentication (2-3 days)
1. Update registration flow
2. Update login flow
3. Update logout flow
4. Test authentication end-to-end

### Phase 3: Storage (2-3 days)
1. Update file upload handlers
2. Update file retrieval
3. Update file deletion
4. Test storage operations

### Phase 4: Real-time (3-4 days)
1. Implement real-time subscriptions
2. Update UI for real-time updates
3. Test real-time functionality
4. Optimize performance

### Phase 5: Testing (2-3 days)
1. Comprehensive testing
2. Load testing
3. Security testing
4. Performance testing

## Rollback Plan

If Supabase integration fails:
1. Disable Supabase features in configuration
2. Use Laravel-only authentication
3. Use Laravel's local storage
4. Keep existing authentication flow as fallback

## Support Resources

### Supabase Documentation
- [Supabase PHP SDK](https://github.com/supabase/supabase-php)
- [Supabase Auth](https://supabase.com/docs/guides/auth)
- [Supabase Storage](https://supabase.com/docs/guides/storage)
- [Supabase Realtime](https://supabase.com/docs/guides/realtime)

### Laravel Documentation
- [Laravel Authentication](https://laravel.com/docs/authentication)
- [Laravel Storage](https://laravel.com/docs/filesystem)
- [Laravel API Resources](https://laravel.com/docs/api-resources)

---

**Integration Date**: 2026-08-09
**Version**: 1.0.0
**Status**: Production Ready
**Dependencies**: Supabase PHP SDK v0.0.3