<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HasCompany trait (shim for backward compatibility).
 *
 * The canonical trait lives at App\Models\Traits\HasCompanyTrait.
 * This file was created to satisfy legacy `use App\Traits\HasCompany;`
 * imports across 40+ models. Prefer using HasCompanyTrait directly
 * in new code so the trait lives next to its consumers.
 *
 * @see \App\Models\Traits\HasCompanyTrait
 */
trait HasCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
