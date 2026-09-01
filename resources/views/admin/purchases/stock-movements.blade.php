@extends('layouts.master')

@section('title')
    {{ __('Stock Movement Report') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>{{ __('Stock Movement Report') }}</h4>
                    <a href="{{ route('admin.purchases.reports') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
                    </a>
                </div>

                {{-- Filters --}}
                <div class="p-16 border-top">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('From Date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('To Date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>{{ __('Filter') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Summary --}}
                <div class="p-16">
                    <div class="row g-3 mb-4">
                        @forelse($summary as $item)
                        <div class="col-md-3">
                            <div class="card border-start border-{{ match($item->movement_type) { 'in' => 'success', 'out' => 'danger', 'adjustment' => 'warning', default => 'secondary' } }} border-4">
                                <div class="card-body">
                                    <div class="text-muted">{{ ucfirst($item->movement_type) }}</div>
                                    <h4 class="fw-bold mb-0">{{ $item->count }} <small class="text-muted">({{ $item->total_qty }} {{ __('units') }})</small></h4>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center text-muted py-3">{{ __('No movement data for this period.') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Movements Table --}}
                <div class="table-responsive p-16">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-center">{{ __('Qty') }}</th>
                                <th class="text-end">{{ __('Before') }}</th>
                                <th class="text-end">{{ __('After') }}</th>
                                <th>{{ __('Batch') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                            <tr>
                                <td><small>{{ $movement->created_at->format('d/m/Y H:i') }}</small></td>
                                <td>{{ $movement->product->productName ?? '—' }}</td>
                                <td>
                                    @php
                                        $typeColors = ['in' => 'success', 'out' => 'danger', 'adjustment' => 'warning'];
                                    @endphp
                                    <span class="badge bg-{{ $typeColors[$movement->movement_type] ?? 'secondary' }}">
                                        {{ ucfirst($movement->movement_type) }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold">{{ $movement->quantity }}</td>
                                <td class="text-end">{{ $movement->before_quantity }}</td>
                                <td class="text-end fw-bold">{{ $movement->after_quantity }}</td>
                                <td><code>{{ $movement->batch_no ?? '—' }}</code></td>
                                <td>{{ $movement->user->name ?? '—' }}</td>
                                <td><small class="text-muted">{{ Str::limit($movement->notes, 50) }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    {{ __('No stock movements found.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                <div class="p-16 border-top">
                    {{ $movements->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
