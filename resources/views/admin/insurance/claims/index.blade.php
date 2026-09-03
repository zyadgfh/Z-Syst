@extends('layouts.admin')

@section('title', __('insurance.Insurance Claims'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Insurance Claims') }}</h1>
        <a href="{{ route('admin.insurance.claims.create') }}" class="btn btn-primary-blue">
            <i class="fas fa-plus me-1"></i> {{ __('common.New Claim') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.Claim Number') }}</th>
                            <th>{{ __('common.Policy') }}</th>
                            <th>{{ __('common.Amount') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Date') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $claim)
                            <tr>
                                <td>{{ $claim->id }}</td>
                                <td>{{ $claim->claim_number ?? '—' }}</td>
                                <td>{{ $claim->policy->policy_number ?? '—' }}</td>
                                <td>{{ number_format($claim->amount ?? 0, 2) }}</td>
                                <td>
                                    @if($claim->status === 'approved')
                                        <span class="badge bg-success">{{ __('common.Approved') }}</span>
                                    @elseif($claim->status === 'pending')
                                        <span class="badge bg-warning text-dark">{{ __('common.Pending') }}</span>
                                    @elseif($claim->status === 'rejected')
                                        <span class="badge bg-danger">{{ __('common.Rejected') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($claim->status ?? 'unknown') }}</span>
                                    @endif
                                </td>
                                <td>{{ $claim->created_at?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.insurance.claims.show', $claim) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.insurance.claims.edit', $claim) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">{{ __('common.No data available') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($claims->hasPages())
            <div class="card-footer">{{ $claims->links() }}</div>
        @endif
    </div>
</div>
@endsection
