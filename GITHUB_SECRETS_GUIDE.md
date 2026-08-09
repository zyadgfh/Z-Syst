# GitHub Secrets Configuration Guide

## Overview
Complete guide for setting up GitHub Secrets for the Z-Syst Pharmacy Management System with Egyptian payment gateways.

## Required GitHub Secrets

### Application Secrets
```yaml
APP_KEY: "base64:your-generated-application-key"
APP_NAME: "Z-Syst"
APP_ENV: "production"
APP_DEBUG: "false"
APP_URL: "https://your-domain.com"
```

### Database Secrets
```yaml
DB_CONNECTION: "mysql"
DB_HOST: "your-database-host"
DB_PORT: "3306"
DB_DATABASE: "z_syst_production"
DB_USERNAME: "your-database-username"
DB_PASSWORD: "your-database-password"
```

### Email Secrets
```yaml
MAIL_MAILER: "smtp"
MAIL_HOST: "smtp.gmail.com"
MAIL_PORT: "587"
MAIL_USERNAME: "your-email@example.com"
MAIL_PASSWORD: "your-app-specific-password"
MAIL_ENCRYPTION: "tls"
MAIL_FROM_ADDRESS: "noreply@your-domain.com"
MAIL_FROM_NAME: "Z-Syst"
```

### Redis Secrets
```yaml
REDIS_HOST: "your-redis-host"
REDIS_PASSWORD: "your-redis-password"
REDIS_PORT: "6379"
REDIS_DB: "0"
REDIS_CACHE_DB: "1"
```

### AWS Secrets (if using S3)
```yaml
AWS_ACCESS_KEY_ID: "your-aws-access-key-id"
AWS_SECRET_ACCESS_KEY: "your-aws-secret-access-key"
AWS_DEFAULT_REGION: "us-east-1"
AWS_BUCKET: "your-s3-bucket-name"
AWS_USE_PATH_STYLE_ENDPOINT: "false"
AWS_URL: "https://your-bucket.s3.amazonaws.com"
```

### Egyptian Payment Gateway Secrets

#### Vodafone Cash
```yaml
VODAFONE_CASH_MERCHANT_ID: "your-vodafone-merchant-id"
VODAFONE_CASH_API_KEY: "your-vodafone-api-key"
VODAFONE_CASH_API_SECRET: "your-vodafone-api-secret"
VODAFONE_CASH_ENVIRONMENT: "production"
```

#### Bank Card
```yaml
BANK_CARD_MERCHANT_ID: "your-bank-card-merchant-id"
BANK_CARD_API_KEY: "your-bank-card-api-key"
BANK_CARD_API_SECRET: "your-bank-card-api-secret"
BANK_CARD_ENVIRONMENT: "production"
BANK_CARD_ACCEPT_MEEZA: "true"
BANK_CARD_ACCEPT_VISAMASTERCARD: "true"
```

#### Fawry
```yaml
FAWRY_MERCHANT_CODE: "your-fawry-merchant-code"
FAWRY_SECURITY_KEY: "your-fawry-security-key"
FAWRY_ENVIRONMENT: "production"
```

#### Orange Cash
```yaml
ORANGE_CASH_MERCHANT_ID: "your-orange-cash-merchant-id"
ORANGE_CASH_API_KEY: "your-orange-cash-api-key"
ORANGE_CASH_API_SECRET: "your-orange-cash-api-secret"
ORANGE_CASH_ENVIRONMENT: "production"
```

#### InstaPay
```yaml
INSTAPAY_MERCHANT_ID: "your-instapay-merchant-id"
INSTAPAY_API_KEY: "your-instapay-api-key"
INSTAPAY_API_SECRET: "your-instapay-api-secret"
INSTAPAY_ENVIRONMENT: "production"
```

#### Cash Payment Settings
```yaml
CASH_REQUIRE_VERIFICATION: "true"
CASH_ALLOW_PARTIAL_PAYMENT: "false"
CASH_AUTO_COMPLETE: "true"
```

### Additional Secrets
```yaml
PUSHER_APP_ID: "your-pusher-app-id"
PUSHER_APP_KEY: "your-pusher-app-key"
PUSHER_APP_SECRET: "your-pusher-app-secret"
PUSHER_HOST: "your-pusher-host"
PUSHER_PORT: "443"
PUSHER_SCHEME: "https"
PUSHER_APP_CLUSTER: "mt1"

VITE_FIREBASE_API_KEY: "your-firebase-api-key"
VITE_FIREBASE_AUTH_DOMAIN: "your-project.firebaseapp.com"
VITE_FIREBASE_PROJECT_ID: "your-project-id"
VITE_FIREBASE_APP_ID: "your-firebase-app-id"
VITE_FIREBASE_STORAGE_BUCKET: "your-bucket.appspot.com"
VITE_FIREBASE_MESSAGING_SENDER_ID: "your-messaging-sender-id"
VITE_FIREBASE_MEASUREMENT_ID: "your-measurement-id"
```

## Setup Instructions

### Step 1: Generate APP_KEY
```bash
php artisan key:generate
```
Copy the generated key and add to GitHub Secrets as `APP_KEY`.

### Step 2: Get Payment Gateway Credentials
Contact each payment gateway provider to obtain production credentials:
- Vodafone Cash Egypt
- Bank Card Provider (Paymob, Fawry Pay, etc.)
- Fawry Egypt
- Orange Cash Egypt
- InstaPay Egypt

### Step 3: Add Secrets to GitHub

#### Via GitHub Web Interface
1. Go to repository → Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Add each secret one by one using the names above
4. Save all secrets

#### Via GitHub CLI
```bash
# Install GitHub CLI
# Then add secrets
gh secret set APP_KEY "your-app-key"
gh secret set DB_PASSWORD "your-db-password"
gh secret set MAIL_PASSWORD "your-mail-password"
# ... add all secrets
```

### Step 4: Update Environment File Template
Update `.env.example` with placeholder values:
```env
APP_KEY=base64:placeholder-key
DB_PASSWORD=your-database-password
MAIL_PASSWORD=your-app-password
# ... (all other placeholders)
```

### Step 5: Remove Sensitive Data from Repository
```bash
# Remove actual credentials from .env
sed -i 's/MAIL_USERNAME=.*/MAIL_USERNAME=/g' .env
sed -i 's/MAIL_PASSWORD=.*/MAIL_PASSWORD=/g' .env
sed -i 's/APP_KEY=.*/APP_KEY=/g' .env
```

## GitHub Actions Configuration

### Update Workflow to Use Secrets
Create `.github/workflows/deploy.yml`:
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, mysql, pdo_mysql
    
    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader
      env:
        # No secrets needed for dependency installation
    
    - name: Generate Application Key
      run: php artisan key:generate
      env:
        APP_KEY: ${{ secrets.APP_KEY }}
    
    - name: Run Migrations
      run: php artisan migrate --force
      env:
        DB_HOST: ${{ secrets.DB_HOST }}
        DB_DATABASE: ${{ secrets.DB_DATABASE }}
        DB_USERNAME: ${{ secrets.DB_USERNAME }}
        DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
    
    - name: Seed Database
      run: php artisan db:seed --force
      env:
        DB_HOST: ${{ secrets.DB_HOST }}
        DB_DATABASE: ${{ secrets.DB_DATABASE }}
        DB_USERNAME: ${{ secrets.DB_USERNAME }}
        DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
    
    - name: Clear Cache
      run: php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
    
    - name: Configure Environment
      run: |
        echo "APP_KEY=${{ secrets.APP_KEY }}" >> .env
        echo "DB_HOST=${{ secrets.DB_HOST }}" >> .env
        echo "DB_DATABASE=${{ secrets.DB_DATABASE }}" >> .env
        echo "DB_USERNAME=${{ secrets.DB_USERNAME }}" >> .env
        echo "DB_PASSWORD=${{ secrets.DB_PASSWORD }}" >> .env
        echo "MAIL_USERNAME=${{ secrets.MAIL_USERNAME }}" >> .env
        echo "MAIL_PASSWORD=${{ secrets.MAIL_PASSWORD }}" >> .env
        # ... add all other secrets
    
    - name: Optimize Application
      run: |
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
```

## Environment Variable Usage in Code

### Accessing Secrets in Laravel
```php
// In your code, use env() function
$apiKey = env('VODAFONE_CASH_API_KEY');
$merchantId = env('VODAFONE_CASH_MERCHANT_ID');
$dbPassword = env('DB_PASSWORD');
```

### Payment Gateway Configuration
```php
// config/app.php - Payment gateway configuration
'payment_gateways' => [
    'vodafone_cash' => [
        'merchant_id' => env('VODAFONE_CASH_MERCHANT_ID'),
        'api_key' => env('VODAFONE_CASH_API_KEY'),
        'api_secret' => env('VODAFONE_CASH_API_SECRET'),
        'environment' => env('VODAFONE_CASH_ENVIRONMENT', 'sandbox'),
    ],
    // ... other gateways
],
```

## Security Best Practices

### 1. Secret Rotation
- Rotate secrets every 90 days
- Rotate compromised secrets immediately
- Update GitHub Secrets and redeploy

### 2. Access Control
- Limit who can modify secrets
- Use different secrets for different environments
- Never commit secrets to repository

### 3. Secret Strength
- Use strong, unique passwords
- Use environment-specific secrets
- Use different secrets for each gateway

### 4. Monitoring
- Monitor secret usage
- Audit secret access logs
- Set up alerts for secret changes

## Testing Secret Configuration

### Local Testing with Secrets
```bash
# Copy GitHub Secrets to local .env
# For local development, use test credentials
cp .env.example .env.local
```

### Verify Secret Access
```bash
# Test that secrets are accessible
php artisan tinker
>>> env('VODAFONE_CASH_API_KEY')
=> 'your-api-key'
```

### Test Payment Gateway Configuration
```bash
# Test gateway configuration
php artisan tinker
>>> env('VODAFONE_CASH_ENVIRONMENT')
=> 'sandbox'
```

## Troubleshooting

### Secret Not Loading
1. Check secret name matches exactly
2. Verify secret is not empty
3. Check file permissions
4. Restart application

### Payment Gateway Not Working
1. Verify API credentials are correct
2. Check environment is set correctly (sandbox/production)
3. Test with sandbox credentials first
4. Check payment gateway service status

### Database Connection Issues
1. Verify database credentials
2. Check database host is accessible
3. Verify database exists
4. Check firewall settings

## Secret Rotation Procedure

### Regular Rotation (Every 90 Days)
1. Generate new secret
2. Update GitHub Secret
3. Update service provider configuration
4. Deploy application
5. Verify functionality
6. Old secret can be deactivated

### Emergency Rotation (Compromised Secret)
1. Immediately disable compromised secret
2. Generate new secret
3. Update GitHub Secret
4. Update service provider configuration
5. Deploy application immediately
6. Monitor for suspicious activity

## Documentation Updates

### Keep Secrets Documentation Updated
- [ ] Document when secrets were added
- [ ] Document when secrets were rotated
- [ ] Document service provider contact information
- [ ] Document API endpoints and versions

---

**Created**: 2026-08-09
**Purpose**: GitHub Secrets Configuration for Z-Syst Pharmacy System
**Status**: Ready for Implementation