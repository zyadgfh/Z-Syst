# Laravel Framework Upgrade Plan

## Current Status
- **Current Version**: Laravel 10.50.2
- **Target Version**: Laravel 12.61.1+
- **PHP Version**: 8.1
- **Severity**: CRITICAL - Security vulnerabilities found

## Security Vulnerabilities Addressed

### HIGH Priority
- **CRLF Injection in Email** (CVE-2026-48019)
- **Path Confusion in Signed URLs** (PKSA-m5cs-t1y6-qpcs)

## Upgrade Timeline: 2-3 Weeks

### Phase 1: Preparation (Days 1-2)
- [ ] Create upgrade testing branch
- [ ] Backup production database
- [ ] Review Laravel 11.x and 12.x upgrade guides
- [ ] Document current functionality
- [ ] Setup staging environment

### Phase 2: Dependencies Update (Days 3-4)
- [ ] Update Laravel to 11.x
- [ ] Update all dependencies
- [ ] Fix deprecated code
- [ ] Run automated tests
- [ ] Fix test failures

### Phase 3: Major Version Upgrade (Days 5-7)
- [ ] Update Laravel to 12.x
- [ ] Update remaining dependencies
- [ ] Fix breaking changes
- [ ] Update configuration files
- [ ] Test all functionality

### Phase 4: Testing (Days 8-10)
- [ ] Run all automated tests
- [ ] Test payment gateway integrations
- [ ] Test email functionality
- [ ] Test authentication flows
- [ ] Manual UI testing
- [ ] Performance testing

### Phase 5: Deployment (Days 11-12)
- [ ] Deploy to staging
- [ ] Full system testing
- [ ] Deploy to production
- [ ] Monitor closely
- ] Rollback plan ready

## Breaking Changes to Address

### Laravel 10.x → 11.x
1. **PHP Minimum Version**: 8.1 → 8.2
2. **Deprecated Code**: Remove deprecated functions
3. **Configuration**: Update config files
4. **Middleware**: Update middleware signatures
5. **Exceptions**: Update exception handling

### Laravel 11.x → 12.x
1. **PHP Minimum Version**: 8.2 → 8.2
2. **New Features**: Test new functionality
3. **Performance**: Optimize for new features
4. **Security**: Implement new security features

## Prerequisites

### PHP Version Check
```bash
php -v
# Must be at least 8.2 for Laravel 12.x
```

### Composer Dependencies
```bash
composer show laravel/framework
# Current: 10.50.2
# Target: 12.61.1
```

### Database Compatibility
```bash
# Check MySQL version
mysql --version
# Must be compatible with Laravel 12.x
```

## Step-by-Step Upgrade Process

### Step 1: Preparation
```bash
# Create backup
cp -r . z-syst-backup-$(date +%Y%m%d)

# Create testing branch
git checkout -b upgrade-laravel-12

# Update composer.json PHP requirement
# Change: "php": "^8.1" → "php": "^8.2"
```

### Step 2: Initial Laravel 11.x Upgrade
```bash
# Update Laravel
composer require laravel/framework:^11.0 --with-all-dependencies

# Update dependencies
composer update

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Fix Laravel 11.x Breaking Changes

#### 1. Update App.php Configuration
```php
// config/app.php
// Remove or update deprecated settings
'key' => env('APP_KEY'),
'cipher' => 'AES-256-CBC',
```

#### 2. Update Middleware
```php
// app/Http/Kernel.php
// Update middleware groups and signatures
// Remove deprecated middleware
```

#### 3. Update Exception Handler
```php
// app/Exceptions/Handler.php
// Update exception handling methods
// Implement new report() method
```

#### 4. Update Model Imports
```php
// Use new Eloquent features
// Update fillable/casts properties
// Use new scopes methods
```

### Step 4: Laravel 12.x Upgrade
```bash
# Update to Laravel 12.x
composer require laravel/framework:^12.0 --with-all-dependencies

# Update all dependencies
composer update

# Install new Laravel framework
composer require laravel/framework:^12.61.1 --with-all-dependencies
```

### Step 5: Fix Laravel 12.x Breaking Changes

#### 1. Update Configuration Files
```php
// config/app.php
// Add new configuration options
// Update paths and settings
```

#### 2. Update Routes
```php
// routes/web.php
// Update route signatures
// Use new route features
```

#### 3. Update Controllers
```php
// Update controller signatures
// Use new response methods
// Implement new features
```

#### 4. Update Views
```php
// Update Blade syntax
// Use new Blade directives
// Update component references
```

### Step 6: Payment Gateway Specific Updates

#### 1. Update Payment Gateway Service
```php
// app/Services/PaymentGatewayService.php
// Update for Laravel 12.x
// Use new collection methods
// Update response handling
```

#### 2. Update Payment Controllers
```php
// app/Http/Controllers/PaymentController.php
// Update request handling
// Use new response methods
// Update error handling
```

#### 3. Update Webhook Controllers
```php
// app/Http/Controllers/PaymentWebhookController.php
// Update request validation
// Use new response methods
// Update signature verification
```

### Step 7: Testing

#### Automated Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --tests/Unit
php artisan test --tests/Feature

# Run payment gateway tests
php artisan test --tests/Feature/PaymentGatewayTest
```

#### Manual Testing Checklist
- [ ] Website loads correctly
- [ ] Login/Logout works
- [ ] Payment gateway configuration works
- [ ] Payment processing works
- [ ] Webhook callbacks work
- [ ] Email functionality works
- [ ] API endpoints respond correctly
- [ ] Database operations work
- [ ] Queue workers process jobs
- [ ] Redis caching works

### Step 8: Deployment

#### Staging Deployment
```bash
# Deploy to staging
git push origin upgrade-laravel-12

# Run migrations on staging
php artisan migrate --force

# Seed database
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Production Deployment
```bash
# After staging tests pass
git checkout main
git merge upgrade-laravel-12
git push origin main

# On production server
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

## Rollback Plan

### If Upgrade Fails
```bash
# Rollback database
php artisan migrate:rollback --step=1

# Rollback code
git revert HEAD
composer install --no-dev --optimize-autoloader
```

### Emergency Rollback
```bash
# Restore from backup
cp -r z-syst-backup-YYYYMMDD/* .
composer install --no-dev --optimize-autoloader
php artisan cache:clear
```

## GitHub Secrets Configuration

### Required Secrets for Upgrade
```yaml
# Application
APP_KEY: "new-generated-key"
APP_ENV: "production"
APP_DEBUG: "false"

# Database
DB_HOST: "production-db-host"
DB_DATABASE: "z_syst_production"
DB_USERNAME: "production-user"
DB_PASSWORD: "production-password"

# Email
MAIL_USERNAME: "new-email@example.com"
MAIL_PASSWORD: "new-app-password"

# Payment Gateways
VODAFONE_CASH_MERCHANT_ID: "prod-merchant-id"
VODAFONE_CASH_API_KEY: "prod-api-key"
VODAFONE_CASH_API_SECRET: "prod-api-secret"
# ... (all payment gateway secrets)
```

### GitHub Actions Workflow
```yaml
name: Laravel Upgrade
on:
  push:
    branches: [ upgrade-laravel-12 ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader
    - name: Run Tests
      run: php artisan test
    - name: Security Audit
      run: composer audit
```

## Post-Upgrade Verification

### Security Verification
```bash
# Run security audit
composer audit

# Should show no Laravel vulnerabilities
```

### Performance Verification
```bash
# Test application performance
php artisan tinker
>>> app('cache')->put('test', 'value')
=> true
>>> app('cache')->get('test')
=> 'value'
```

### Functionality Verification
- [ ] All admin features work
- [ ] Payment gateways work
- [ ] Webhooks process correctly
- [ ] Email sends correctly
- [ ] API endpoints respond
- [ ] Queue workers process jobs
- [ ] Redis caching works
- [ ] Database operations work

## Known Issues & Solutions

### Issue 1: Email CRLF Injection
**Solution**: Laravel 12.x includes email header validation
**Action**: No code changes needed after upgrade

### Issue 2: Signed URL Path Confusion
**Solution**: Laravel 12.x fixes path confusion
**Action**: No code changes needed after upgrade

### Issue 3: Deprecated Code
**Solution**: Update code to use new Laravel features
**Action**: Replace deprecated functions with new alternatives

## Timeline Estimate

- **Phase 1**: 2 days
- **Phase 2**: 2 days
- **Phase 3**: 3 days
- **Phase 4**: 3 days
- **Phase 5**: 2 days

**Total**: 12 days (2 weeks)

## Risk Assessment

### Risk Level: MEDIUM-HIGH
- **Impact**: Critical security vulnerabilities
- **Complexity**: Major version upgrade
- **Timeline**: 2 weeks
- **Rollback**: Well-planned rollback procedure

### Mitigation Strategies
- Comprehensive testing before production
- Staging environment testing
- Quick rollback capability
- Monitoring during deployment
- Database backups before upgrade

## Success Criteria

### Technical Success
- [ ] Laravel upgraded to 12.61.1+
- [ ] All automated tests pass
- [ ] No security vulnerabilities
- [ ] All payment gateways work
- [ ] Performance is acceptable

### Business Success
- [ ] No downtime during upgrade
- [ ] All existing features work
- [ ] User experience maintained
- [ ] Data integrity preserved
- [ ] System stability maintained

## Contact & Support

### Laravel Documentation
- [Laravel 12.x Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [Laravel 11.x Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [Laravel Security Advisories](https://github.com/laravel/framework/security/advisories)

### Support Channels
- Laravel Community Forums
- Laravel Discord Server
- Payment Gateway Provider Support

---

**Plan Created**: 2026-08-09
**Status**: READY FOR EXECUTION
**Priority**: CRITICAL
**Timeline**: 2-3 weeks