@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Suppliers</h2>
                <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Supplier
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="filter-status">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" class="form-control" id="search" placeholder="Search name...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Rating</th>
                                    <th>Performance</th>
                                    <th>Orders</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="suppliers-table">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        loadSuppliers();

        $('#filter-status, #search').on('change', loadSuppliers);
    });

    function loadSuppliers() {
        const status = $('#filter-status').val();
        const search = $('#search').val();

        $.get('{{ route('admin.suppliers.index') }}', {
            status: status,
            search: search
        }, function(data) {
            let html = '';
            data.data.forEach(function(supplier) {
                html += `
                    <tr>
                        <td><strong>${supplier.company_name}</strong></td>
                        <td>${supplier.contact_person}</td>
                        <td>${supplier.email}</td>
                        <td>${supplier.phone}</td>
                        <td>
                            <span class="badge badge-warning">${supplier.rating.toFixed(1)}/5</span>
                        </td>
                        <td>
                            <span class="badge badge-info">${supplier.performance_score.toFixed(1)}/100</span>
                        </td>
                        <td>${supplier.purchase_orders_count || 0}</td>
                        <td>
                            <span class="badge badge-${supplier.is_active ? 'success' : 'secondary'}">
                                ${supplier.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick="viewSupplier(${supplier.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="calculatePerformance(${supplier.id})">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#suppliers-table').html(html);
        });
    }

    function viewSupplier(id) {
        window.location.href = `{{ route('admin.suppliers.show') }}`.replace('{supplier}', id);
    }

    function calculatePerformance(id) {
        if (confirm('Calculate performance for this supplier?')) {
            $.post(`{{ route('admin.suppliers.calculate-performance') }}`.replace('{supplier}', id), function(data) {
                alert('Performance calculated successfully');
                loadSuppliers();
            });
        }
    }
</script>
@endpush
