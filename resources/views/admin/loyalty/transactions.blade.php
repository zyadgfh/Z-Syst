@extends('layouts.master')

@section('title')
    {{ __('Loyalty Transactions') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Loyalty Transactions') }}</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="type-filter">
                                <option value="all">{{ __('All Types') }}</option>
                                <option value="earned">{{ __('Earned') }}</option>
                                <option value="redeemed">{{ __('Redeemed') }}</option>
                            </select>
                            <div class="table-search position-relative">
                                <input class="form-control" type="text" id="transaction-search" placeholder="{{ __('Search...') }}">
                                <span class="position-absolute">
                                    <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="responsive-table table-container mt-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="table-header-content">{{ __('SL') }}.</th>
                                    <th class="table-header-content">{{ __('Date') }}</th>
                                    <th class="table-header-content">{{ __('Customer') }}</th>
                                    <th class="table-header-content">{{ __('Type') }}</th>
                                    <th class="table-header-content">{{ __('Points') }}</th>
                                    <th class="table-header-content">{{ __('Balance') }}</th>
                                    <th class="table-header-content">{{ __('Reference') }}</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-data">
                                @forelse($transactions as $transaction)
                                    <tr class="table-content" data-type="{{ $transaction->type }}">
                                        <td class="table-single-content">{{ $loop->iteration }}</td>
                                        <td class="table-single-content">{{ \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="table-single-content">{{ $transaction->party->name ?? __('Unknown') }}</td>
                                        <td class="table-single-content">
                                            @if ($transaction->type === 'earned')
                                                <span class="badge bg-soft-success">{{ __('Earned') }}</span>
                                            @else
                                                <span class="badge bg-soft-info">{{ __('Redeemed') }}</span>
                                            @endif
                                        </td>
                                        <td class="table-single-content">
                                            @if ($transaction->type === 'earned')
                                                <span class="text-success">+{{ $transaction->points }}</span>
                                            @else
                                                <span class="text-danger">-{{ $transaction->points }}</span>
                                            @endif
                                        </td>
                                        <td class="table-single-content">{{ $transaction->balance_after }}</td>
                                        <td class="table-single-content">{{ $transaction->reference ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">{{ __('No transactions found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $transactions->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('transaction-search');
            const filterSelect = document.getElementById('type-filter');
            const rows = document.querySelectorAll('#transactions-data tr');

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
