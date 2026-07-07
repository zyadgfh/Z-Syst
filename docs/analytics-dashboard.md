# Sales & Inventory Analytics Dashboard

## Overview

This comprehensive analytics dashboard provides pharmacy owners and managers with actionable insights into sales performance, inventory levels, and business metrics. The system leverages your existing product, sales, and inventory data to generate real-time analytics and reports.

## Features Implemented

### 1. **Analytics Service** (`app/Services/AnalyticsService.php`)
- **Sales Analytics**: Revenue trends, top-selling products, sales by category/branch, payment method breakdown
- **Inventory Analytics**: Stock summaries, low stock alerts, expiring products, dead stock identification, fast-moving products, stock turnover analysis
- **Caching**: Intelligent caching strategy to improve performance and reduce database load
- **Multi-tenancy**: Automatic company and branch scoping for data isolation

### 2. **Dashboard Controller** (`app/Http/Controllers/Admin/DashboardController.php`)
- RESTful API endpoints for all analytics features
- Flexible date range filtering
- Pagination and limit controls
- Consistent JSON response format with metadata

### 3. **Report Export Service** (`app/Services/ReportExportService.php`)
- CSV export functionality for:
  - Sales reports
  - Inventory summaries
  - Expiring products
  - Top-selling products
  - Stock turnover analysis
- Automatic file cleanup (7-day retention)
- Storage management

### 4. **Notification System**
- **Low Stock Notifications** (`app/Notifications/LowStockNotification.php`)
  - Email and database notifications
  - Urgency categorization (critical, high, medium, low)
  - Branch-specific alerts
- **Expiry Date Notifications** (`app/Notifications/ExpiryDateNotification.php`)
  - Configurable warning periods (default: 30 days)
  - Urgent alerts for items expiring within 7 days
  - Batch-specific tracking

### 5. **Scheduled Command** (`app/Console/Commands/CalculateDailyStockMetrics.php`)
- Daily automated calculation of stock metrics
- Proactive notification dispatching
- Cache management
- Runs at 1:00 AM daily

### 6. **API Resources** (`app/Http/Resources/`)
- Consistent API response formatting
- Type-safe data transformation
- Resources for:
  - Dashboard KPIs
  - Inventory summaries
  - Low stock products
  - Expiring products
  - Sales trends
  - Top-selling products
  - Stock turnover

### 7. **Configuration** (`config/analytics.php`)
- Cache TTL settings
- Notification preferences
- Export configurations
- Turnover category definitions
- Urgency level thresholds
- Scheduled task settings

### 8. **Comprehensive Testing** (`tests/Unit/AnalyticsServiceTest.php`)
- Unit tests for all major service methods
- Factory classes for test data generation
- Cache validation tests
- Branch scoping verification

## API Endpoints

### Authentication Required
All endpoints require `auth:sanctum` middleware.

### Dashboard KPIs
```
GET /api/v1/dashboard/kpis
```
Returns key performance indicators including total sales, revenue, stock value, and alert counts.

### Sales Analytics
```
GET /api/v1/dashboard/sales-trends?period=daily&start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/top-selling-products?limit=10&start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/sales-by-category?start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/sales-by-branch?start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/payment-method-breakdown?start_date=2024-01-01&end_date=2024-01-31
```

### Inventory Analytics
```
GET /api/v1/dashboard/inventory-summary
GET /api/v1/dashboard/low-stock-products?limit=20
GET /api/v1/dashboard/out-of-stock-products?limit=20
GET /api/v1/dashboard/expiring-products?days=30&limit=20
GET /api/v1/dashboard/dead-stock?days=90&limit=20
GET /api/v1/dashboard/fast-moving-products?days=30&limit=20
GET /api/v1/dashboard/stock-turnover?days=90
```

### Report Exports
```
GET /api/v1/dashboard/export/sales?start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/export/inventory
GET /api/v1/dashboard/export/expiring-products?days=30
GET /api/v1/dashboard/export/top-selling-products?limit=20&start_date=2024-01-01&end_date=2024-01-31
GET /api/v1/dashboard/export/stock-turnover?days=90
```

### Cache Management
```
POST /api/v1/dashboard/clear-cache
```

## Usage Examples

### Get Dashboard KPIs
```bash
curl -X GET "http://your-domain.com/api/v1/dashboard/kpis" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Get Sales Trends
```bash
curl -X GET "http://your-domain.com/api/v1/dashboard/sales-trends?period=daily&start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Get Low Stock Products
```bash
curl -X GET "http://your-domain.com/api/v1/dashboard/low-stock-products?limit=20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Export Sales Report
```bash
curl -X GET "http://your-domain.com/api/v1/dashboard/export/sales?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

## Configuration

### Environment Variables
Add these to your `.env` file:

```env
# Analytics Cache Settings
ANALYTICS_KPIS_CACHE_TTL=300
ANALYTICS_SALES_TRENDS_CACHE_TTL=600
ANALYTICS_INVENTORY_SUMMARY_CACHE_TTL=300

# Notification Settings
LOW_STOCK_NOTIFICATIONS_ENABLED=true
LOW_STOCK_NOTIFICATION_CHANNELS=mail,database
EXPIRY_NOTIFICATIONS_ENABLED=true
EXPIRY_NOTIFICATION_CHANNELS=mail,database
EXPIRY_WARNING_DAYS=30
EXPIRY_URGENT_DAYS=7

# Export Settings
ANALYTICS_EXPORT_DISK=local
ANALYTICS_EXPORT_PATH=exports
ANALYTICS_EXPORT_CLEANUP_DAYS=7
ANALYTICS_MAX_EXPORT_ROWS=10000

# Scheduled Tasks
ANALYTICS_DAILY_METRICS_ENABLED=true
ANALYTICS_DAILY_METRICS_SCHEDULE=0:00
```

## Scheduled Tasks

The system includes two scheduled tasks configured in `app/Console/Kernel.php`:

```php
// Daily analytics calculation (runs at 1:00 AM)
$schedule->command('analytics:calculate-daily-metrics')->dailyAt('01:00');
```

To enable the scheduler, add this to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Running Tests

```bash
# Run all analytics tests
php artisan test --filter=AnalyticsServiceTest

# Run specific test
php artisan test --filter=it_can_get_dashboard_kpis
```

## Performance Considerations

### Caching Strategy
- KPIs: 5 minutes TTL
- Sales trends: 10 minutes TTL
- Inventory summary: 5 minutes TTL
- Dead stock analysis: 30 minutes TTL (computationally intensive)

### Database Optimization
- Ensure proper indexes on:
  - `sales.company_id`, `sales.created_at`
  - `sale_items.product_id`, `sale_items.sale_id`
  - `product_stocks.company_id`, `product_stocks.product_id`
  - `product_stocks.expiry_date`

### Rate Limiting
- Read operations: 60 requests/minute
- Write operations: 30 requests/minute
- Export operations: 10 requests/minute

## Security

- All endpoints require authentication
- Automatic company/branch data scoping
- Rate limiting to prevent abuse
- Input validation on all parameters
- SQL injection protection via Eloquent ORM

## Future Enhancements

Potential improvements for future iterations:

1. **Real-time Dashboard**: WebSocket integration for live updates
2. **Advanced Filtering**: More granular filtering options
3. **Custom Reports**: User-defined report templates
4. **Predictive Analytics**: Sales forecasting and demand prediction
5. **PDF Generation**: Native PDF export support
6. **Chart Integration**: Built-in chart data APIs
7. **Mobile Notifications**: Push notification support
8. **Multi-currency Support**: Currency conversion for international operations

## Troubleshooting

### Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
```

### Notification Not Sending
- Check mail configuration in `.env`
- Ensure queue worker is running: `php artisan queue:work`
- Verify notification channels are enabled in config

### Export Files Not Downloading
- Check storage permissions
- Verify disk configuration in `config/filesystems.php`
- Ensure export directory exists: `storage/app/exports`

## Support

For issues or questions about the analytics dashboard, please refer to the main project documentation or contact the development team.