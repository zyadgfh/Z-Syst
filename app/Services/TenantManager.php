<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\Request;

class TenantManager
{
    protected ?Company $company = null;

    /**
     * Resolve tenant from request using authenticated user, header, or route.
     *
     * SECURITY: Authenticated user's company is used as the PRIMARY source.
     * The X-Company-Id header is only respected if it matches the user's company
     * (prevents IDOR / tenant hopping).
     */
    public function resolveFromRequest(Request $request): ?Company
    {
        // 0) Authenticated user is the PRIMARY tenant source (most secure)
        if ($request->user() && $request->user()->company_id) {
            $this->company = Company::find($request->user()->company_id);
            if ($this->company) {
                return $this->company;
            }
        }

        // 1) Explicit header — ONLY for unauthenticated requests (e.g., webhooks)
        if (! $request->user() && $request->header('X-Company-Id')) {
            $id = $request->header('X-Company-Id');
            $this->company = Company::find($id);
            if ($this->company) {
                return $this->company;
            }
        }

        // 2) Host/Subdomain (e.g. tenant.example.com -> slug = tenant)
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

        // 3) Route model binding (company)
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

            // Log tenant context
            \Log::info('Tenant bound', [
                'company_id' => $id,
                'company_name' => $company->name,
            ]);
        }
    }

    public function switchTenant(Company $company): void
    {
        $this->bindTenant($company);
    }

    public function clearTenant(): void
    {
        app()->forgetInstance('tenant.company_id');
        config(['tenant.company_id' => null]);
        $this->company = null;

        \Log::info('Tenant cleared');
    }

    public function getCompanyId(): ?int
    {
        if ($this->company) {
            return $this->company->id;
        }

        return app()->bound('tenant.company_id') ? app('tenant.company_id') : null;
    }
}
