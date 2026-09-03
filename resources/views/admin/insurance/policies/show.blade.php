@extends('layouts.admin')

@section('title', __('insurance.Policy') . ' - ' . $policy->policy_number)

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Policy') }}: {{ $policy->policy_number }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.insurance.policies.edit', $policy) }}" class="btn btn-primary-blue">
                <i class="fas fa-edit me-1"></i> {{ __('common.Edit') }}
            </a>
            <a href="{{ route('admin.insurance.policies.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Policy Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Policy Number') }}:</strong> <code>{{ $policy->policy_number }}</code>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Insurance Company') }}:</strong> {{ $policy->company->name ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Holder Name') }}:</strong> {{ $policy->holder_name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Member ID') }}:</strong> {{ $policy->member_id ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Plan Type') }}:</strong> {{ ucfirst($policy->plan_type) }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Status') }}:</strong>
                            <span class="badge bg-{{ $policy->status === 'active' ? 'success' : ($policy->status === 'expired' ? 'secondary' : 'warning') }}">
                                {{ ucfirst($policy->status) }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Start Date') }}:</strong> {{ $policy->start_date?->format('d M Y') ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.End Date') }}:</strong> {{ $policy->end_date?->format('d M Y') ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Phone') }}:</strong> {{ $policy->holder_phone ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Email') }}:</strong> {{ $policy->holder_email ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Coverage Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ __('common.Annual Limit') }}</span>
                            <strong>{{ number_format($policy->annual_limit ?? 0, 2) }}</strong>
                        </div>
                        @if($policy->annual_limit)
                        <div class="progress h-8">
                            <div class="progress-bar bg-{{ ($policy->used_amount / max($policy->annual_limit, 1)) * 100 > 80 ? 'danger' : 'success' }}"
                                 style="width: {{ min(($policy->used_amount / max($policy->annual_limit, 1)) * 100, 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Used Amount') }}</span>
                        <strong>{{ number_format($policy->used_amount ?? 0, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Remaining') }}</span>
                        <strong class="text-success">{{ number_format($policy->remaining_limit ?? 0, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Coverage') }}</span>
                        <strong>{{ $policy->coverage_percent }}%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Copay') }}</span>
                        <strong>{{ $policy->copay_percent }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Related Claims --}}
    @if(isset($policy->claims) && $policy->claims->count())
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('insurance.Related Claims') }}</h5>
            <a href="{{ route('admin.insurance.claims.index') }}?policy_id={{ $policy->id }}" class="btn btn-sm btn-outline-primary">{{ __('common.View All') }}</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('common.Claim Number') }}</th>
                            <th>{{ __('common.Amount') }}</th>
                            <th>{{ __('common.Covered') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policy->claims->take(10) as $claim)
                        <tr>
                            <td>
                                <a href="{{ route('admin.insurance.claims.show', $claim) }}">{{ $claim->claim_number }}</a>
                            </td>
                            <td>{{ number_format($claim->total_amount, 2) }}</td>
                            <td>{{ number_format($claim->covered_amount ?? 0, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $claim->status === 'approved' ? 'success' : ($claim->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($claim->status) }}
                                </span>
                            </td>
                            <td>{{ $claim->service_date?->format('d M Y') ?? '—' }}</td>
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
