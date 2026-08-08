# Queue Workers Setup

## Supervisor Configuration

### Install Supervisor
```bash
sudo apt-get update
sudo apt-get install supervisor
```

### Configure Laravel Queue Worker
1. Copy the supervisor configuration:
```bash
sudo cp supervisor.conf /etc/supervisor/conf.d/laravel-worker.conf
```

2. Update the path in the configuration to match your deployment path:
```ini
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
stdout_logfile=/path/to/your/project/storage/logs/worker.log
```

3. Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Alternative: Systemd Service
Create `/etc/systemd/system/laravel-worker.service`:
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/z-syst
ExecStart=/usr/bin/php /var/www/z-syst/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

Start the service:
```bash
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
```

### Queue Commands
```bash
# Start queue worker manually
php artisan queue:work redis

# Start queue worker in background
php artisan queue:work redis --daemon

# Stop queue worker
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### Monitoring Queue
```bash
# Check queue status
php artisan queue:failed

# List queues
php artisan queue:listen redis
```
