# Error Tracking Setup

## Sentry Integration

### Installation
```bash
composer require sentry/sentry-laravel
```

### Configuration
Add to your `.env` file:
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
```

### Publish Config
```bash
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

### Testing
```bash
php artisan test
```

## Alternative: Bugsnag

### Installation
```bash
composer require bugsnag/bugsnag-laravel
```

### Configuration
Add to your `.env` file:
```env
BUGSNAG_API_KEY=your-api-key
```

## Alternative: Ray

### Installation
```bash
composer require spatie/laravel-ray
```

### Usage
```php
ray('Hello World');
ray()->showQueries();
```

## Custom Error Handling

### Exception Handler
Update `app/Exceptions/Handler.php`:

```php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if (app()->environment('production')) {
            // Send to error tracking service
            \Sentry\captureException($e);
        }
    });
}
```

### Custom Logging
```php
use Illuminate\Support\Facades\Log;

// Security events
Log::channel('security')->warning('Security event detected', [
    'type' => 'failed_login',
    'ip' => request()->ip(),
]);

// Performance metrics
Log::channel('performance')->info('Query executed', [
    'query' => $query,
    'time' => $executionTime,
]);

// Audit trail
Log::channel('audit')->info('User action', [
    'user_id' => auth()->id(),
    'action' => 'product_created',
    'details' => $productData,
]);
```

## Log Rotation

### Configure Log Rotation
Add to `config/logging.php`:
```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14,
],
```

### Setup Logrotate (Linux)
Create `/etc/logrotate.d/laravel`:
```
/var/www/z-syst/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php-fpm
    endscript
}
```

## Monitoring Endpoints

### Health Check
Create route in `routes/web.php`:
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'up' : 'down',
            'cache' => Cache::get('health_check') ? 'up' : 'down',
            'queue' => Queue::size() < 100 ? 'up' : 'down',
        ],
    ]);
});
```

### Metrics Endpoint
```php
Route::get('/metrics', function () {
    return response()->json([
        'users' => User::count(),
        'businesses' => Business::count(),
        'products' => Product::count(),
        'sales_today' => Sale::whereDate('created_at', today())->count(),
        'queue_size' => Queue::size(),
    ]);
});
```

## Alert Configuration

### Slack Alerts
Add to `config/logging.php`:
```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'Laravel Log',
    'emoji' => ':boom:',
    'level' => 'critical',
],
```

### Email Alerts
Create custom notification:
```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CriticalError extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Critical Error Detected')
            ->line('A critical error has occurred in the application.')
            ->line($this->exception->getMessage());
    }
}
```
