@extends('layouts.master')

@section('title')
    {{ __('Subscriptions') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Subscriptions') }}</h4>
                <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New subscription') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Business') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Status') }}</th><th>{{ __('Starts') }}</th><th>{{ __('Ends') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->business?->companyName ?? $subscription->business_id }}</td>
                                <td>{{ $subscription->plan?->name ?? $subscription->plan_id }}</td>
                                <td><span class="badge bg-secondary">{{ $subscription->status }}</span></td>
                                <td>{{ $subscription->starts_at?->format('Y-m-d') }}</td>
                                <td>{{ $subscription->ends_at?->format('Y-m-d') }}</td>
                                <td><a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-sm btn-light">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>

@endsection
