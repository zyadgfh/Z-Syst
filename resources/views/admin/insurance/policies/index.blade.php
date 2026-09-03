@extends('layouts.admin')

@section('title', __('insurance.Insurance Policies'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Insurance Policies') }}</h1>
        <a href="{{ route('admin.insurance.policies.create') }}" class="btn btn-primary-blue">
            <i class="fas fa-plus me-1"></i> {{ __('common.Add Policy') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.Policy Number') }}</th>
                            <th>{{ __('common.Company') }}</th>
                            <th>{{ __('common.Customer') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                            <tr>
                                <td>{{ $policy->id }}</td>
                                <td>{{ $policy->policy_number ?? '—' }}</td>
                                <td>{{ $policy->company->name ?? '—' }}</td>
                                <td>{{ $policy->customer->name ?? '—' }}</td>
                                <td>
                                    @if($policy->status === 'active')
                                        <span class="badge bg-success">{{ __('common.Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($policy->status ?? 'unknown') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.insurance.policies.show', $policy) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    @can('insurance-policies-update')
                                    <a href="{{ route('admin.insurance.policies.edit', $policy) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">{{ __('common.No data available') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($policies->hasPages())
            <div class="card-footer">{{ $policies->links() }}</div>
        @endif
    </div>
</div>
@endsection
