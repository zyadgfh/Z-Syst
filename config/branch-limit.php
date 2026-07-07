<?php

return [
    'warning_threshold' => env('BRANCH_LIMIT_WARNING_THRESHOLD', 80),
    'critical_threshold' => env('BRANCH_LIMIT_CRITICAL_THRESHOLD', 90),
    'cache_ttl' => env('BRANCH_LIMIT_CACHE_TTL', 300),
    'stats_cache_ttl' => env('BRANCH_LIMIT_STATS_CACHE_TTL', 600),
    'notification_channels' => ['database', 'mail'],
    'api_rate_limit' => env('BRANCH_LIMIT_API_RATE_LIMIT', 60),
];
