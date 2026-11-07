@extends('layouts.master')

@section('title')
    {{ __('Insurance Companies') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{ __('Insurance Companies') }</h4>
                <a href="{{ route('admin.insurance.companies.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{ __('Add company') }</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{ __('Name') }</th><th>{ __('Code') }</th><th>{ __('Contact') }</th><th>{ __('Phone') }</th><th>{ __('Coverage') }</th><th>{ __('Status') }</th><th></th></tr></thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->code }}</td>
                                <td>{{ $company->contact_person }}</td>
                                <td>{{ $company->phone }}</td>
                                <td>{{ $company->default_coverage_percent }}%</td>
                                <td><span class="badge bg-secondary">{{ $company->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.insurance.companies.show', $company) }}" class="btn btn-sm btn-light">{ __('View') }</a>
                                    <a href="{{ route('admin.insurance.companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">{ __('Edit') }</a>
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
