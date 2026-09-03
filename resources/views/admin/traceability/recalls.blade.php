@extends('layouts.admin')

@section('title')
    {{ __('audit.Recall Events') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-top-form">
                        <div class="table-search">
                            <span><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('audit.Search recalls...') }}" id="recall-search">
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="status-filter">
                                <option value="all">{{ __('common.All Status') }}</option>
                                <option value="active">{{ __('common.Active') }}</option>
                                <option value="resolved">{{ __('audit.Resolved') }}</option>
                            </select>
                            @can('recalls-create')
                                <a href="{{ route('admin.traceability.initiate-recall') }}" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>{{ __('audit.Initiate Recall') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover" id="recalls-table">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('common.SL') }}.</th>
                                        <th class="table-header-content">{{ __('common.Product') }}</th>
                                        <th class="table-header-content">{{ __('audit.Batch/Lot #') }}</th>
                                        <th class="table-header-content">{{ __('common.Reason') }}</th>
                                        <th class="table-header-content">{{ __('audit.Initiated By') }}</th>
                                        <th class="table-header-content">{{ __('common.Date') }}</th>
                                        <th class="table-header-content">{{ __('common.Status') }}</th>
                                        <th class="table-header-content">{{ __('common.Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="recalls-data">
                                    @forelse($recalls as $recall)
                                        <tr class="table-content" data-status="{{ $recall->status }}">
                                            <td class="table-single-content">{{ $loop->iteration }}</td>
                                            <td class="table-single-content">
                                                <div>
                                                    <strong>{{ $recall->product->name ?? __('common.Unknown') }}</strong>
                                                    <small class="d-block text-muted">{{ $recall->product->sku ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td class="table-single-content">{{ $recall->batch_lot_number }}</td>
                                            <td class="table-single-content">{{ Str::limit($recall->reason, 50) }}</td>
                                            <td class="table-single-content">{{ $recall->user->name ?? __('common.Unknown') }}</td>
                                            <td class="table-single-content">{{ formatted_date($recall->created_at) }}</td>
                                            <td class="table-single-content">
                                                @if ($recall->status === 'active')
                                                    <span class="badge expired">{{ __('common.Active') }}</span>
                                                @else
                                                    <span class="badge-soft-success">{{ __('audit.Resolved') }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                @if ($recall->status === 'active')
                                                    <button class="btn btn-sm btn-success resolve-recall-btn" data-id="{{ $recall->id }}">
                                                        <i class="fas fa-check"></i> {{ __('audit.Resolve') }}
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
                                            <td colspan="8" class="text-center py-4">{{ __('audit.No recall events found') }}</td>
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
                    if (confirm('{{ __('audit.Are you sure you want to resolve this recall?') }}')) {
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
                toastr.error('{{ __('common.An error occurred') }}');
            });
        }

        function viewRecallDetails(id) {
            // Implement view details functionality
            window.location.href = `/admin/traceability/recalls/${id}`;
        }
    </script>
@endpush
