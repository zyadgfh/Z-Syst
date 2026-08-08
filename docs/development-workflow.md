# Z-Syst Pharmacy Management System - Development Workflow

## 🔄 Development Workflow Overview

This document defines the complete development, testing, and deployment workflow for the Z-Syst Pharmacy Management System.

---

## 📋 Workflow Stages

### Stage 1: Development
### Stage 2: Code Review
### Stage 3: Testing
### Stage 4: Staging
### Stage 5: Production

---

## 🚀 Stage 1: Development

### 1.1 Branch Strategy
```
main          → Production code (always deployable)
develop       → Integration branch
feature/*     → Feature branches
bugfix/*      → Bug fix branches
hotfix/*      → Emergency production fixes
release/*     → Release preparation
```

### 1.2 Development Process

#### Before Starting Development
```bash
# 1. Ensure main is up to date
git checkout main
git pull origin main

# 2. Create feature branch
git checkout -b feature/feature-name

# 3. Install dependencies
composer install
npm install
```

#### During Development
```bash
# Run tests frequently
php artisan test

# Check code style
./vendor/bin/pint

# Run security checks
php artisan security:check
```

#### Commit Guidelines
```bash
# Stage files
git add .

# Commit with conventional format
git commit -m "feat(scope): add user authentication"

# Examples:
# feat: add new feature
# fix: fix bug
# docs: update documentation
# style: formatting changes
# refactor: code refactoring
# test: add tests
# chore: maintenance tasks
```

### 1.3 Code Standards
- Follow PSR-12 coding standards
- Use type hints for all methods
- Write DocBlocks for all public methods
- Keep methods under 50 lines
- Keep classes under 500 lines
- Use meaningful variable names
- Add comments for complex logic

---

## 🔍 Stage 2: Code Review

### 2.1 Pull Request Process

#### Creating PR
```bash
# Push feature branch
git push origin feature/feature-name

# Create PR on GitHub/GitLab
# Include:
# - Description of changes
# - Related issue number
# - Testing performed
# - Screenshots (if UI changes)
```

#### PR Checklist
- [ ] Code follows project standards
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No console.log/debug statements
- [ ] No hardcoded values
- [ ] Security reviewed
- [ ] Performance considered
- [ ] Accessibility checked (if UI changes)

#### Review Process
1. **Self-Review**: Review your own changes
2. **Peer Review**: Request team review
3. **Security Review**: Security team approval
4. **Final Approval**: Lead developer approval

### 2.2 Review Criteria

#### Code Quality
- ✅ Clean, readable code
- ✅ Proper error handling
- ✅ Input validation
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ CSRF protection

#### Performance
- ✅ No N+1 queries
- ✅ Proper caching
- ✅ Efficient database queries
- ✅ Optimized asset loading

#### Testing
- ✅ Unit tests for new functions
- ✅ Feature tests for new features
- ✅ Security tests for security changes
- ✅ All tests passing

---

## 🧪 Stage 3: Testing

### 3.1 Testing Pyramid

```
        /\
       /E2E\    - 5% (End-to-end tests)
      /------\
     /Feature\  - 25% (Feature tests)
    /--------\
   /  Unit   \  - 70% (Unit tests)
  /----------\
```

### 3.2 Test Execution

#### Local Testing
```bash
# Run all tests
php artisan test

# Run unit tests only
php artisan test --testsuite=Unit

# Run feature tests only
php artisan test --testsuite=Feature

# Run specific test
php artisan test --filter=TestClassName

# Run with coverage
./run-coverage.sh
```

#### CI/CD Testing
```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install Dependencies
        run: composer install --no-progress --no-interaction
        
      - name: Run Tests
        run: php artisan test
        
      - name: Run Coverage
        run: ./run-coverage.sh
```

### 3.3 Test Requirements

#### Coverage Requirements
- **Overall Coverage:** ≥ 70%
- **Critical Services:** ≥ 90%
- **New Code:** ≥ 80%

#### Test Types
- **Unit Tests:** Business logic, services, models
- **Feature Tests:** User workflows, API endpoints
- **Security Tests:** SQL injection, XSS, CSRF
- **Integration Tests:** Complete workflows
- **Performance Tests:** Load testing, stress testing

---

## 🎭 Stage 4: Staging

### 4.1 Staging Environment

#### Setup
```bash
# Deploy to staging
git checkout develop
git pull origin develop

# Deploy to staging server
./deploy.sh staging
```

#### Staging Configuration
```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.z-syst.com

DB_DATABASE=z_syst_staging
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 4.2 Staging Checklist

#### Pre-Staging
- [ ] All tests passing
- [ ] Code review approved
- [ ] Documentation updated
- [ ] Migration scripts tested
- [ ] Backup current staging database

#### During Staging
- [ ] Deploy successfully
- [ ] Database migrations run
- [ ] Seeders execute
- [ ] Assets compiled
- [ ] Cache cleared
- [ ] Queue workers restarted

#### Post-Staging
- [ ] Smoke tests pass
- [ ] Feature verification
- [ ] Performance check
- [ ] Security verification
- [ ] Bug fixes if needed

### 4.3 Staging Testing

#### Smoke Tests
```bash
# Health check
curl https://staging.z-syst.com/health

# Login test
curl -X POST https://staging.z-syst.com/login \
  -d "email=test@test.com&password=test123"

# API test
curl https://staging.z-syst.com/api/products
```

#### Feature Testing
- [ ] User registration/login
- [ ] Product management
- [ ] Sales processing
- [ ] Report generation
- [ ] User permissions
- [ ] Tenant isolation

---

## 🚀 Stage 5: Production

### 5.1 Production Deployment

#### Pre-Production Checklist
- [ ] Staging verified
- [ ] All tests passing
- [ ] Security scan completed
- [ ] Performance benchmarks met
- [ ] Backup strategy confirmed
- [ ] Rollback plan ready
- [ ] Team notified
- [ ] Maintenance window scheduled

#### Deployment Process
```bash
# 1. Create release branch
git checkout -b release/v1.0.0

# 2. Merge develop
git merge develop

# 3. Tag release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# 4. Deploy to production
./deploy.sh production

# 5. Verify deployment
curl https://app.z-syst.com/health
```

#### Deployment Script
```bash
#!/bin/bash
# deploy.sh

ENVIRONMENT=$1
PROJECT_DIR="/var/www/z-syst"
BACKUP_DIR="/var/backups/z-syst"

echo "Deploying to $ENVIRONMENT..."

# Create backup
echo "Creating backup..."
mysqldump -u root -p z_syst_pharmacy > $BACKUP_DIR/backup-$(date +%Y%m%d).sql

# Pull latest code
echo "Pulling latest code..."
cd $PROJECT_DIR
git pull origin main

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear cache
echo "Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
echo "Restarting queue workers..."
sudo supervisorctl restart laravel-worker:*

echo "Deployment complete!"
```

### 5.2 Post-Deployment

#### Verification Steps
```bash
# 1. Health check
curl https://app.z-syst.com/health

# 2. Database check
php artisan db:show

# 3. Queue check
php artisan queue:failed

# 4. Cache check
php artisan cache:clear
php artisan config:cache

# 5. Permission check
ls -la storage/
```

#### Monitoring
- [ ] Error logs checked
- [ ] Performance metrics monitored
- [ ] User feedback collected
- [ ] Backup verification
- [ ] Queue workers status

### 5.3 Rollback Plan

#### If Deployment Fails
```bash
# 1. Restore database
mysql -u root -p z_syst_pharmacy < $BACKUP_DIR/backup-YYYYMMDD.sql

# 2. Revert code
cd /var/www/z-syst
git revert HEAD

# 3. Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx
sudo supervisorctl restart laravel-worker:*

# 4. Verify rollback
curl https://app.z-syst.com/health
```

---

## 🔄 Continuous Integration/Continuous Deployment

### CI/CD Pipeline

#### GitHub Actions Workflow
```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v2
        
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, mysql, bcmath
          
      - name: Install dependencies
        run: composer install --no-progress --no-interaction
        
      - name: Run tests
        run: php artisan test
        
      - name: Run coverage
        run: ./run-coverage.sh
        
      - name: Security scan
        run: composer audit

  deploy-staging:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/develop'
    
    steps:
      - name: Deploy to staging
        run: ./deploy.sh staging

  deploy-production:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - name: Deploy to production
        run: ./deploy.sh production
```

---

## 📊 Workflow Metrics

### Key Performance Indicators

#### Development Metrics
- **Code Review Time:** < 24 hours
- **Test Execution Time:** < 10 minutes
- **Build Time:** < 5 minutes
- **Deployment Time:** < 30 minutes

#### Quality Metrics
- **Test Coverage:** ≥ 70%
- **Code Review Approval Rate:** ≥ 90%
- **Bug Detection Rate:** < 5% in production
- **Deployment Success Rate:** ≥ 95%

#### Performance Metrics
- **Page Load Time:** < 3 seconds
- **API Response Time:** < 200ms
- **Database Query Time:** < 100ms
- **Uptime:** ≥ 99.9%

---

## 🛠️ Tools & Automation

### Development Tools
- **IDE:** VS Code / PhpStorm
- **Version Control:** Git
- **Package Manager:** Composer, npm
- **Testing:** PHPUnit
- **Code Quality:** PHPStan, Larastan

### CI/CD Tools
- **CI/CD:** GitHub Actions / GitLab CI
- **Container:** Docker (optional)
- **Monitoring:** Sentry, New Relic
- **Logging:** ELK Stack (optional)

### Deployment Tools
- **Server:** Ubuntu + Nginx + PHP-FPM
- **Queue:** Redis + Supervisor
- **Database:** MySQL
- **SSL:** Let's Encrypt / Certbot

---

## 📝 Workflow Documentation

### Creating New Feature

1. **Planning**
   - Create issue in project management tool
   - Discuss with team
   - Define acceptance criteria

2. **Development**
   - Create feature branch
   - Implement feature
   - Write tests
   - Update documentation

3. **Review**
   - Create pull request
   - Self-review
   - Peer review
   - Address feedback

4. **Testing**
   - Run unit tests
   - Run feature tests
   - Run security tests
   - Manual testing

5. **Deployment**
   - Merge to develop
   - Deploy to staging
   - Verify on staging
   - Merge to main
   - Deploy to production

### Bug Fix Process

1. **Report Bug**
   - Create issue with details
   - Include steps to reproduce
   - Add screenshots if applicable

2. **Triage**
   - Assign priority
   - Assign developer
   - Estimate effort

3. **Fix**
   - Create bugfix branch
   - Implement fix
   - Write tests
   - Verify fix

4. **Review & Deploy**
   - Code review
   - Testing
   - Staging verification
   - Production deployment

---

## 🎯 Best Practices

### Development Best Practices
- ✅ Write tests before writing code (TDD)
- ✅ Keep commits small and focused
- ✅ Write meaningful commit messages
- ✅ Review your own code before PR
- ✅ Follow coding standards
- ✅ Document complex logic

### Code Review Best Practices
- ✅ Be constructive and respectful
- ✅ Focus on code quality, not style
- ✅ Explain reasoning for suggestions
- ✅ Approve promptly when ready
- ✅ Follow up on discussions

### Testing Best Practices
- ✅ Test behavior, not implementation
- ✅ Keep tests independent
- ✅ Use descriptive test names
- ✅ Mock external dependencies
- ✅ Test edge cases

### Deployment Best Practices
- ✅ Always backup before deployment
- ✅ Deploy during low-traffic hours
- ✅ Have rollback plan ready
- ✅ Monitor after deployment
- ✅ Communicate with team

---

## 🚨 Emergency Procedures

### Hotfix Process

1. **Identify Issue**
   - Monitor alerts
   - Assess severity
   - Determine impact

2. **Create Hotfix**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b hotfix/critical-issue
   ```

3. **Fix & Test**
   - Implement fix
   - Write tests
   - Test locally

4. **Deploy Immediately**
   ```bash
   git push origin hotfix/critical-issue
   ./deploy.sh production
   ```

5. **Post-Deployment**
   - Verify fix
   - Monitor logs
   - Update documentation

### Rollback Procedure

```bash
# 1. Identify last stable version
git log --oneline

# 2. Revert to stable version
git revert HEAD

# 3. Deploy
./deploy.sh production

# 4. Verify
curl https://app.z-syst.com/health
```

---

## 📚 Related Documentation

- [Deployment Guide](deployment-guide.md)
- [Production Checklist](production-checklist.md)
- [Production Readiness Report](production-readiness-report.md)
- [PROJECT_RULES/](../PROJECT_RULES/README.md)

---

**Workflow Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Status:** Active
