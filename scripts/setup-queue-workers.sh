#!/bin/bash
# ============================================================
# Z-Syst Queue Workers Setup Script
# Sets up Redis, Horizon, and Supervisor for production
# ============================================================

set -e

echo "🔧 Setting up Z-Syst queue workers..."

# 1. Check Redis is installed and running
echo "1. Checking Redis..."
if command -v redis-cli &> /dev/null; then
    if redis-cli ping | grep -q "PONG"; then
        echo "   ✅ Redis is running"
    else
        echo "   ⚠️  Redis is installed but not running. Starting..."
        sudo systemctl start redis-server || sudo service redis-server start
    fi
else
    echo "   ❌ Redis is not installed. Installing..."
    sudo apt-get update && sudo apt-get install -y redis-server
    sudo systemctl enable redis-server
    sudo systemctl start redis-server
fi

# 2. Update .env for Redis queue
echo "2. Configuring .env for Redis queue..."
APP_DIR="/var/www/z-syst"
ENV_FILE="$APP_DIR/.env"

if [ -f "$ENV_FILE" ]; then
    # Update QUEUE_CONNECTION to redis
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' "$ENV_FILE"
    # Ensure Redis config
    grep -q "^REDIS_HOST=" "$ENV_FILE" && sed -i 's/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' "$ENV_FILE" || echo "REDIS_HOST=127.0.0.1" >> "$ENV_FILE"
    grep -q "^REDIS_PORT=" "$ENV_FILE" && sed -i 's/^REDIS_PORT=.*/REDIS_PORT=6379/' "$ENV_FILE" || echo "REDIS_PORT=6379" >> "$ENV_FILE"
    grep -q "^REDIS_PASSWORD=" "$ENV_FILE" || echo "REDIS_PASSWORD=" >> "$ENV_FILE"
    echo "   ✅ .env updated"
else
    echo "   ❌ .env not found at $ENV_FILE"
fi

# 3. Run Horizon publish
echo "3. Publishing Horizon assets..."
cd "$APP_DIR"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider" --tag=horizon-assets --force 2>/dev/null || true
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider" --tag=horizon-config --force 2>/dev/null || true

# 4. Optimize for production
echo "4. Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Setup Supervisor
echo "5. Setting up Supervisor..."
SUPERVISOR_CONF="/etc/supervisor/conf.d/z-syst.conf"
if [ -d "/etc/supervisor/conf.d" ]; then
    sudo cp "$APP_DIR/supervisor.conf" "$SUPERVISOR_CONF"
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start "z-syst:*"
    echo "   ✅ Supervisor configured and started"
else
    echo "   ⚠️  Supervisor not installed. Installing..."
    sudo apt-get install -y supervisor
    sudo cp "$APP_DIR/supervisor.conf" "$SUPERVISOR_CONF"
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start "z-syst:*"
    echo "   ✅ Supervisor installed and configured"
fi

# 6. Verify workers are running
echo "6. Verifying workers..."
sleep 2
sudo supervisorctl status

echo ""
echo "✅ Queue workers setup complete!"
echo ""
echo "Commands to manage workers:"
echo "  sudo supervisorctl status          # Check worker status"
echo "  sudo supervisorctl restart z-syst:* # Restart all workers"
echo "  php artisan horizon:status         # Check Horizon status"
echo "  php artisan queue:work redis       # Manual worker (dev)"
