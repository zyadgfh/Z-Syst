# Comprehensive Post-Upgrade Testing Plan

## Purpose
Comprehensive testing plan to ensure all functionality works correctly after Laravel Framework upgrade to 12.61.1+.

## Testing Environment

### Staging Environment
- **URL**: staging.your-domain.com
- **Database**: z_syst_staging
- **PHP Version**: 8.2+
- **Laravel Version**: 12.61.1+
- **Redis**: Configured and accessible
- **Queue Workers**: Running with Supervisor

### Production Environment
- **URL**: production.your-domain.com
- **Database**: z_syst_production
- **PHP Version**: 8.2+
- **Laravel Version**: 12.61.1+
- **Redis**: Configured and accessible
- **Queue Workers**: Running with Supervisor

## Test Categories

### 1. Application Bootstrapping Tests

#### Test 1.1: Application Starts Successfully
```bash
# Test that application loads without errors
php artisan serve --host=0.0.0.0 --port=8000
curl http://localhost:8000
# Expected: 200 OK response
```

#### Test 1.2: Configuration Loads Correctly
```bash
php artisan tinker
>>> app('app.name')
=> 'Z-Syst'
>>> app('app.env')
=> 'production'
>>> app('app.debug')
=> false
```

#### Test 1.3: Database Connection Works
```bash
php artisan tinker
>>> DB::connection()->getPdo()
=> PDO object
>>> DB::table('users')->count()
=> integer count
```

#### Test 1.4: Cache Connection Works
```bash
php artisan tinker
>>> Cache::put('test', 'value')
=> true
>>> Cache::get('test')
=> 'value'
```

### 2. Authentication & Authorization Tests

#### Test 2.1: User Login Works
```bash
# Test admin login
curl -X POST http://staging.your-domain.com/api/v1/sign-in \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
# Expected: 200 OK with token
```

#### Test 2.2: User Registration Works
```bash
# Test user registration
curl -X POST http://staging.your-domain.com/api/v1/sign-up \
  -H "Content-Type: application/json" \
  -d '{"email":"newuser@example.com","password":"password123","phone":"01012345678"}'
# Expected: 200 OK
```

#### Test 2.3: Password Reset Works
```bash
# Test password reset request
curl -X POST http://staging.your-domain.com/api/v1/send-reset-code \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com"}'
# Expected: 200 OK
```

#### Test 2.4: Admin Authorization Works
```bash
# Test admin panel access
curl -X GET http://staging.your-domain.com/admin/dashboard \
  -H "Authorization: Bearer admin-token"
# Expected: 200 OK
```

### 3. Egyptian Payment Gateway Tests

#### Test 3.1: Payment Gateway Configuration

##### Test 3.1.1: Admin Gateway Access
```bash
# Test admin can access payment gateway management
curl -X GET http://staging.your-domain.com/admin/payment-gateways \
  -H "Authorization: Bearer admin-token"
# Expected: 200 OK with gateway list
```

##### Test 3.1.2: Create Gateway Configuration
```bash
# Test creating Vodafone Cash gateway
curl -X POST http://staging.your-domain.com/admin/payment-gateways \
  -H "Authorization: Bearer admin-token" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "gateway_type": "vodafone_cash",
    "is_active": true,
    "config_data": {
      "merchant_id": "test-merchant-id",
      "api_key": "test-api-key",
      "api_secret": "test-api-secret",
      "environment": "sandbox"
    }
  }'
# Expected: 200 OK with created gateway
```

##### Test 3.1.3: Validate Gateway Configuration
```bash
# Test gateway configuration validation
curl -X POST http://staging.your-domain.com/admin/payment-gateways/test-configuration \
  -H "Authorization: Bearer admin-token" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway_type": "vodafone_cash",
    "config_data": {
      "merchant_id": "test-merchant-id",
      "api_key": "test-api-key",
      "api_secret": "test-api-secret"
    }
  }'
# Expected: 200 OK with validation result
```

#### Test 3.2: Payment Processing

##### Test 3.2.1: Get Available Gateways
```bash
# Test POS payment gateway listing
curl -X GET http://staging.your-domain.com/api/v1/payments/gateways?company_id=1 \
  -H "Authorization: Bearer user-token"
# Expected: 200 OK with gateway list
```

##### Test 3.2.2: Process Cash Payment
```bash
# Test cash payment processing
curl -X POST http://stacing.your-domain.com/api/v1/payments/process \
  -H "Authorization: Bearer user-token" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway_id": 1,
    "amount": 100.50,
    "customer_phone": "01012345678",
    "received_amount": 200.00,
    "customer_email": "customer@example.com"
  }'
# Expected: 200 OK with transaction details
```

##### Test 3.2.3: Calculate Change
```bash
# Test change calculation
curl -X POST http://staging.your-domain.com/api/v1/payments/calculate-change \
  -H "Authorization: Bearer user-token" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.50,
    "received_amount": 200.00
  }'
# Expected: 200 OK with change amount
```

##### Test 3.2.4: Verify Payment Status
```bash
# Test payment verification
curl -X POST http://staging.your-domain.com/api/v1/payments/verify \
  -H "Authorization: Bearer user-token" \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 123
  }'
# Expected: 200 OK with payment status
```

##### Test 3.2.5: Process Refund
```bash
# Test refund processing
curl -X POST http://staging.your-domain.com/api/v1/payments/refund \
  -H "Authorization: Bearer user-token" \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 123,
    "amount": 50.25
  }'
# Expected: 200 OK with refund details
```

#### Test 3.3: Subscription Payment Flow

##### Test 3.3.1: Display Payment Options
```bash
# Test subscription payment page loads
curl -X GET http://staging.your-domain.com/payments-gateways/1/1
# Expected: 200 OK with payment options
```

##### Test 3.3.2: Process Subscription Payment
```bash
# Test subscription payment processing
curl -X POST http://staging.your-domain.com/payments/1/1 \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "01012345678",
    "customer_email": "customer@example.com"
  }'
# Expected: Redirect to success/failure page
```

### 4. Webhook Tests

#### Test 4.1: Vodafone Cash Webhook
```bash
# Test Vodafone Cash webhook endpoint
curl -X POST http://staging.your-domain.com/webhooks/vodafone-cash \
  -H "Content-Type: application/json" \
  -H "X-Vodafone-Signature: test-signature" \
  -d '{
    "reference_id": "TEST-123456",
    "status": "success",
    "amount": 100.50
  }'
# Expected: 200 OK
```

#### Test 4.2: Bank Card Webhook
```bash
# Test Bank card webhook endpoint
curl -X POST http://staging.your-domain.com/webhooks/bank-card \
  -H "Content-Type: application/json" \
  -H "X-Payment-Signature: test-signature" \
  -d '{
    "reference_id": "TEST-123456",
    "status": "success",
    "amount": 100.50
  }'
# Expected: 200 OK
```

#### Test 4.3: Fawry Webhook
```bash
# Test Fawry webhook endpoint
curl -X POST http://staging.yourdomain.com/webhooks/fawry \
  -H "Content-Type: application/json" \
  - H "X-Fawry-Signature: test-signature" \
  -d '{
    "merchant_ref_num": "TEST-123456",
    "payment_status": "PAID",
    "amount": 100.50
  }'
# Expected: 200 OK
```

#### Test 4.4: Orange Cash Webhook
```bash
# Test Orange Cash webhook endpoint
curl -X POST http://staging.your-domain.com/webhooks/orange-cash \
  -H "Content-Type: application/json" \
  - H "X-Orange-Signature: test-signature" \
  -d '{
    "reference_id": "TEST-123456",
    "status": "success",
    "amount": 100.50
  }'
# Expected: 200 OK
```

#### Test 4.5: InstaPay Webhook
```bash
# Test InstaPay webhook endpoint
curl -X POST http://staging.your-domain.com/webhooks/instapay \
  -H "Content-Type: application/json" \
  - H "X-Instapay-Signature: test-signature" \
  - -d '{
    "reference_id": "TEST-123456",
    "status": "success",
    "amount": 100.50
  }'
# Expected: 200 OK
```

### 5. Database Operations Tests

#### Test 5.1: Database Migrations
```bash
# Test migrations run successfully
php artisan migrate:status
# Expected: All migrations show as "N/A" (not run)
php artisan migrate --force
# Expected: All migrations run successfully
```

#### Test 5.2: Database Seeding
```bash
# Test seeding works
php artisan db:seed --class=PaymentGatewaySeeder
# Expected: Seeding completes successfully
```

#### Test 5.3: Model Operations
```bash
# Test model operations
php artisan tinker
>>> $user = \App\Models\User::first()
>>> $user->business
=> Business object
>>> $gateway = \App\Models\CompanyPaymentGateway::first()
>>> $gateway->company
=> Business object
```

#### Test 5.4: Query Scopes
```bash
# Test query scopes work
php artisan tinker
>>> \App\Models\CompanyPaymentGateway::byCompany(1)->active()->get()
=> Collection of active gateways
>>> \App\Models\PaymentTransaction::byCompany(1)->byStatus('completed')->get()
=> Collection of completed transactions
```

### 6. Queue Worker Tests

#### Test 6.1: Queue Worker Processes Jobs
```bash
# Test queue workers are running
php artisan queue:work --stop-when-empty --tries=3
# Queue should process pending jobs
```

#### Test 6.2: Failed Job Handling
```bash
# Test failed job handling
php artisan queue:failed
# Should show any failed jobs
php artisan queue:retry all
# Should retry failed jobs
```

#### Test 6.3: Queue Monitoring
```bash
# Test queue monitoring
php artisan queue:monitor
# Should show queue status
```

### 7. Cache Tests

#### Test 7.1: Cache Write/Read
```bash
# Test cache operations
php artisan tinker
>>> Cache::put('test', 'value', 60)
=> true
>>> Cache::get('test')
=> 'value'
>>> Cache::forget('test')
=> true
```

#### Test 7.2: Cache Clearing
```bash
# Test cache clearing
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
# Expected: All caches cleared successfully
```

#### Test 7.3: Redis Connection
```bash
# Test Redis connection
php artisan tinker
>>> Redis::ping()
=> true
>>> Redis::set('test', 'value')
=> true
>>> Redis::get('test')
=> 'value'
```

### 8. Email Functionality Tests

#### Test 8.1: Email Sending
```bash
# Test email sending
php artisan tinker
>>> Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
=> null
# Expected: Email sent successfully
```

#### Test 8.2: Email Configuration
```bash
# Test email configuration
php artisan tinker
>>> config('mail.mailer')
=> 'smtp'
>>> config('mail.host')
=> 'smtp.gmail.com'
```

### 9. API Endpoint Tests

#### Test 9.1: Authentication Required
```bash
# Test authentication is required
curl -X GET http://staging.your-domain.com/api/v1/summary
# Expected: 401 Unauthorized
```

#### Test 9.2: Rate Limiting
```bash
# Test rate limiting works
for i in {1..20}; do
  curl -X POST http://staging.your-domain.com/api/v1/sign-in \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"test"}'
done
# Expected: 429 Too Many Requests after limit
```

#### Test 9.3: All POS Payment Endpoints
```bash
# Test all POS payment endpoints
curl -X GET http://staging.your-domain.com/api/v1/payments/gateways
curl -X POST http://staging.your-domain.com/api/v1/payments/process
curl -X POST http://staging.your-domain.com/api/v1/payments/verify
curl -X POST http://staging.your-domain.com/api/v1/payments/refund
curl -X POST http://staging.your-domain.com/api/v1/payments/calculate-change
curl -X GET http://staging.yadium.com/api/v1/payments/stats
# Expected: All endpoints respond correctly
```

### 10. Security Tests

#### Test 10.1: SQL Injection Protection
```bash
# Test SQL injection protection
curl -X POST http://staging.your-domain.com/api/v1/dues \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"test\' OR 1=1 --"}'
# Expected: 400 Validation Error
```

#### Test 10.2: XSS Protection
```bash
# Test XSS protection
curl -X POST http://staging.your-domain.com/api/v1/products \
  -H "Content-Type: application/json" \
  -d '{"name":"<script>alert(1)</script>"}'
# Expected: 400 Validation Error
```

#### Test 10.3: CSRF Protection
```bash
# Test CSRF protection
curl -X POST http://staging.your-domain.com/admin/payment-gateways \
  -H "Content-Type: application/json" \
  -d '{"gateway_type":"vodafone_cash"}'
# Expected: 419 CSRF Token Mismatch
```

#### Test 10.4: Webhook Signature Verification
```bash
# Test webhook signature verification
curl -X POST http://staging.your-domain.com/webhooks/vodafone-cash \
  -H "Content-Type: application/json" \
  -H "X-Vodafone-Signature: invalid-signature" \
  -d '{"reference_id":"TEST"}'
# Expected: 401 Unauthorized
```

### 11. Performance Tests

#### Test 11.1: Response Time
```bash
# Test API response times
time curl -X GET http://staging.your-domain.com/api/v1/summary
# Expected: Response time < 500ms
```

#### Test 11.2: Database Query Performance
```bash
# Test database query performance
php artisan tinker
>>> $start = microtime(true)
>>> \App\Models\PaymentTransaction::where('status', 'completed')->count()
>>> $end = microtime(true)
>>> ($end - $start) * 1000
=> Response time in ms
# Expected: < 100ms for 1000 records
```

#### Test 11.3: Cache Performance
```bash
# Test cache performance
php artisan tinker
>>> $start = microtime(true)
>>> Cache::remember('test', 60, function() {
    return \App\Models\User::count();
})
>>> $end = microtime(true)
>>> ($end - $start) * 1000
=> Response time in ms
# Expected: < 50ms after first call
```

### 12. UI/UX Tests

#### Test 12.1: Admin Panel Loads
```bash
# Test admin panel loads
curl -X GET http://staging.your-domain.com/admin/dashboard \
  -H "Cookie: session=..."
# Expected: 200 OK with dashboard content
```

#### Test 12.2: Payment Gateway Management UI
```bash
# Test payment gateway management
curl -X GET http://staging.your-domain.com/admin/payment-gateways \
  -H "Cookie: session=..."
# Expected: 200 OK with gateway list
```

#### Test 12.3: Transaction History UI
```bash
# Test transaction history
curl -X GET http://staging.your-domain.com/admin/payment-gateways/1/transactions \
  -H "Cookie: session=..."
# Expected: 200 OK with transaction list
```

### 13. Integration Tests

#### Test 13.1: End-to-End Payment Flow
```bash
# Test complete payment flow
# 1. Get available gateways
# 2. Process payment
# 3. Verify payment
# 4. Process refund
# Expected: All steps complete successfully
```

#### Test 13.2: Subscription Flow
```bash
# Test complete subscription flow
# 1. View payment options
# 2. Select gateway
# 3. Process payment
# 4. Complete subscription
# Expected: Subscription activated successfully
```

#### Test 13.3: POS Sale Flow
```bash
# Test complete POS sale flow
# 1. Create sale
# 2. Process payment
# 3. Update inventory
# 4. Generate receipt
# Expected: Sale completed successfully
```

## Test Execution Order

### Phase 1: Critical Functionality (Days 1-2)
1. Application bootstrapping
2. Database operations
3. Authentication
4. Payment gateway configuration
5. Payment processing
6. Webhook handling

### Phase 2: Core Features (Days 3-4)
7. Email functionality
8. Queue workers
9. Cache operations
10. API endpoints
11. Security tests

### Phase 3: Advanced Features (Days 5-6)
12. Performance tests
13. UI/UX tests
14. Integration tests
15. Load testing

## Test Results Documentation

### Test Results Template
```markdown
## Test Results Summary

### Critical Tests: PASS/FAIL
- Application Bootstrapping: [PASS/FAIL]
- Database Operations: [PASS/FAIL]
- Authentication: [PASS/FAIL]
- Payment Gateways: [PASS/FAIL]
- Webhooks: [PASS/FAIL]

### Core Features: PASS/FAIL
- Email Functionality: [PASS/FAIL]
- Queue Workers: [PASS/FAIL]
- Cache Operations: [PASS/FAIL]
- API Endpoints: [FAIL/PASS]
- Security: [PASS/FAIL]

### Advanced Features: PASS/FAIL
- Performance: [PASS/FAIL]
- UI/UX: [PASS/FAIL]
- Integration: [PASS/FAIL]
```

### Failed Test Remediation
For each failed test:
1. Document the error
2. Identify root cause
3. Implement fix
4. Re-test
5. Document resolution

## Success Criteria

### Critical Success Criteria
- [ ] All critical tests pass
- [ ] No security vulnerabilities
- [ ] All payment gateways work
- [ ] Webhooks process correctly
- [ ] Database operations work

### Core Success Criteria
- [ ] All core features work
- [ ] Email functionality works
- [ ] Queue workers process jobs
- [ ] Cache operations work
- [ ] API endpoints respond correctly

### Advanced Success Criteria
- [ ] Performance is acceptable
- [ ] UI/UX is responsive
- [] Integration tests pass
- [ ] Load tests pass

## Rollback Criteria

### When to Rollback
- If critical tests fail
- If security vulnerabilities remain
- If payment gateways don't work
- If database operations fail
- If authentication breaks

### Rollback Procedure
1. Stop deployment
2. Rollback database migrations
3. Restore previous code version
4. Restart services
5. Verify system stability

## Post-Upgrade Monitoring

### Monitoring Checklist
- [ ] Application logs monitored for errors
- [ ] Database performance monitored
- [ Queue worker performance monitored
- [ ] Cache hit rates monitored
- [ ] API response times monitored
- [ ] Payment gateway performance monitored
- [ ] Webhook success rates monitored

### Alert Thresholds
- Error rate > 1%: Critical alert
- API response time > 1s: Warning alert
- Queue backlog > 100 jobs: Warning alert
- Cache hit rate < 80%: Warning alert
- Payment failure rate > 5%: Critical alert

---

**Created**: 2026-08-09
**Purpose**: Comprehensive testing for Laravel 12.x upgrade
**Status**: Ready for Execution
**Priority**: CRITICAL