#!/bin/bash
# Z-Syst Staging Environment Setup
# Run this on a fresh MySQL server to set up the staging environment

set -e

echo "🏥 Z-Syst Staging Environment Setup"
echo "===================================="

# Check prerequisites
command -v php >/dev/null 2>&1 || { echo "❌ PHP is required. Install PHP 8.2+"; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "❌ MySQL client is required"; exit 1; }

# Load .env.staging
if [ -f .env.staging ]; then
    export $(cat .env.staging | grep -v '^#' | xargs)
    echo "✅ Loaded .env.staging"
else
    echo "❌ .env.staging not found"
    exit 1
fi

# Create database
echo "📦 Creating database ${DB_DATABASE}..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true

# Create user
echo "👤 Creating database user..."
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null || true
mysql -u root -e "GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'${DB_HOST}';" 2>/dev/null || true
mysql -u root -e "FLUSH PRIVILEGES;" 2>/dev/null || true

# Copy .env.staging to .env
cp .env.staging .env
echo "✅ Copied .env.staging to .env"

# Generate app key
php artisan key:generate --force
echo "✅ Generated application key"

# Install dependencies
composer install --no-interaction --prefer-dist
echo "✅ Installed Composer dependencies"

npm install --production
echo "✅ Installed NPM dependencies"

# Run migrations
php artisan migrate --force
echo "✅ Ran database migrations"

# Seed demo data
php artisan db:seed --class=DemoDataSeeder --force
echo "✅ Seeded demo data"

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Cached configuration, routes, and views"

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "✅ Set file permissions"

echo ""
echo "🎉 Staging environment setup complete!"
echo ""
echo "📋 Summary:"
echo "   Database: ${DB_DATABASE}"
echo "   URL: ${APP_URL}"
echo "   Debug: ${APP_DEBUG}"
echo ""
echo "🚀 To start the server:"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "👤 Login with:"
echo "   Email: admin@z-syst.com"
echo "   Password: password"
