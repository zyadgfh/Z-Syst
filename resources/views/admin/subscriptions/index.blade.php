@extends('layouts.admin')

@section('title', __('subscriptions.Subscriptions'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('subscriptions.Subscriptions') }}</h1>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.Business') }}</th>
                            <th>{{ __('common.Plan') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Start Date') }}</th>
                            <th>{{ __('common.End Date') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->id }}</td>
                                <td>{{ $subscription->business->companyName ?? '—' }}</td>
                                <td>{{ $subscription->plan->subscriptionName ?? '—' }}</td>
                                <td>
                                    @if($subscription->status === 'active')
                                        <span class="badge bg-success">{{ __('common.Active') }}</span>
                                    @elseif($subscription->status === 'expired')
                                        <span class="badge bg-danger">{{ __('common.Expired') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($subscription->status ?? 'unknown') }}</span>
                                    @endif
                                </td>
                                <td>{{ $subscription->start_date ?? '—' }}</td>
                                <td>{{ $subscription->end_date ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    {{ __('common.No subscriptions found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($subscriptions->hasPages())
            <div class="card-footer">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
