@extends('layouts.admin')

@section('title', $company->name)

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ $company->name }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.insurance.companies.edit', $company) }}" class="btn btn-primary-blue">
                <i class="fas fa-edit me-1"></i> {{ __('common.Edit') }}
            </a>
            <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Company Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Name') }}:</strong> {{ $company->name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Code') }}:</strong> <code>{{ $company->code }}</code>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Contact Person') }}:</strong> {{ $company->contact_person ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Phone') }}:</strong> {{ $company->phone ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Email') }}:</strong> {{ $company->email ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Status') }}:</strong>
                            <span class="badge bg-{{ $company->status === 'active' ? 'success' : ($company->status === 'suspended' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($company->status) }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Integration Type') }}:</strong> {{ ucfirst($company->integration_type) }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Tax ID') }}:</strong> {{ $company->tax_id ?? '—' }}
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong>{{ __('common.Address') }}:</strong> {{ $company->address ?? '—' }}, {{ $company->city ?? '' }} {{ $company->country ?? '' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Coverage Settings') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('common.Default Coverage') }}:</strong> {{ $company->default_coverage_percent }}%
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('common.Default Copay') }}:</strong> {{ $company->default_copay_percent }}%
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('common.Settlement Days') }}:</strong> {{ $company->settlement_days }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('insurance.Active Policies') }}</span>
                        <strong>{{ $company->policies->where('status', 'active')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('insurance.Total Claims') }}</span>
                        <strong>{{ $company->claims->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('insurance.Approved Claims') }}</span>
                        <strong class="text-success">{{ $company->claims->where('status', 'approved')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('insurance.Pending Claims') }}</span>
                        <strong class="text-warning">{{ $company->claims->where('status', 'pending')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('insurance.Rejected Claims') }}</span>
                        <strong class="text-danger">{{ $company->claims->where('status', 'rejected')->count() }}</strong>
                    </div>
                </div>
            </div>

            @if($company->notes)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('common.Notes') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $company->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Recent Claims --}}
    @if($company->claims->count())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('insurance.Recent Claims') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('common.Claim Number') }}</th>
                            <th>{{ __('common.Amount') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Date') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($company->claims->take(10) as $claim)
                        <tr>
                            <td>{{ $claim->claim_number }}</td>
                            <td>{{ number_format($claim->total_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $claim->status === 'approved' ? 'success' : ($claim->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($claim->status) }}
                                </span>
                            </td>
                            <td>{{ $claim->service_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.insurance.claims.show', $claim) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
