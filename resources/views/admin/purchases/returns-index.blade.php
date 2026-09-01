@extends('layouts.master')

@section('title')
    {{ __('Purchase Returns') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('Purchase Returns') }}</h4>
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Purchases') }}
                    </a>
                </div>

                <div class="table-responsive p-16">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Return #') }}</th>
                                <th>{{ __('Purchase Invoice') }}</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Credit Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($returns as $return)
                            <tr>
                                <td><strong>{{ $return->invoice_no }}</strong></td>
                                <td>
                                    <a href="{{ route('admin.purchases.show', $return->purchase_id) }}">
                                        {{ $return->purchase->invoiceNumber ?? '—' }}
                                    </a>
                                </td>
                                <td>{{ $return->purchase->party->name ?? '—' }}</td>
                                <td>{{ $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') : '—' }}</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($return->credit_amount ?? $return->total_amount ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ ($return->status ?? 'completed') === 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($return->status ?? 'completed') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.purchases.returns.show', $return->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-undo fa-2x mb-2 opacity-25"></i>
                                    <p>{{ __('No purchase returns found.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($returns->hasPages())
                <div class="p-16 border-top">
                    {{ $returns->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
