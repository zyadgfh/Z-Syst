<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * HasCompanyScope Trait
 * 
 * Automatically scopes queries to current tenant company.
 * Applied to all multi-tenant models.
 */
trait HasCompanyScope
{
    /**
     * Boot the trait
     */
    public static function bootHasCompanyScope(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check() && !$model->company_id) {
                $user = auth()->user();
                $model->company_id = $user->company_id;
                if ($user->branch_id) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });

        static::addGlobalScope('company', function ($query) {
            if (auth()->check()) {
                $user = auth()->user();
                $query->where('company_id', $user->company_id);
            }
        });
    }

    /**
     * Get the company that owns this model
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the branch that owns this model (if applicable)
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class)->nullable();
    }
}
