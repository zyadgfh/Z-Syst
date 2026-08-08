# Production Deployment Checklist

## 🔧 Server Setup
- [ ] Ubuntu 20.04 LTS or higher installed
- [ ] Minimum 2GB RAM (4GB+ recommended)
- [ ] Minimum 20GB SSD storage
- [ ] 2+ CPU cores
- [ ] SSH access configured
- [ ] Firewall configured (UFW)
- [ ] Domain DNS configured
- [ ] SSL certificate ready

## 📦 Software Installation
- [ ] PHP 8.2+ installed
- [ ] PHP-FPM installed and configured
- [ ] Required PHP extensions installed:
  - [ ] php-mysql
  - [ ] php-xml
  - [ ] php-mbstring
  - [ ] php-curl
  - [ ] php-zip
  - [ ] php-bcmath
  - [ ] php-gd
  - [ ] php-intl
- [ ] MySQL 8.0+ installed
- [ ] Redis 6.0+ installed
- [ ] Nginx 1.18+ installed
- [ ] Composer 2.0+ installed
- [ ] Node.js 18+ installed
- [ ] Git 2.0+ installed
- [ ] Supervisor installed
- [ ] Fail2Ban installed

## 🚀 Application Setup
- [ ] Repository cloned to /var/www/z-syst
- [ ] Ownership set to www-data:www-data
- [ ] Permissions set correctly (755 for directories, 644 for files)
- [ ] Storage permissions set to 777
- [ ] Bootstrap cache permissions set to 777
- [ ] Composer dependencies installed: `composer install --no-dev --optimize-autoloader`
- [ ] Node dependencies installed: `npm install`
- [ ] Assets built: `npm run build`

## 🔐 Environment Configuration
- [ ] .env file created from .env.production
- [ ] APP_KEY generated: `php artisan key:generate`
- [ ] APP_ENV set to production
- [ ] APP_DEBUG set to false
- [ ] APP_URL configured with domain
- [ ] Database configured:
  - [ ] DB_CONNECTION=mysql
  - [ ] DB_HOST=127.0.0.1
  - [ ] DB_PORT=3306
  - [ ] DB_DATABASE configured
  - [ ] DB_USERNAME configured
  - [ ] DB_PASSWORD configured
- [ ] Cache configured:
  - [ ] CACHE_DRIVER=redis
  - [ ] REDIS_HOST configured
  - [ ] REDIS_PASSWORD configured
  - [ ] REDIS_PORT=6379
- [ ] Queue configured:
  - [ ] QUEUE_CONNECTION=redis
- [ ] Session configured:
  - [ ] SESSION_DRIVER=redis
- [ ] Mail configured (optional)
- [ ] Security settings configured:
  - [ ] SECURITY_ENABLED=true
  - [ ] SECURITY_MAX_LOGIN_ATTEMPTS=5
  - [ ] SECURITY_LOCKOUT_DURATION=15

## 🗄️ Database Setup
- [ ] MySQL service running
- [ ] Database created: `z_syst_pharmacy`
- [ ] Database user created with strong password
- [ ] User granted privileges on database
- [ ] Database charset set to utf8mb4
- [ ] Database collation set to utf8mb4_unicode_ci
- [ ] Migrations run: `php artisan migrate --force`
- [ ] Seeders run: `php artisan db:seed --force`
- [ ] Verify tables created
- [ ] Verify data seeded

## 🔗 SSL/HTTPS Setup
- [ ] Certbot installed
- [ ] SSL certificate obtained: `certbot --nginx`
- [ ] SSL certificate valid
- [ ] Auto-renewal configured
- [ ] HTTP redirects to HTTPS
- [ ] HSTS header configured
- [ ] Security headers configured

## 🌐 Nginx Configuration
- [ ] Nginx config file created
- [ ] Server block configured
- [ ] Root path set to /var/www/z-syst/public
- [ ] PHP-FPM socket configured
- [ ] FastCGI parameters configured
- [ ] Security headers added:
  - [ ] X-Frame-Options
  - [ ] X-Content-Type-Options
  - [ ] X-XSS-Protection
- [ ] Gzip compression enabled
- [ ] Client upload size configured
- [ ] Config tested: `nginx -t`
- [ ] Nginx restarted

## 🔄 Queue Workers
- [ ] Supervisor installed
- [ ] Supervisor config file created
- [ ] Laravel worker configured
- [ ] Paths updated in supervisor config
- [ ] Supervisor reloaded: `supervisorctl reread`
- [ ] Supervisor updated: `supervisorctl update`
- [ ] Workers started: `supervisorctl start laravel-worker:*`
- [ ] Workers status checked: `supervisorctl status`

## ⏰ Scheduled Tasks
- [ ] Cron job added to crontab
- [ ] Schedule command configured: `* * * * * cd /var/www/z-syst && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Cron job tested
- [ ] Backup schedule configured

## 📊 Cache & Optimization
- [ ] Config cached: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Views cached: `php artisan view:cache`
- [ ] Storage link created: `php artisan storage:link`
- [ ] OPcache enabled
- [ ] Redis configured
- [ ] Cache working

## 🔒 Security Hardening
- [ ] SSH root login disabled
- [ ] SSH password authentication disabled
- [ ] SSH key authentication enabled
- [ ] Firewall enabled: `ufw enable`
- [ ] Firewall rules configured:
  - [ ] Port 22 (SSH) allowed
  - [ ] Port 80 (HTTP) allowed
  - [ ] Port 443 (HTTPS) allowed
  - [ ] Other ports closed
- [ ] Fail2Ban installed and configured
- [ ] File permissions reviewed
- [ ] Sensitive files protected (.env, .git)
- [ ] Disable directory listing
- [ ] Remove server signatures

## 📈 Monitoring & Logging
- [ ] Log rotation configured
- [ ] Error tracking configured (Sentry/Bugsnag)
- [ ] Performance logging enabled
- [ ] Security logging enabled
- [ ] Audit logging enabled
- [ ] Health check endpoint configured
- [ ] Metrics endpoint configured
- [ ] Slack alerts configured (optional)
- [ ] Email alerts configured (optional)
- [ ] Uptime monitoring configured

## 🧪 Testing
- [ ] Unit tests run: `php artisan test --testsuite=Unit`
- [ ] Feature tests run: `php artisan test --testsuite=Feature`
- [ ] Security tests run: `php artisan test --filter=Security`
- [ ] Test coverage checked: `./run-coverage.sh`
- [ ] Coverage > 70%
- [ ] All tests passing
- [ ] No critical security issues

## 📋 Pre-Launch Verification
- [ ] Health check endpoint accessible
- [ ] Application loads without errors
- [ ] Login works
- [ ] Dashboard loads
- [ ] Database connections working
- [ ] Cache working
- [ ] Queue workers processing
- [ ] Scheduled tasks running
- [ ] Emails sending (if configured)
- [ ] File uploads working
- [ ] PDF generation working
- [ ] Reports generating
- [ ] API endpoints working
- [ ] Mobile responsive
- [ ] Accessibility features working

## 🔄 Backup Strategy
- [ ] Database backup configured
- [ ] File backup configured
- [ ] Backup schedule set (daily at 2 AM)
- [ ] Backup retention configured (30 days)
- [ ] Backup restoration tested
- [ ] Offsite backup configured (optional)
- [ ] Backup notifications configured

## 📚 Documentation
- [ ] README.md updated
- [ ] Deployment guide completed
- [ ] API documentation available
- [ ] User guide available
- [ ] Admin guide available
- [ ] Troubleshooting guide available
- [ ] Contact information updated

## 🎯 Final Checks
- [ ] SSL certificate valid
- [ ] Domain pointing correctly
- [ ] DNS propagation complete
- [ ] Load time acceptable (< 3 seconds)
- [ ] Mobile optimization working
- [ ] SEO basics configured
- [ ] Analytics installed (optional)
- [ ] Terms of service available
- [ ] Privacy policy available
- [ ] Cookie policy available

## 🚀 Launch
- [ ] Stakeholders notified
- [ ] Maintenance mode enabled during launch
- [ ] Final backup taken
- [ ] Application deployed
- [ ] Maintenance mode disabled
- [ ] Post-launch monitoring active
- [ ] Emergency plan ready
- [ ] Support team notified

## 📞 Post-Launch
- [ ] Monitor error logs for 24 hours
- [ ] Monitor performance metrics
- [ ] Check user feedback
- [ ] Verify all features working
- [ ] Monitor backup success
- [ ] Monitor queue workers
- [ ] Update documentation if needed
- [ ] Plan next release

---

## 🎯 Critical Path (Must Complete Before Launch)

1. ✅ Server setup and software installation
2. ✅ Database setup and migrations
3. ✅ SSL/HTTPS configuration
4. ✅ Security hardening
5. ✅ Cache and optimization
6. ✅ Queue workers setup
7. ✅ Monitoring and logging
8. ✅ All tests passing
9. ✅ Pre-launch verification
10. ✅ Backup strategy confirmed

---

**Checklist Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Status:** Ready for Production Deployment
