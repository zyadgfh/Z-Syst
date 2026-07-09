<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\Request;

class TenantManager
{
    protected ?Company $company = null;

    /**
     * Resolve tenant from request using header, authenticated user, or route.
     */
    public function resolveFromRequest(Request $request): ?Company
    {
        // 1) Explicit header
        if ($request->header('X-Company-Id')) {
            $id = $request->header('X-Company-Id');
            $this->company = Company::find($id);
            if ($this->company) {
                return $this->company;
            }
        }

        // 2) Authenticated user
        if ($request->user() && $request->user()->company_id) {
            $this->company = Company::find($request->user()->company_id);
            if ($this->company) {
                return $this->company;
            }
        }

        // 3) Host/Subdomain (e.g. tenant.example.com -> slug = tenant)
        $host = $request->getHost();
        if ($host && str_contains($host, '.')) {
            $parts = explode('.', $host);
            $subdomain = $parts[0];
            if (! in_array($subdomain, ['www', 'localhost', '127'])) {
                $this->company = Company::where('slug', $subdomain)->first();
                if ($this->company) {
                    return $this->company;
                }
            }
        }

        // 4) Route model binding (company)
        $routeCompany = $request->route('company');
        if ($routeCompany instanceof Company) {
            $this->company = $routeCompany;

            return $this->company;
        }

        return null;
    }

    public function bindTenant(?Company $company): void
    {
        if ($company) {
            $id = $company->id;
            app()->instance('tenant.company_id', $id);
            config(['tenant.company_id' => $id]);
            $this->company = $company;
        }
    }

    public function getCompanyId(): ?int
    {
        if ($this->company) {
            return $this->company->id;
        }

        return app()->bound('tenant.company_id') ? app('tenant.company_id') : null;
    }
}
