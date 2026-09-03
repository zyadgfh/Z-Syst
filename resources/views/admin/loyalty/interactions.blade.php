@extends('layouts.admin')

@section('title')
    {{ __('loyalty.Customer Interactions') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('loyalty.Customer Interactions') }}</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="type-filter">
                                <option value="all">{{ __('common.All Types') }}</option>
                                <option value="sale">{{ __('gateways.Sale') }}</option>
                                <option value="inquiry">{{ __('loyalty.Inquiry') }}</option>
                                <option value="support">{{ __('loyalty.Support') }}</option>
                                <option value="feedback">{{ __('loyalty.Feedback') }}</option>
                            </select>
                            <div class="table-search position-relative">
                                <input class="form-control" type="text" id="interaction-search" placeholder="{{ __('common.Search...') }}">
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
                                    <th class="table-header-content">{{ __('common.SL') }}.</th>
                                    <th class="table-header-content">{{ __('common.Date') }}</th>
                                    <th class="table-header-content">{{ __('common.Customer') }}</th>
                                    <th class="table-header-content">{{ __('common.Type') }}</th>
                                    <th class="table-header-content">{{ __('common.Notes') }}</th>
                                    <th class="table-header-content">{{ __('loyalty.Staff') }}</th>
                                </tr>
                            </thead>
                            <tbody id="interactions-data">
                                @forelse($interactions as $interaction)
                                    <tr class="table-content" data-type="{{ $interaction->interaction_type }}">
                                        <td class="table-single-content">{{ $loop->iteration }}</td>
                                        <td class="table-single-content">{{ \Carbon\Carbon::parse($interaction->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="table-single-content">{{ $interaction->party->name ?? __('common.Unknown') }}</td>
                                        <td class="table-single-content">
                                            <span class="badge bg-soft-primary">{{ ucfirst(str_replace('_', ' ', $interaction->interaction_type)) }}</span>
                                        </td>
                                        <td class="table-single-content">{{ Str::limit($interaction->notes, 60) }}</td>
                                        <td class="table-single-content">{{ $interaction->user->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">{{ __('loyalty.No interactions found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $interactions->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('interaction-search');
            const filterSelect = document.getElementById('type-filter');
            const rows = document.querySelectorAll('#interactions-data tr');

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
