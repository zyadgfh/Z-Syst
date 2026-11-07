@extends('layouts.master')

@section('title')
    {{ __('Insurance Claims') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Insurance Claims') }}</h4>
                <a href="{{ route('admin.insurance.claims.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add claim') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Claim #') }}</th><th>{{ __('Service date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Covered') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($claims as $claim)
                            <tr>
                                <td>{{ $claim->claim_number }}</td>
                                <td>{{ $claim->service_date }}</td>
                                <td>{{ $claim->total_amount }}</td>
                                <td>{{ $claim->covered_amount }}</td>
                                <td><span class="badge bg-secondary">{{ $claim->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.insurance.claims.show', $claim) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.insurance.claims.edit', $claim) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
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
