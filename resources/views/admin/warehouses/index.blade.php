@extends('layouts.admin')

@section('title')
    {{ __('warehouses.Warehouses') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('warehouses.Warehouses') }}</h4>
                        @can('warehouses-create')
                            <a type="button" href="{{ route('admin.warehouses.create') }}" class="add-order-btn rounded-2 active">
                                <i class="fas fa-plus-circle me-1"></i> {{ __('warehouses.Add Warehouse') }}
                            </a>
                        @endcan
                    </div>

                    <div class="table-top-form sec-header d-print-none">
                        <div class="d-flex gap-3">
                            <div class="table-search position-relative">
                                <input class="form-control searchInput" type="text" id="warehouse-search" placeholder="{{ __('warehouses.Search warehouses...') }}">
                                <span class="position-absolute">
                                    <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="responsive-table table-container mt-0">
                        <table class="table" id="warehouse-table">
                            <thead>
                                <tr>
                                    <th class="table-header-content">{{ __('common.SL') }}.</th>
                                    <th class="table-header-content">{{ __('common.Name') }}</th>
                                    <th class="table-header-content">{{ __('common.Code') }}</th>
                                    <th class="table-header-content">{{ __('warehouses.Location') }}</th>
                                    <th class="table-header-content">{{ __('common.Type') }}</th>
                                    <th class="table-header-content">{{ __('common.Status') }}</th>
                                    <th class="table-header-content">{{ __('common.Default') }}</th>
                                    <th class="table-header-content d-print-none">{{ __('common.Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="warehouse-data">
                                @forelse($warehouses as $warehouse)
                                    <tr class="table-content">
                                        <td class="table-single-content">{{ $loop->iteration }}</td>
                                        <td class="table-single-content">{{ $warehouse->name }}</td>
                                        <td class="table-single-content">{{ $warehouse->code }}</td>
                                        <td class="table-single-content">{{ $warehouse->location ?? __('warehouses.N/A') }}</td>
                                        <td class="table-single-content">{{ $warehouse->type ?? __('products.Standard') }}</td>
                                        <td class="table-single-content">
                                            @if ($warehouse->is_active)
                                                <span class="badge bg-success">{{ __('common.Active') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('common.Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="table-single-content">
                                            @if ($warehouse->is_default)
                                                <span class="badge bg-soft-primary">{{ __('common.Yes') }}</span>
                                            @else
                                                @if ($warehouse->is_active)
                                                    <button class="btn btn-sm btn-outline-primary set-default-btn" data-id="{{ $warehouse->id }}">
                                                        {{ __('warehouses.Set Default') }}
                                                    </button>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="table-single-content d-print-none">
                                            <div class="d-flex gap-2">
                                                @can('warehouses-update')
                                                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('warehouses-delete')
                                                    <button class="btn btn-sm btn-outline-danger delete-warehouse-btn" data-id="{{ $warehouse->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">{{ __('warehouses.No warehouses found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $warehouses->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('warehouse-search');
            const tableBody = document.getElementById('warehouse-data');
            const rows = tableBody.querySelectorAll('tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(searchTerm) ? '' : 'none';
                });
            });

            document.querySelectorAll('.delete-warehouse-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (confirm('{{ __('warehouses.Are you sure you want to delete this warehouse?') }}')) {
                        fetch(`/admin/warehouses/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        }).then(() => location.reload());
                    }
                });
            });

            document.querySelectorAll('.set-default-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    fetch(`/admin/warehouses/${id}/set-default`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    }).then(() => location.reload());
                });
            });
        });
    </script>
@endpush
