@extends('layouts.master')

@section('title')
    {{ __('Recall Events') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-top-form">
                        <div class="table-search">
                            <span><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('Search recalls...') }}" id="recall-search">
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="status-filter">
                                <option value="all">{{ __('All Status') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="resolved">{{ __('Resolved') }}</option>
                            </select>
                            @can('recalls-create')
                                <a href="{{ route('admin.traceability.initiate-recall') }}" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>{{ __('Initiate Recall') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover" id="recalls-table">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Product') }}</th>
                                        <th class="table-header-content">{{ __('Batch/Lot #') }}</th>
                                        <th class="table-header-content">{{ __('Reason') }}</th>
                                        <th class="table-header-content">{{ __('Initiated By') }}</th>
                                        <th class="table-header-content">{{ __('Date') }}</th>
                                        <th class="table-header-content">{{ __('Status') }}</th>
                                        <th class="table-header-content">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="recalls-data">
                                    @forelse($recalls as $recall)
                                        <tr class="table-content" data-status="{{ $recall->status }}">
                                            <td class="table-single-content">{{ $loop->iteration }}</td>
                                            <td class="table-single-content">
                                                <div>
                                                    <strong>{{ $recall->product->name ?? __('Unknown') }}</strong>
                                                    <small class="d-block text-muted">{{ $recall->product->sku ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td class="table-single-content">{{ $recall->batch_lot_number }}</td>
                                            <td class="table-single-content">{{ Str::limit($recall->reason, 50) }}</td>
                                            <td class="table-single-content">{{ $recall->user->name ?? __('Unknown') }}</td>
                                            <td class="table-single-content">{{ formatted_date($recall->created_at) }}</td>
                                            <td class="table-single-content">
                                                @if ($recall->status === 'active')
                                                    <span class="badge expired">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge-soft-success">{{ __('Resolved') }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                @if ($recall->status === 'active')
                                                    <button class="btn btn-sm btn-success resolve-recall-btn" data-id="{{ $recall->id }}">
                                                        <i class="fas fa-check"></i> {{ __('Resolve') }}
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-info" onclick="viewRecallDetails({{ $recall->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">{{ __('No recall events found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($recalls->hasPages())
                            <div class="pagination">
                                {{ $recalls->appends(request()->query())->links() }}
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
            const searchInput = document.getElementById('recall-search');
            const filterSelect = document.getElementById('status-filter');
            const rows = document.querySelectorAll('#recalls-data tr');

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

            document.querySelectorAll('.resolve-recall-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (confirm('{{ __("Are you sure you want to resolve this recall?") }}')) {
                        resolveRecall(id);
                    }
                });
            });
        });

        function resolveRecall(id) {
            fetch(`/api/v1/traceability/recalls/${id}/resolve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    toastr.error(data.message);
                }
            })
            .catch(error => {
                toastr.error('{{ __("An error occurred") }}');
            });
        }

        function viewRecallDetails(id) {
            // Implement view details functionality
            window.location.href = `/admin/traceability/recalls/${id}`;
        }
    </script>
@endpush
