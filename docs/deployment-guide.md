# Z-Syst Pharmacy Management System - Deployment Guide

## 📋 Prerequisites

### Server Requirements
- **OS:** Ubuntu 20.04 LTS or higher
- **RAM:** Minimum 2GB (Recommended 4GB+)
- **Storage:** Minimum 20GB SSD
- **CPU:** 2 cores minimum

### Software Requirements
- **PHP:** 8.2 or higher
- **MySQL:** 8.0 or higher
- **Redis:** 6.0 or higher
- **Nginx:** 1.18 or higher
- **Composer:** 2.0 or higher
- **Node.js:** 18 or higher (for Vite)
- **Git:** 2.0 or higher

---

## 🚀 Deployment Steps

### Step 1: Server Setup

#### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

#### Install Required Packages
```bash
sudo apt install -y nginx mysql-server redis-server supervisor git curl unzip
```

#### Install PHP Extensions
```bash
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl
```

#### Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Install Node.js
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

---

### Step 2: Application Setup

#### Clone Repository
```bash
cd /var/www
sudo git clone <your-repo-url> z-syst
cd z-syst
```

#### Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/z-syst
sudo chmod -R 755 /var/www/z-syst
sudo chmod -R 777 /var/www/z-syst/storage
sudo chmod -R 777 /var/www/z-syst/bootstrap/cache
```

#### Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

---

### Step 3: Environment Configuration

#### Copy Environment File
```bash
cp .env.production .env
```

#### Generate Application Key
```bash
php artisan key:generate
```

#### Configure Environment Variables
Edit `.env` file:
```env
APP_NAME="Z-Syst Pharmacy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=z_syst_pharmacy
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
```

---

### Step 4: Database Setup

#### Create Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE z_syst_pharmacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'z_syst_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON z_syst_pharmacy.* TO 'z_syst_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Run Migrations
```bash
php artisan migrate --force
```

#### Seed Database
```bash
php artisan db:seed --force
```

---

### Step 5: Application Optimization

#### Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Create Storage Link
```bash
php artisan storage:link
```

#### Optimize Composer
```bash
composer install --optimize-autoloader --no-dev
```

---

### Step 6: Nginx Configuration

#### Create Nginx Config
```bash
sudo nano /etc/nginx/sites-available/z-syst
```

Add this configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/z-syst/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

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
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/z-syst /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### Step 7: SSL/HTTPS Setup

#### Install Certbot
```bash
sudo apt install certbot python3-certbot-nginx
```

#### Obtain SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

#### Auto-Renewal
```bash
sudo certbot renew --dry-run
```

---

### Step 8: Queue Workers Setup

#### Install Supervisor
```bash
sudo apt install supervisor
```

#### Configure Supervisor
```bash
sudo cp supervisor.conf /etc/supervisor/conf.d/laravel-worker.conf
```

Update paths in the configuration file.

#### Start Workers
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

### Step 9: Scheduled Tasks

#### Add Cron Job
```bash
sudo crontab -e
```

Add this line:
```cron
* * * * * cd /var/www/z-syst && php artisan schedule:run >> /dev/null 2>&1
```

---

### Step 10: Monitoring Setup

#### Install Uptime Monitoring
```bash
sudo apt install monit
```

#### Configure Monit
```bash
sudo nano /etc/monit/monitrc
```

---

## 🔒 Security Hardening

### Firewall Setup
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Fail2Ban Setup
```bash
sudo apt install fail2ban
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
```

### Disable SSH Root Login
```bash
sudo nano /etc/ssh/sshd_config
```
Set:
```
PermitRootLogin no
PasswordAuthentication no
```

---

## 📊 Monitoring & Maintenance

### Check Logs
```bash
# Application logs
tail -f /var/www/z-syst/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log

# Queue worker logs
tail -f /var/www/z-syst/storage/logs/worker.log
```

### Database Backup
```bash
php artisan backup:run
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Management
```bash
# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush

# Check queue status
php artisan queue:failed
```

---

## 🔄 Updates & Maintenance

### Update Application
```bash
cd /var/www/z-syst
sudo git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart laravel-worker:*
```

### Update Dependencies
```bash
composer update
npm update
npm run build
```

---

## 🆘 Troubleshooting

### 502 Bad Gateway
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Database Connection Failed
```bash
sudo systemctl restart mysql
php artisan config:clear
```

### Queue Not Processing
```bash
sudo supervisorctl restart laravel-worker:*
php artisan queue:restart
```

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/z-syst
sudo chmod -R 755 /var/www/z-syst
sudo chmod -R 777 /var/www/z-syst/storage
```

---

## 📞 Support

For deployment issues:
- Email: support@z-syst.com
- Documentation: https://docs.z-syst.com
- GitHub Issues: https://github.com/z-syst/pharmacy/issues

---

## ✅ Pre-Deployment Checklist

- [ ] Server requirements met
- [ ] SSL certificate installed
- [ ] Database configured
- [ ] Environment variables set
- [ ] Migrations run successfully
- [ ] Seeders run successfully
- [ ] Cache configured
- [ ] Queue workers running
- [ ] Scheduled tasks configured
- [ ] Monitoring set up
- [ ] Security hardening applied
- [ ] Backup strategy in place
- [ ] SSL certificate valid
- [ ] Domain DNS configured
- [ ] Firewall configured
- [ ] Error tracking enabled

---

**Last Updated:** 2026-08-07  
**Version:** 1.0.0
