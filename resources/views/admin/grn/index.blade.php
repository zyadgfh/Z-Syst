@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Goods Received Notes (GRN)</h2>
                <a href="{{ route('admin.grn.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create GRN
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
                                <label>Supplier</label>
                                <select class="form-control" id="filter-supplier">
                                    <option value="">All Suppliers</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="filter-status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="verified">Verified</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="partially_accepted">Partially Accepted</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Purchase Order</label>
                                <select class="form-control" id="filter-po">
                                    <option value="">All POs</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" class="form-control" id="search" placeholder="Search GRN number...">
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
                                    <th>GRN Number</th>
                                    <th>Supplier</th>
                                    <th>Purchase Order</th>
                                    <th>Status</th>
                                    <th>Received Date</th>
                                    <th>Total Received</th>
                                    <th>Total Accepted</th>
                                    <th>Total Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="grn-table">
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
        loadGRNs();

        $('#filter-supplier, #filter-status, #filter-po, #search').on('change', loadGRNs);
    });

    function loadGRNs() {
        const supplier = $('#filter-supplier').val();
        const status = $('#filter-status').val();
        const po = $('#filter-po').val();
        const search = $('#search').val();

        $.get('{{ route('admin.grn.index') }}', {
            supplier_id: supplier,
            status: status,
            purchase_order_id: po,
            search: search
        }, function(data) {
            let html = '';
            data.data.forEach(function(grn) {
                html += `
                    <tr>
                        <td><strong>${grn.grn_number}</strong></td>
                        <td>${grn.supplier ? grn.supplier.name : 'N/A'}</td>
                        <td>${grn.purchase_order ? grn.purchase_order.po_number : 'N/A'}</td>
                        <td><span class="badge badge-${getStatusClass(grn.status)}">${grn.status}</span></td>
                        <td>${formatDate(grn.received_date)}</td>
                        <td>${grn.total_received_quantity}</td>
                        <td>${grn.total_accepted_quantity}</td>
                        <td>${formatCurrency(grn.total_value)}</td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick="viewGRN(${grn.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${getActionButtons(grn)}
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#grn-table').html(html);
        });
    }

    function getStatusClass(status) {
        const classes = {
            'pending': 'secondary',
            'verified': 'info',
            'accepted': 'success',
            'partially_accepted': 'warning',
            'rejected': 'danger'
        };
        return classes[status] || 'secondary';
    }

    function getActionButtons(grn) {
        let buttons = '';

        if (grn.status === 'pending') {
            buttons += `
                <button class="btn btn-sm btn-success" onclick="verifyGRN(${grn.id})">
                    <i class="fas fa-check"></i>
                </button>
            `;
        }

        if (grn.status === 'verified') {
            buttons += `
                <button class="btn btn-sm btn-success" onclick="acceptGRN(${grn.id})">
                    <i class="fas fa-check-double"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="rejectGRN(${grn.id})">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }

        return buttons;
    }

    function verifyGRN(id) {
        if (confirm('Verify this GRN and update stock?')) {
            $.post(`{{ route('admin.grn.verify') }}`.replace('{grn}', id), function(data) {
                alert('GRN verified successfully');
                loadGRNs();
            });
        }
    }

    function acceptGRN(id) {
        if (confirm('Accept this GRN?')) {
            $.post(`{{ route('admin.grn.accept') }}`.replace('{grn}', id), function(data) {
                alert('GRN accepted successfully');
                loadGRNs();
            });
        }
    }

    function rejectGRN(id) {
        if (confirm('Reject this GRN and rollback stock?')) {
            $.post(`{{ route('admin.grn.reject') }}`.replace('{grn}', id), function(data) {
                alert('GRN rejected successfully');
                loadGRNs();
            });
        }
    }

    function viewGRN(id) {
        window.location.href = `{{ route('admin.grn.show') }}`.replace('{grn}', id);
    }
</script>
@endpush
