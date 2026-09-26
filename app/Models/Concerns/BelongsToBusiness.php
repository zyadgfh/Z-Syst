<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder): void {
            $businessId = app()->bound('tenant_id') ? app('tenant_id') : null;

            if ($businessId !== null) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('business_id'),
                    (int) $businessId
                );
            }
        });

        static::creating(function ($model): void {
            $businessId = app()->bound('tenant_id') ? app('tenant_id') : null;

            if ($businessId === null) {
                return;
            }

            if ($model->getAttribute('business_id') === null) {
                $model->setAttribute('business_id', (int) $businessId);
                return;
            }

            if ((int) $model->getAttribute('business_id') !== (int) $businessId) {
                abort(403, 'Resource does not belong to the current tenant.');
            }
        });

        static::updating(function ($model): void {
            $businessId = app()->bound('tenant_id') ? app('tenant_id') : null;

            if ($businessId === null) {
                return;
            }

            if ($model->isDirty('business_id')
                && (int) $model->getAttribute('business_id') !== (int) $businessId) {
                abort(403, 'Resource cannot be moved to another tenant.');
            }

            if ($model->getAttribute('business_id') !== null
                && (int) $model->getAttribute('business_id') !== (int) $businessId) {
                abort(403, 'Resource does not belong to the current tenant.');
            }
        });
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->withoutGlobalScope('business')
            ->where($query->getModel()->qualifyColumn('business_id'), $businessId);
    }
}
