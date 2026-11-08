<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records tenant-scoped CRUD on core catalog models into audit_logs
 * (action: created/updated/deleted, model_type/model_id, actor, tenant).
 */
class ModelAuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    protected function record(Model $model, string $action): void
    {
        $user = auth()->user() ?: auth('sanctum')->user();
        if (! $user) {
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                'business_id' => $model->business_id ?? $user->business_id ?? null,
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $model->getMorphClass(),
                'model_id' => $model->getKey(),
                'description' => class_basename($model).' '.$action.'.',
                'ip_address' => request()?->ip(),
                'user_agent' => (string) request()?->userAgent(),
                'old_values' => null,
                'new_values' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e); // audit must never break the business action
        }
    }
}
