@extends('layouts.master')

@section('title')
    {{ __('Receipts') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-top-form">
                        <div class="table-search">
                            <span><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('Search receipts...') }}" id="receipt-search">
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="type-filter">
                                <option value="all">{{ __('All Types') }}</option>
                                <option value="sale">{{ __('Sales') }}</option>
                                <option value="purchase">{{ __('Purchases') }}</option>
                            </select>
                            <a href="{{ route('admin.receipts.settings') }}" class="btn btn-secondary">
                                <i class="fas fa-cog me-2"></i>{{ __('Receipt Settings') }}
                            </a>
                        </div>
                    </div>

                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Receipt #') }}</th>
                                        <th class="table-header-content">{{ __('Type') }}</th>
                                        <th class="table-header-content">{{ __('Customer/Supplier') }}</th>
                                        <th class="table-header-content">{{ __('Total') }}</th>
                                        <th class="table-header-content">{{ __('Date') }}</th>
                                        <th class="table-header-content">{{ __('Status') }}</th>
                                        <th class="table-header-content">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="receipts-data">
                                    @forelse($receipts as $receipt)
                                        <tr class="table-content" data-type="{{ $receipt->receiptable_type === 'sale' ? 'sale' : 'purchase' }}">
                                            <td class="table-single-content">{{ $loop->iteration }}</td>
                                            <td class="table-single-content">
                                                <div>
                                                    <strong>{{ $receipt->receipt_number }}</strong>
                                                    <small class="d-block text-muted">{{ $receipt->invoice_number ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td class="table-single-content">
                                                @if ($receipt->receiptable_type === 'sale')
                                                    <span class="badge-soft-info">{{ __('Sale') }}</span>
                                                @else
                                                    <span class="badge-soft-success">{{ __('Purchase') }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">{{ $receipt->party->name ?? __('Unknown') }}</td>
                                            <td class="table-single-content">{{ format_currency($receipt->total_amount) }}</td>
                                            <td class="table-single-content">{{ formatted_date($receipt->created_at) }}</td>
                                            <td class="table-single-content">
                                                @if ($receipt->status === 'paid')
                                                    <span class="badge-soft-success">{{ __('Paid') }}</span>
                                                @else
                                                    <span class="badge-soft-warning">{{ ucfirst($receipt->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.receipts.show', $receipt) }}" class="btn btn-sm btn-info" target="_blank" title="{{ __('View') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.receipts.show', $receipt) }}?download=1" class="btn btn-sm btn-success" title="{{ __('Download') }}">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">{{ __('No receipts found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($receipts->hasPages())
                            <div class="pagination">
                                {{ $receipts->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('receipt-search');
            const filterSelect = document.getElementById('type-filter');
            const rows = document.querySelectorAll('#receipts-data tr');

            function filterRows() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterType = filterSelect.value;

                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const rowType = row.dataset.type;
                    const matchesSearch = rowText.includes(searchTerm);
                    const matchesFilter = filterType === 'all' || rowType === filterType;
                    row.style.display = matchesSearch && matchesFilter ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterRows);
            filterSelect.addEventListener('change', filterRows);
        });
    </script>
@endpush
