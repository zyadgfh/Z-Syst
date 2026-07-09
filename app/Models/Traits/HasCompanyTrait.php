<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCompanyTrait
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
