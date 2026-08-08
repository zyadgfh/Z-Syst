# Z-Syst Deployment Guidelines

## 🚀 معايير النشر

### Pre-Deployment Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Cache cleared
- [ ] Config cached
- [ ] Assets compiled
- [ ] Dependencies updated
- [ ] Security scan completed
- [ ] Tests passed
- [ ] Backup created
- [ ] SSL certificate configured
- [ ] Monitoring enabled
- [ ] Logging configured

---

## 🌍 Environment Configuration

### Production Environment Variables
```env
APP_NAME=Z-Syst
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=production_db
DB_USERNAME=production_user
DB_PASSWORD=secure_password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com

AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name

FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

---

## 🗄️ Database Deployment

### Run Migrations
```bash
php artisan migrate --force
```

### Seed Production Data
```bash
php artisan db:seed --force
```

### Database Backup
```bash
# Backup
mysqldump -u username -p database_name > backup.sql

# Restore
mysql -u username -p database_name < backup.sql
```

---

## 📦 Asset Compilation

### Compile Assets for Production
```bash
npm run build
```

### Version Assets
```php
// webpack.mix.js or vite.config.js
mix.version('resources/css/app.css')
    .version('resources/js/app.js');
```

---

## 🔧 Server Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/z-syst/public;

    ssl_certificate /etc/ssl/certs/your-domain.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### PHP-FPM Configuration
```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
```

---

## 🔐 Security Hardening

### File Permissions
```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/z-syst
sudo chmod -R 755 /var/www/z-syst
sudo chmod -R 775 /var/www/z-syst/storage
sudo chmod -R 775 /var/www/z-syst/bootstrap/cache
```

### Disable Debug Mode
```env
APP_DEBUG=false
```

### Enable HTTPS
```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

---

## 🔄 Continuous Deployment

### GitHub Actions Workflow
```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Run Migrations
      run: php artisan migrate --force
      
    - name: Clear Cache
      run: |
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        
    - name: Cache Config
      run: |
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        
    - name: Deploy to Server
      uses: easingthemes/ssh-deploy@v2
      with:
        SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
        REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
        REMOTE_USER: ${{ secrets.REMOTE_USER }}
        TARGET: /var/www/z-syst
```

---

## 📊 Monitoring

### Laravel Telescope (Development)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Error Tracking (Sentry)
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --tag=sentry-config
```

```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
```

### Performance Monitoring (New Relic)
```bash
composer require newrelic/newrelic-laravel
```

---

## 🔍 Logging

### Configure Logging
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'warning',
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

---

## 💾 Backup Strategy

### Automated Backups
```bash
# Install Laravel Backup Package
composer require spatie/laravel-backup

# Publish config
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Run backup
php artisan backup:run
```

### Backup Configuration
```php
// config/backup.php
'backup' => [
    'name' => env('APP_NAME', 'laravel'),
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => [
            'mysql',
        ],
    ],
    'destination' => [
        'filename_prefix' => '',
        'disks' => [
            's3',
        ],
    ],
],
```

### Schedule Backups
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')->dailyAt('02:00');
}
```

---

## 🚀 Queue Workers

### Supervisor Configuration
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/z-syst/artisan queue:work redis --sleep=3 --tries=3
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
sudo supervisorctl start laravel-worker:*
```

---

## 🔧 Optimization

### Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Optimize Composer
```bash
composer install --optimize-autoloader --no-dev
```

### OPcache Configuration
```ini
; /etc/php/8.2/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

---

## 📱 SSL Configuration

### Let's Encrypt Certificate
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain Certificate
sudo certbot --nginx -d your-domain.com

# Auto Renewal
sudo certbot renew --dry-run
```

### SSL Configuration
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

---

## 🌐 CDN Configuration

### Cloudflare Setup
1. Add domain to Cloudflare
2. Update nameservers
3. Configure SSL (Full mode)
4. Enable caching rules
5. Configure Page Rules

### CDN Asset URL
```env
ASSET_URL=https://cdn.your-domain.com
```

---

## 🔥 Load Balancing

### Nginx Load Balancer
```nginx
upstream backend {
    server 10.0.0.1:8000;
    server 10.0.0.2:8000;
    server 10.0.0.3:8000;
}

server {
    listen 80;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 🧪 Health Checks

### Health Check Endpoint
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::get('health_check') ? 'connected' : 'disconnected',
    ]);
});
```

---

## 📊 Performance Monitoring

### Laravel Horizon (Queue Monitoring)
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

### New Relic APM
```bash
composer require newrelic/newrelic-laravel
php artisan vendor:publish --tag=newrelic-config
```

---

## 🚨 Incident Response

### Rollback Procedure
```bash
# Revert to previous commit
git revert HEAD

# Rollback migrations
php artisan migrate:rollback --step=1

# Restore database backup
mysql -u username -p database_name < backup.sql

# Clear cache
php artisan cache:clear
```

### Maintenance Mode
```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up
```

---

## 📚 Resources

### Deployment Resources
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Laravel Forge](https://forge.laravel.com/)
- [Laravel Vapor](https://vapor.laravel.com/)

### Server Resources
- [DigitalOcean](https://www.digitalocean.com/)
- [AWS](https://aws.amazon.com/)
- [Linode](https://www.linode.com/)

---

**آخر تحديث:** 2026-08-07
