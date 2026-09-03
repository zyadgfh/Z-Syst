@extends('layouts.admin')

@section('title', __('insurance.Insurance Companies'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Insurance Companies') }}</h1>
        <a href="{{ route('admin.insurance.companies.create') }}" class="btn btn-primary-blue">
            <i class="fas fa-plus me-1"></i> {{ __('common.Add Company') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.Name') }}</th>
                            <th>{{ __('common.Email') }}</th>
                            <th>{{ __('common.Phone') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td>{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->email ?? '—' }}</td>
                                <td>{{ $company->phone ?? '—' }}</td>
                                <td>
                                    @if($company->status)
                                        <span class="badge bg-success">{{ __('common.Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('common.Inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.insurance.companies.show', $company) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    @can('insurance-companies-update')
                                    <a href="{{ route('admin.insurance.companies.edit', $company) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
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
        @if($companies->hasPages())
            <div class="card-footer">{{ $companies->links() }}</div>
        @endif
    </div>
</div>
@endsection
