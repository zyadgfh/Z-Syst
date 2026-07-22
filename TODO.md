# Security Vulnerability Fixes - DONE ✅

All 13 security vulnerabilities have been fixed.

## Priority 1: CRITICAL Fixes ✅
- [x] 1. Fix CORS - Restrict wildcard origin (config/cors.php → allowed_origins now uses env vars)
- [x] 2. Set Sanctum Token Expiration (config/sanctum.php → 1440 min / 24h default)
- [x] 3. Fix Legacy Auth - Mass assignment & OTP leak (AuthController.php → use `only()` not `except('password')`, user data no longer returned)
- [x] 4. Fix Webhook HMAC Verification (PaymentWebhookController.php → added HMAC/Signature verification for Paymob & generic gateways)
- [x] 5. Fix Filesystem Public Disk Root (config/filesystems.php → changed from `.` to `storage_path('app/public')`)
- [x] 6. Fix Barcode Login - Remove placeholder email lookup (AuthController.php → now queries by `barcode` field, not email)

## Priority 2: HIGH Fixes ✅
- [x] 7. Add Auth Protection to Demo Product Import (ProductImportController.php → requires auth, uses user's company)
- [x] 8. Fix Admin Middleware - Use RBAC (AdminMiddleware.php → uses Spatie hasRole() with legacy fallback)
- [x] 9. Fix Tenant Bypass via Header (TenantManager.php → authenticated user is primary source, header only for unauthenticated)
- [x] 10. Fix Upload Filenames - Sanitize (HasUploader.php → added Str::slug, random suffix, MIME validation, safe deletion)
- [x] 11. Fix undefined stockMovementService (PrescriptionController.php → injected via app() container)
- [x] 12. Add Rate Limiting to Auth Endpoints (routes/api.php → throttle:5,1 on login; throttle:3,1 on register; throttle:10,1 on OTP verify)
- [x] 13. Fix TrustProxies - Explicit config (TrustProxies.php → set $proxies = '*')

