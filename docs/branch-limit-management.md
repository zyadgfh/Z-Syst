# Branch Limit Management System

## Overview

The Branch Limit Management System allows Super Administrators to control and enforce limits on the number of branches each company can create. This system provides flexible configuration, real-time validation, and comprehensive monitoring.

## Database Structure

### companies Table Additions

| Column | Type | Description |
|--------|------|-------------|
| `max_branches` | unsignedInteger | Maximum number of branches allowed (NULL for unlimited if `is_unlimited_branches` is true) |
| `is_unlimited_branches` | boolean | Flag to enable unlimited branch creation (default: false) |
| `default_branch_limit` | unsignedInteger | Stores the original plan limit for reset purposes |
| `branch_limit_updated_at` | timestamp | When the limit was last modified |
| `branch_limit_updated_by` | foreignKey (users) | Administrator who made the change |

### Key Relationships

- **Company → User**: `branch_limit_updated_by` (who modified the limit)
- **Company → Branch**: One-to-many relationship (branches belong to a company)

## Business Rules

1. **Unlimited Mode**: When `is_unlimited_branches` is true, all restrictions are ignored.
2. **Limit Enforcement**: Before creating a branch, the system checks:
   - Is unlimited mode enabled?
   - Is `max_branches` NULL?
   - Is current branch count < max_branches?
3. **Decreasing Limits**: Administrators can decrease limits without deleting existing branches. Existing branches remain, but new creation is blocked.
4. **Override Persistence**: Custom limits override plan defaults. Admins can reset to plan default using `reset_to_default`.
5. **Subscription Integration**: Plans can set default limits. Companies inherit these on subscription. Admins can override per-company.

## Validation Flow

```
User attempts to create a branch
         ↓
Load company branch limit settings
         ↓
Check is_unlimited_branches
         ↓ YES → Allow creation
         ↓ NO
Check max_branches is NULL
         ↓ YES → Allow creation
         ↓ NO
Count current branches
         ↓
Compare count < max_branches
         ↓ YES → Allow creation
         ↓ NO → Throw Exception: "You have reached the maximum number..."
```

## Override Logic

### Setting a Custom Limit

```php
// Admin sets custom limit for company
$company->update([
    'max_branches' => 25,
    'is_unlimited_branches' => false,
    'default_branch_limit' => $company->default_branch_limit, // preserves plan default
    'branch_limit_updated_at' => now(),
    'branch_limit_updated_by' => auth()->id(),
]);
```

### Resetting to Plan Default

```php
// Admin resets to original plan limit
$company->update([
    'max_branches' => $company->default_branch_limit,
    'is_unlimited_branches' => false,
    'branch_limit_updated_at' => now(),
    'branch_limit_updated_by' => auth()->id(),
]);
```

## API Endpoints

### Authentication

All endpoints require:
- `auth:sanctum` middleware
- `can:super-admin` ability for admin routes

### Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/admin/companies` | List all companies with branch counts and limits |
| GET | `/api/admin/companies/{company}` | Show specific company details |
| PUT | `/api/admin/companies/{company}/branch-limit` | Update company branch limit |
| POST | `/api/admin/companies/bulk-update-branch-limits` | Bulk update multiple companies |
| GET | `/api/admin/companies/{company}/branch-availability` | Check if company can create new branch |

### Rate Limiting

- Read operations: 60 requests/minute
- Write operations: 30 requests/minute

## Usage

### In Controllers

```php
use App\Services\BranchLimitService;

public function store(Request $request, BranchLimitService $branchLimitService)
{
    $company = auth()->user()->company;
    
    // Enforce limit before creation
    $branchLimitService->enforceBeforeCreate($company);
    
    // Create branch
    $branch = Branch::create($request->validated());
    
    // Increment cached count
    $branchLimitService->incrementBranchCount($company);
    
    return response()->json($branch, 201);
}
```

### In Services

```php
// Check availability
if ($branchLimitService->canCreateBranch($company)) {
    // Proceed with creation
}

// Get statistics
$remaining = $branchLimitService->getRemainingBranches($company);
$percentage = $branchLimitService->getUsagePercentage($company);

// Notify thresholds
$branchLimitService->checkAndNotify($company);
```

## Caching

### Cache Keys

| Key | TTL | Purpose |
|-----|-----|---------|
| `company:{id}:branches` | 300s | Current branch count |
| `company:{id}:limit` | 300s | Effective limit |
| `branch_limits_stats` | 600s | Global statistics |

### Cache Invalidation

Cache is cleared when:
- Branch is created/deleted (increment/decrement)
- Branch limit is updated
- Admin manually invalidates

## Notifications

### Notification Triggers

- **80% Usage**: `type = 'warning'`
- **90% Usage**: `type = 'critical'`
- **100% Usage**: `type = 'limit_reached'`
- **Manual Update**: `type = 'updated'`

### Notification Channels

- Database (for in-app notifications)
- Mail (for email alerts)

## Dashboard Metrics

Display for each company:

| Metric | Calculation |
|--------|-------------|
| Allowed Branches | `max_branches` or "Unlimited" |
| Current Branches | Count of branches where `company_id` matches |
| Remaining Branches | `max_branches - current` or NULL if unlimited |
| Usage Percentage | `(current / max) * 100` |
| Progress Bar | Visual representation of percentage |
| Warning State | `usage_percentage >= 80` |

## Console Commands

### Check Branch Limits

```bash
php artisan branches:check-limits
```

Displays:
- Total companies
- Unlimited companies
- Companies near limit (>=80%)
- Companies at limit (100%)
- Total branches system-wide

Schedule this command in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('branches:check-limits')->daily();
}
```

## Testing

### Unit Tests

Located in `tests/Unit/BranchLimitTest.php`:

- Unlimited branch creation
- Limit enforcement
- Decreasing limits without deletion
- Caching behavior
- Notification triggers

### Feature Tests

Create additional tests in `tests/Feature/`:

- API endpoint authorization
- Validation rules
- Bulk updates
- Rate limiting

### Running Tests

```bash
php artisan test
```

## Security Considerations

1. **Authorization**: Only Super Admins can modify limits
2. **Policy Checks**: `CompanyPolicy` validates all actions
3. **Form Requests**: All inputs validated server-side
4. **Rate Limiting**: Prevents abuse of API endpoints
5. **SQL Injection**:Protected by Eloquent ORM
6. **XSS/CSRF**: Standard Laravel protections apply

## Performance Optimizations

1. **Cached Branch Counts**: Avoid repeated COUNT queries
2. **Eager Loading**: `withCount('branches')` for list views
3. **Database Indexes**: Add indexes on `company_id` in branches table
4. **Pagination**: Limit results to 25 per page
5. **Service Layer**: Centralized logic reduces duplication

### Recommended Indexes

```php
Schema::table('branches', function (Blueprint $table) {
    $table->index('company_id');
});
```

## Integration with Subscriptions

### Subscription Change Event

When a company's subscription changes:

```php
event(new SubscriptionChanged($company));

// Listener
public function handle(SubscriptionChanged $event)
{
    $company = $event->company;
    
    // Option 1: Keep custom limit
    // Do nothing, custom limit remains
    
    // Option 2: Reset to plan default
    $company->update([
        'max_branches' => $company->subscriptionPlan->branch_limit,
        'default_branch_limit' => $company->subscriptionPlan->branch_limit,
        'is_unlimited_branches' => false,
    ]);
}
```

## Troubleshooting

### Cache Not Updating

```bash
php artisan cache:forget "company:{id}:branches"
php artisan cache:forget "company:{id}:limit"
php artisan cache:forget "branch_limits_stats"
```

### Notification Not Sending

Check:
- Mail configuration in `.env`
- Queue worker is running (`php artisan queue:work`)
- Notification channels are enabled in `config/branch-limit.php`

## Maintenance

### Regular Tasks

1. Monitor cache hit rates
2. Review slow query log for branch counting
3. Archive old branches if needed
4. Review notification frequency
5. Audit admin actions on limits

### Scaling Considerations

For thousands of companies:
- Use Redis for cache backend
- Implement queue workers for notifications
- Consider read replicas for reporting queries
- Use database partitioning for branches table by company_id

## License

Proprietary - Z-Syst