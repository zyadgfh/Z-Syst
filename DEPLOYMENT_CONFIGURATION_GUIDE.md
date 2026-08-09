# Deployment & Configuration Guide

## Overview
Comprehensive guide for deploying and configuring the Z-Syst Pharmacy Management System with Egyptian payment gateways.

## 🔐 GitHub Secrets Configuration

### Required GitHub Secrets

#### Application Secrets
```yaml
APP_KEY: "base64:your-generated-key"
APP_NAME: "Z-Syst"
APP_ENV: "production"
APP_DEBUG: "false"
APP_URL: "https://your-domain.com"
```

#### Database Secrets
```yaml
DB_CONNECTION: "mysql"
DB_HOST: "your-db-host"
DB_PORT: "3306"
DB_DATABASE: "z_syst_production"
DB_USERNAME: "your-db-user"
DB_PASSWORD: "your-db-password"
```

#### Email Secrets
```yaml
MAIL_MAILER: "smtp"
MAIL_HOST: "smtp.gmail.com"
MAIL_PORT: "587"
MAIL_USERNAME: "your-email@example.com"
MAIL_PASSWORD: "your-app-password"
MAIL_ENCRYPTION: "tls"
```

#### Redis Secrets
```yaml
REDIS_HOST: "your-redis-host"
REDIS_PASSWORD: "your-redis-password"
REDIS_PORT: "6379"
REDIS_DB: "0"
REDIS_CACHE_DB: "1"
```

#### AWS Secrets (if using S3)
```yaml
AWS_ACCESS_KEY_ID: "your-aws-access-key"
AWS_SECRET_ACCESS_KEY: "your-aws-secret-key"
AWS_DEFAULT_REGION: "us-east-1"
AWS_BUCKET: "your-bucket-name"
```

#### Egyptian Payment Gateway Secrets
```yaml
# Vodafone Cash
VODAFONE_CASH_MERCHANT_ID: "your-merchant-id"
VODAFONE_CASH_API_KEY: "your-api-key"
VODAFONE_CASH_API_SECRET: "your-api-secret"
VODAFONE_CASH_ENVIRONMENT: "production"

# Bank Card
BANK_CARD_MERCHANT_ID: "your-merchant-id"
BANK_CARD_API_KEY: "your-api-key"
BANK_CARD_API_SECRET: "your-api-secret"
BANK_CARD_ENVIRONMENT: "production"
BANK_CARD_ACCEPT_MEEZA: "true"
BANK_CARD_ACCEPT_VISAMASTERCARD: "true"

# Fawry
FAWRY_MERCHANT_CODE: "your-merchant-code"
FAWRY_SECURITY_KEY: "your-security-key"
FAWRY_ENVIRONMENT: "production"

# Orange Cash
ORANGE_CASH_MERCHANT_ID: "your-merchant-id"
ORANGE_CASH_API_KEY: "your-api-key"
ORANGE_CASH_API_SECRET: "your-api-secret"
ORANGE_CASH_ENVIRONMENT: "production"

# InstaPay
INSTAPAY_MERCHANT_ID: "your-merchant-id"
INSTAPAY_API_KEY: "your-api-key"
INSTAPAY_API_SECRET: "your-api-secret"
INSTAPAY_ENVIRONMENT: "production"
```

### GitHub Actions Configuration

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
        php-version: '8.1'
        extensions: mbstring, xml, mysql, pdo_mysql
    
    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader
    
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
    
    - name: Clear Cache
      run: php artisan cache:clear && php artisan config:clear
```

## 🔧 Redis Configuration

### Installation
```bash
# Ubuntu/Debian
sudo apt-get install redis-server

# CentOS/RHEL
sudo yum install redis

# macOS
brew install redis
```

### Configuration
Edit `/etc/redis/redis.conf`:
```conf
# Bind to localhost for security
bind 127.0.0.1

# Set password
requirepass your-redis-password

# Memory management
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000
```

### Laravel Configuration
Update `.env`:
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Redis as Cache Driver
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Test Redis Connection
```bash
php artisan tinker
>>> Redis::ping()
=> true
```

## 🔒 SSL/TLS Configuration

### Using Let's Encrypt
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate SSL Certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/z-syst/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Laravel SSL Configuration
Update `.env`:
```env
APP_URL=https://your-domain.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

## 💾 Backup System Configuration

### Database Backup Script
Create `backup-database.sh`:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/z-syst"
DB_NAME="z_syst_production"
DB_USER="backup_user"
DB_PASS="backup_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep last 7 days
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete
```

### Laravel Backup Configuration
Install backup package:
```bash
composer require spatie/laravel-backup
```

Publish configuration:
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configure `config/backup.php`:
```php
'destination' => [
    'filename_prefix' => '',
    'disks' => [
        's3',
    ],
],
```

### S3 Backup Configuration
Update `.env`:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-backup-bucket
```

### Automated Backups (Cron)
```bash
# Add to crontab
0 2 * * * /path/to/backup-database.sh
0 3 * * * php /var/www/z-syst/artisan backup:run
```

## 📊 Monitoring System Setup

### Application Monitoring with Laravel Telescope
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Error Tracking with Sentry
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\SentryLaravelServiceProvider"
```

Configure `.env`:
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### Server Monitoring with Monit
Install Monit:
```bash
sudo apt-get install monit
```

Configure `/etc/monit/monitrc`:
```conf
check process php-fpm with pidfile /var/run/php/php8.1-fpm.pid
    start program = "/etc/init.d/php8.1-fpm start"
    stop program = "/etc/init.d/php8.1-fpm stop"

check process nginx with pidfile /var/run/nginx.pid
    start program = "/etc/init.d/nginx start"
    stop program = "/etc/init.d/nginx stop"

check process redis with pidfile /var/run/redis/redis-server.pid
    start program = "/etc/init.d/redis-server start"
    stop program = "/etc/init.d/redis-server stop"
```

### Log Monitoring
```bash
# Install GoAccess for log analysis
sudo apt-get install goaccess

# Monitor logs in real-time
tail -f /var/www/z-syst/storage/logs/laravel.log | goaccess -
```

## 🧪 Queue Workers Configuration

### Supervisor Installation
```bash
sudo apt-get install supervisor
```

### Supervisor Configuration
Create `/etc/supervisor/conf.d/z-syst-worker.conf`:
```ini
[program:z-syst-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/z-syst/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/z-syst/storage/logs/worker.log
stopwaitsecs=3600
```

### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start z-syst-worker:*
```

### Monitor Queue Workers
```bash
# Check queue status
php artisan queue:monitor

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## 📧 Email Testing

### Test Email Configuration
```bash
php artisan tinker
>>> Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
=> null
```

### Configure Mailhog for Development
```bash
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog
```

Update `.env`:
```env
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_ENCRYPTION=null
```

Access web interface at `http://localhost:8025`

## 🔥 Firebase Integration Testing

### Firebase Configuration
Update `.env`:
```env
VITE_FIREBASE_API_KEY=your-api-key
VITE_FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
VITE_FIREBASE_PROJECT_ID=your-project-id
VITE_FIREBASE_APP_ID=your-app-id
VITE_FIREBASE_STORAGE_BUCKET=your-bucket.appspot.com
VITE_FIREBASE_MESSAGING_SENDER_ID=your-sender-id
VITE_FIREBASE_MEASUREMENT_ID=your-measurement-id
```

### Test Firebase Connection
```javascript
// resources/js/firebase-test.js
import { initializeApp } from 'firebase/app';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

const app = initializeApp(firebaseConfig);
console.log('Firebase initialized:', app);
```

## 📱 Flutter App Integration Testing

### API Testing
```bash
# Test POS payment API
curl -X GET http://localhost:8000/api/v1/payments/gateways \
  -H "Authorization: Bearer your-token"

# Test payment processing
curl -X POST http://localhost:8000/api/v1/payments/process \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "gateway_id": 1,
    "amount": 100.50,
    "customer_phone": "01012345678"
  }'
```

### Flutter Configuration
Update Flutter environment variables:
```dart
// lib/config.dart
class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://your-domain.com/api/v1',
  );
  
  static const String firebaseApiKey = String.fromEnvironment(
    'FIREBASE_API_KEY',
    defaultValue: 'your-api-key',
  );
}
```

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] All tests passing
- [ ] Code formatted with Pint
- [ ] Security audit completed
- [ ] Sensitive data removed
- [ ] GitHub Secrets configured
- [ ] Database backups created
- [ ] SSL certificates obtained
- [ ] Redis configured
- [ ] Queue workers set up
- [ ] Monitoring configured

### Deployment Steps
1. **Pull latest code**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```

2. **Run migrations**
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=PaymentGatewaySeeder
   ```

3. **Clear cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Restart services**
   ```bash
   sudo supervisorctl restart z-syst-worker:*
   sudo systemctl restart nginx
   sudo systemctl restart php8.1-fpm
   ```

5. **Test deployment**
   - [ ] Website loads correctly
   - [ ] API endpoints respond
   - [ ] Email functionality works
   - [ ] Payment gateways functional
   - [ ] Queue workers processing
   - [ ] Redis connection active

### Post-Deployment
- [ ] Monitor error logs
- [ ] Check system resources
- [ ] Verify backup system
- [ ] Test payment gateways in production
- [ ] Monitor transaction processing
- [ ] Review security alerts

## 📞 Emergency Procedures

### Rollback Plan
```bash
# Database rollback
php artisan migrate:rollback --step=1

# Code rollback
git revert HEAD
composer install --no-dev --optimize-autoloader
```

### Incident Response
1. Identify the issue
2. Assess impact
3. Implement temporary fix
4. Monitor for recurrence
5. Document incident
6. Implement permanent fix

---

**Last Updated**: 2026-08-09
**Version**: 1.0.0
**Status**: Ready for Deployment