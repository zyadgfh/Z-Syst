@extends('layouts.master')

@section('title')
    {{ __('Batch Lots') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Batch Lots') }}</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="expiry-filter">
                                <option value="all">{{ __('All Status') }}</option>
                                <option value="expired">{{ __('Expired') }}</option>
                                <option value="expiring">{{ __('Expiring Soon') }}</option>
                                <option value="recalled">{{ __('Recalled') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-top-form sec-header d-print-none">
                        <div class="d-flex gap-3">
                            <div class="table-search position-relative">
                                <input class="form-control" type="text" id="batch-search" placeholder="{{ __('Search batches...') }}">
                                <span class="position-absolute">
                                    <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="responsive-table table-container mt-0">
                        <table class="table" id="batch-lots-table">
                            <thead>
                                <tr>
                                    <th class="table-header-content">{{ __('SL') }}.</th>
                                    <th class="table-header-content">{{ __('Product') }}</th>
                                    <th class="table-header-content">{{ __('Batch/Lot #') }}</th>
                                    <th class="table-header-content">{{ __('Qty') }}</th>
                                    <th class="table-header-content">{{ __('Manufactured') }}</th>
                                    <th class="table-header-content">{{ __('Expiry Date') }}</th>
                                    <th class="table-header-content">{{ __('Status') }}</th>
                                    <th class="table-header-content d-print-none">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="batch-lots-data">
                                @forelse($batchLots as $batch)
                                    <tr class="table-content" data-status="{{ $batch->status }}">
                                        <td class="table-single-content">{{ $loop->iteration }}</td>
                                        <td class="table-single-content">{{ $batch->product->name ?? __('Unknown') }}</td>
                                        <td class="table-single-content">{{ $batch->lot_number }}</td>
                                        <td class="table-single-content">{{ $batch->qty }}</td>
                                        <td class="table-single-content">{{ $batch->manufactured_date ? \Carbon\Carbon::parse($batch->manufactured_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="table-single-content">{{ $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="table-single-content">
                                            @if ($batch->status === 'recalled')
                                                <span class="badge bg-danger">{{ __('Recalled') }}</span>
                                            @elseif ($batch->is_expired)
                                                <span class="badge bg-warning text-dark">{{ __('Expired') }}</span>
                                            @elseif ($batch->days_to_expiry <= 30 && $batch->days_to_expiry > 0)
                                                <span class="badge bg-info">{{ __('Expiring Soon') }}</span>
                                            @else
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @endif
                                        </td>
                                        <td class="table-single-content d-print-none">
                                            @if ($batch->status !== 'recalled' && $batch->status !== 'expired')
                                                <button class="btn btn-sm btn-outline-warning initiate-recall-btn" data-id="{{ $batch->id }}">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Initiate Recall') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">{{ __('No batch lots found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $batchLots->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('batch-search');
            const filterSelect = document.getElementById('expiry-filter');
            const rows = document.querySelectorAll('#batch-lots-data tr');

            function filterRows() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterStatus = filterSelect.value;

                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const rowStatus = row.dataset.status;
                    const matchesSearch = rowText.includes(searchTerm);
                    const matchesFilter = filterStatus === 'all' || rowStatus === filterStatus;
                    row.style.display = matchesSearch && matchesFilter ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterRows);
            filterSelect.addEventListener('change', filterRows);

            document.querySelectorAll('.initiate-recall-btn').forEach(button => {
                button.addEventListener('click', function() {
                    window.location.href = '{{ route('admin.traceability.initiate-recall') }}?batch_id=' + this.dataset.id;
                });
            });
        });
    </script>
@endpush
