# Threat Model - STRIDE Analysis

**Repository:** PharmaMaster Pro (Pharmacy Management System)
**Generated:** 2026-07-22
**Framework:** Laravel 11 + React/Vue Frontend + Mobile App + WhatsApp Integration

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         External Threats                             │
│  (Internet, WhatsApp API, Payment Gateways, Suppliers)              │
└──────────────┬──────────────────────────────────────────────────────┘
               │
        ┌──────▼──────┐
        │   API Layer │  (app/Http/Controllers, app/Http/Routes)
        │   (Laravel) │  [TRUST BOUNDARY: Authentication]
        └──────┬──────┘
               │
        ┌──────▼─────────────┐
        │ Application Logic  │  (app/Models, app/Jobs, app/Services)
        │  Business Rules    │  [TRUST BOUNDARY: Authorization]
        └──────┬─────────────┘
               │
        ┌──────▼──────────────┐
        │  Data Access Layer  │  (database/migrations, ORM)
        │  (Eloquent ORM)     │  [TRUST BOUNDARY: Data Isolation]
        └──────┬──────────────┘
               │
        ┌──────▼──────────────┐
        │   Data Layer        │  (MySQL/Firebase)
        │   (Sensitive Data)  │  [TRUST BOUNDARY: Encryption, Access Control]
        └─────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  Frontend Layer (React/Vue)                                         │
│  - admin dashboard (sensitive operations)                           │
│  - mobile app (field operations, inventory management)              │
│  - pos application (transaction processing)                         │
└─────────────────────────────────────────────────────────────────────┘
```

## Trust Boundaries

### 1. **Authentication Boundary** (API Entry Point)
- Unauthenticated requests → Limited API endpoints
- Authenticated requests (JWT/Session) → User-specific operations
- Admin/Staff Role transitions → Elevated permissions
- Risk: Token bypass, session fixation, privilege escalation

### 2. **Authorization Boundary** (Business Logic)
- Role-based access control (RBAC)
- Feature toggles and pharmacy-specific permissions
- Risk: Broken access control, privilege escalation, data leakage

### 3. **Data Isolation Boundary** (Multi-tenant/Multi-pharmacy)
- Each pharmacy has isolated: inventory, staff, transactions
- Shared: supplier data, payment integrations
- Risk: Cross-tenant data leakage, unauthorized data access

### 4. **External Integration Boundary** (Third-party Services)
- WhatsApp API (invoicing, notifications)
- Payment gateways (transactions)
- Supplier APIs (inventory sync)
- Risk: Man-in-the-middle, API credential exposure, injection attacks

## High-Risk Components

### **CRITICAL Risk Areas:**
1. **app/Http/Controllers** (21 files flagged)
   - User input handling (unsanitized query params, request bodies)
   - Authentication/authorization checks
   - Sensitive operations (payments, inventory adjustments)

2. **app/Models** (2 files flagged as CRITICAL)
   - Business logic validation
   - Relationship access control
   - Data transformation

3. **config/** (3 files flagged)
   - API credentials
   - Database connection strings
   - Feature flags

### **HIGH Risk Areas:**
1. **database/** (149 files)
   - Migration logic for schema changes
   - Seeding operations
   - Query construction

2. **app/Http** (74 HIGH-tier files)
   - Middleware stack (authentication, rate limiting)
   - Route definitions
   - Request/response handling

## STRIDE Threat Categories

### **S — Spoofing**
- **Threats:**
  - Forged JWT tokens
  - Session hijacking via CSRF
  - API consumer impersonation (webhook verification)
- **Mitigation:**
  - Implement JWT signature validation
  - CSRF token verification for state-changing operations
  - Request signature validation for webhooks (WhatsApp)

### **T — Tampering**
- **Threats:**
  - Modify request data in transit (inventory, pricing)
  - SQL injection via unsanitized query parameters
  - Business logic tampering (invoice amounts, discounts)
- **Mitigations:**
  - HTTPS/TLS for all API communication
  - Parameterized queries (Eloquent ORM — verify all usages)
  - Input validation and sanitization
  - Integrity checks on critical data

### **R — Repudiation**
- **Threats:**
  - Users deny performing transactions
  - Admin denies making inventory changes
  - Audit log tampering
- **Mitigations:**
  - Comprehensive audit logging (immutable log storage)
  - Cryptographic signatures on transactions
  - Timestamps and user tracking

### **I — Information Disclosure**
- **Threats:**
  - Expose user PII (names, phone, addresses)
  - Leak API credentials/database passwords
  - Information leakage via error messages
  - Business data exposure (inventory, pricing)
- **Mitigations:**
  - Encrypt sensitive data at rest
  - Mask errors in production
  - Implement field-level access control
  - Proper secret management

### **D — Denial of Service**
- **Threats:**
  - Large request payloads crash API
  - Database query exhaustion
  - Resource-intensive operations (exports, reports)
  - Rate limit bypass
- **Mitigations:**
  - Request size limits
  - Query complexity limits
  - Rate limiting per user/IP
  - Async job queues for heavy operations

### **E — Elevation of Privilege**
- **Threats:**
  - Staff member becomes admin
  - Pharmacy user accesses another pharmacy's data
  - Supplier account escalation to vendor
- **Mitigations:**
  - Role-based access control (RBAC) enforcement
  - Permission checks before every sensitive operation
  - Audit sensitive permission changes
  - API-level authorization on all endpoints

## Vulnerable Code Patterns

### Pattern 1: Unsanitized User Input
```php
// Vulnerable: Direct query parameter use
$query = "SELECT * FROM inventory WHERE sku = '" . $_GET['sku'] . "'";
// Risk: SQL injection

// Safe: Use ORM with parameter binding
$items = Inventory::where('sku', request('sku'))->get();
```

### Pattern 2: Missing Authorization Checks
```php
// Vulnerable: No pharmacy ownership check
public function getInventory($pharmacyId) {
    return Inventory::where('pharmacy_id', $pharmacyId)->get();
}
// Risk: User can access any pharmacy's inventory

// Safe: Verify user's pharmacy access
public function getInventory($pharmacyId) {
    $this->authorize('view', Pharmacy::find($pharmacyId));
    return Inventory::where('pharmacy_id', $pharmacyId)->get();
}
```

### Pattern 3: Sensitive Data Logging
```php
// Vulnerable: Log contains password/token
Log::info('User login', ['email' => $email, 'password' => $password]);

// Safe: Exclude sensitive fields
Log::info('User login', ['email' => $email]);
```

### Pattern 4: Unvalidated External Integration
```php
// Vulnerable: No signature verification on webhook
public function handleWhatsAppWebhook(Request $request) {
    $data = $request->json()->all();
    // Process without verifying sender
}

// Safe: Verify webhook signature
public function handleWhatsAppWebhook(Request $request) {
    if (!$this->verifyWhatsAppSignature($request)) {
        abort(401);
    }
    // Process trusted webhook
}
```

## Scan Focus Areas (Priority)

### **Must-Scan (CRITICAL):**
1. `app/Http/Controllers/*` — User input handling, authorization
2. `app/Models/*` (CRITICAL tier) — Business logic, data access
3. `config/` — Credential exposure, configuration errors
4. `database/migrations/*` — Schema vulnerabilities
5. `routes/api.php`, `routes/web.php` — Endpoint security

### **Should-Scan (HIGH):**
1. `app/Http/Middleware/*` — Authentication, rate limiting
2. `app/Services/*` — Business logic, external integrations
3. `frontend/`, `Mobile App/` — Client-side injection, CSRF
4. `app/Jobs/*` — Background job security, message queues

### **Could-Scan (MEDIUM):**
1. `app/Helpers/*` — Utility functions, validation helpers
2. `tests/` — Test data exposure, mock credential leaks
3. `resources/` — Template injection, XSS vectors

## Known Vulnerabilities to Verify

This codebase has external documentation mentioning:
- WhatsApp invoice feature (webhook security, rate limiting)
- POS system (transaction integrity, offline sync)
- Payment gateway integration (credential management, PCI compliance)
- Role-based access control (admin vs staff vs vendor permissions)
- Multi-pharmacy isolation (data leakage risks)

Hunters should verify these are implemented securely.

## Severity Thresholds

| Threat Type | Severity | CVSS | Example |
|-------------|----------|------|---------|
| SQL injection in admin API | CRITICAL | 9.0+ | Direct DB access via user input |
| Auth bypass on sensitive endpoint | CRITICAL | 9.0+ | Missing role check on payment processing |
| Credential hardcoding | CRITICAL | 9.0+ | API keys in `.env` or config files |
| Cross-tenant data leak | CRITICAL | 8.5+ | Pharmacy user accessing other pharmacy data |
| XSS in invoice display | HIGH | 7.0+ | User-supplied data in PDF/email |
| Missing input validation | HIGH | 7.0+ | Large request payloads, format bypass |
| Weak rate limiting | HIGH | 6.5+ | Brute force on login/API endpoints |
| Unencrypted sensitive data | HIGH | 7.0+ | PII in transit or at rest |
| Missing audit logging | MEDIUM | 5.0+ | No record of who made changes |
| Information disclosure | MEDIUM | 5.0+ | Error messages leak system info |

---

**This threat model is used by Hunters to guide vulnerability searches and prioritize scanning efforts.**
