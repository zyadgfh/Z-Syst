<?php

namespace App\Policies;

use App\Models\InsuranceCompany;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InsuranceCompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, InsuranceCompany $company): bool
    {
        return $user->business_id === $company->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, InsuranceCompany $company): bool
    {
        return $user->business_id === $company->business_id;
    }

    public function delete(User $user, InsuranceCompany $company): bool
    {
        return $user->business_id === $company->business_id;
    }
}