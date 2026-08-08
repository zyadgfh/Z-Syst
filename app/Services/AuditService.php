<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action
     */
    public function log(string $action, ?Model $model = null, array $data = []): AuditLog
    {
        return AuditLog::create([
            'business_id' => auth()->user()?->business_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $data['description'] ?? $this->generateDescription($action, $model),
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log model creation
     */
    public function logCreated(Model $model, array $data = []): AuditLog
    {
        return $this->log('created', $model, array_merge($data, [
            'new_values' => $model->getAttributes(),
        ]));
    }

    /**
     * Log model update
     */
    public function logUpdated(Model $model, array $oldValues, array $newValues): AuditLog
    {
        return $this->log('updated', $model, [
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Log model deletion
     */
    public function logDeleted(Model $model, array $data = []): AuditLog
    {
        return $this->log('deleted', $model, array_merge($data, [
            'old_values' => $model->getAttributes(),
        ]));
    }

    /**
     * Log login
     */
    public function logLogin(?Model $user = null): AuditLog
    {
        return $this->log('login', $user, [
            'description' => 'User logged in',
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout(?Model $user = null): AuditLog
    {
        return $this->log('logout', $user, [
            'description' => 'User logged out',
        ]);
    }

    /**
     * Log failed login
     */
    public function logFailedLogin(string $email): AuditLog
    {
        return $this->log('failed_login', null, [
            'description' => "Failed login attempt for email: {$email}",
        ]);
    }

    /**
     * Log export
     */
    public function logExport(string $exportType, array $filters = []): AuditLog
    {
        return $this->log('export', null, [
            'description' => "Exported data: {$exportType}",
            'new_values' => $filters,
        ]);
    }

    /**
     * Log import
     */
    public function logImport(string $importType, int $count): AuditLog
    {
        return $this->log('import', null, [
            'description' => "Imported {$count} records for: {$importType}",
            'new_values' => ['count' => $count],
        ]);
    }

    /**
     * Get audit logs for business
     */
    public function getLogsForBusiness(int $businessId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::forBusiness($businessId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['model_type'])) {
            $query->forModel($filters['model_type'], $filters['model_id'] ?? null);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Get audit logs for model
     */
    public function getLogsForModel(Model $model): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::forModel(get_class($model), $model->id)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs for user
     */
    public function getLogsForUser(int $userId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::forUser($userId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(int $businessId, array $filters = []): array
    {
        $query = AuditLog::forBusiness($businessId);

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        $logs = $query->get();

        return [
            'total_logs' => $logs->count(),
            'logs_by_action' => $logs->groupBy('action')->map->count(),
            'logs_by_user' => $logs->groupBy('user_id')->map->count(),
            'logs_by_model' => $logs->whereNotNull('model_type')->groupBy('model_type')->map->count(),
            'recent_logs' => $logs->take(10),
        ];
    }

    /**
     * Clean old audit logs
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Generate description for audit log
     */
    protected function generateDescription(string $action, ?Model $model): string
    {
        if (!$model) {
            return ucfirst($action);
        }

        $modelName = class_basename($model);
        $modelId = $model->id;

        return match($action) {
            'created' => "Created {$modelName} #{$modelId}",
            'updated' => "Updated {$modelName} #{$modelId}",
            'deleted' => "Deleted {$modelName} #{$modelId}",
            'restored' => "Restored {$modelName} #{$modelId}",
            default => ucfirst($action) . " {$modelName} #{$modelId}",
        };
    }
}
