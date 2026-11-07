@extends('layouts.master')

@section('title')
    {{ __('Insurance Policies') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Insurance Policies') }}</h4>
                <a href="{{ route('admin.insurance.policies.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add policy') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Policy #') }}</th><th>{{ __('Holder') }}</th><th>{{ __('Company') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Limit') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($policies as $policy)
                            <tr>
                                <td>{{ $policy->policy_number }}</td>
                                <td>{{ $policy->holder_name }}</td>
                                <td>{{ $policy->company?->name ?? $policy->insurance_company_id }}</td>
                                <td>{{ $policy->plan_type }}</td>
                                <td>{{ $policy->remaining_limit }} / {{ $policy->annual_limit }}</td>
                                <td><span class="badge bg-secondary">{{ $policy->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.insurance.policies.show', $policy) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.insurance.policies.edit', $policy) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>

@endsection
