@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __('purchases.Purchase Orders') }}</h2>
                <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('purchases.Create Purchase Order') }}
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
                                <label>{{ __('purchases.Supplier') }}</label>
                                <select class="form-control" id="filter-supplier">
                                    <option value="">{{ __('purchases.All Suppliers') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('common.Status') }}</label>
                                <select class="form-control" id="filter-status">
                                    <option value="">{{ __('common.All Status') }}</option>
                                    <option value="draft">{{ __('purchases.Draft') }}</option>
                                    <option value="sent">{{ __('purchases.Sent') }}</option>
                                    <option value="accepted">{{ __('purchases.Accepted') }}</option>
                                    <option value="partially_received">{{ __('purchases.Partially Received') }}</option>
                                    <option value="received">{{ __('purchases.Received') }}</option>
                                    <option value="cancelled">{{ __('purchases.Cancelled') }}</option>
                                    <option value="rejected">{{ __('purchases.Rejected') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('purchases.Priority') }}</label>
                                <select class="form-control" id="filter-priority">
                                    <option value="">{{ __('purchases.All Priorities') }}</option>
                                    <option value="low">{{ __('purchases.Low') }}</option>
                                    <option value="normal">{{ __('purchases.Normal') }}</option>
                                    <option value="high">{{ __('purchases.High') }}</option>
                                    <option value="urgent">{{ __('purchases.Urgent') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('common.Search') }}</label>
                                <input type="text" class="form-control" id="search" placeholder="Search PO number...">
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
                                    <th>{{ __('purchases.PO Number') }}</th>
                                    <th>{{ __('purchases.Supplier') }}</th>
                                    <th>{{ __('common.Status') }}</th>
                                    <th>{{ __('purchases.Priority') }}</th>
                                    <th>{{ __('purchases.Total Amount') }}</th>
                                    <th>{{ __('purchases.Expected Delivery') }}</th>
                                    <th>{{ __('common.Items') }}</th>
                                    <th>{{ __('purchases.Progress') }}</th>
                                    <th>{{ __('common.Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-orders-table">
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
        loadPurchaseOrders();

        $('#filter-supplier, #filter-status, #filter-priority, #search').on('change', loadPurchaseOrders);
    });

    function loadPurchaseOrders() {
        const supplier = $('#filter-supplier').val();
        const status = $('#filter-status').val();
        const priority = $('#filter-priority').val();
        const search = $('#search').val();

        $.get('{{ route('admin.purchase-orders.index') }}', {
            supplier_id: supplier,
            status: status,
            priority: priority,
            search: search
        }, function(data) {
            let html = '';
            data.data.forEach(function(po) {
                html += `
                    <tr>
                        <td><strong>${po.po_number}</strong></td>
                        <td>${po.supplier ? po.supplier.name : 'N/A'}</td>
                        <td><span class="badge badge-${getStatusClass(po.status)}">${po.status}</span></td>
                        <td><span class="badge badge-${getPriorityClass(po.priority)}">${po.priority}</span></td>
                        <td>${formatCurrency(po.total_amount)}</td>
                        <td>${formatDate(po.expected_delivery_date)}</td>
                        <td>${po.total_quantity}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="width: ${po.completion_percentage}%">
                                    ${po.completion_percentage.toFixed(0)}%
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick="viewPO(${po.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${getActionButtons(po)}
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#purchase-orders-table').html(html);
        });
    }

    function getStatusClass(status) {
        const classes = {
            'draft': 'secondary',
            'sent': 'info',
            'accepted': 'success',
            'partially_received': 'warning',
            'received': 'success',
            'cancelled': 'danger',
            'rejected': 'danger'
        };
        return classes[status] || 'secondary';
    }

    function getPriorityClass(priority) {
        const classes = {
            'low': 'secondary',
            'normal': 'info',
            'high': 'warning',
            'urgent': 'danger'
        };
        return classes[priority] || 'info';
    }

    function getActionButtons(po) {
        let buttons = '';
        
        if (po.status === 'draft') {
            buttons += `
                <button class="btn btn-sm btn-success" onclick="sendPO(${po.id})">
                    <i class="fas fa-paper-plane"></i>
                </button>
            `;
        }
        
        if (po.status === 'sent') {
            buttons += `
                <button class="btn btn-sm btn-success" onclick="approvePO(${po.id})">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="rejectPO(${po.id})">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }
        
        if (po.status === 'accepted' || po.status === 'partially_received') {
            buttons += `
                <button class="btn btn-sm btn-primary" onclick="convertToPurchase(${po.id})">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            `;
        }
        
        if (po.status !== 'received' && po.status !== 'cancelled') {
            buttons += `
                <button class="btn btn-sm btn-warning" onclick="cancelPO(${po.id})">
                    <i class="fas fa-ban"></i>
                </button>
            `;
        }
        
        return buttons;
    }

    function sendPO(id) {
        if (confirm('Send this purchase order to supplier?')) {
            $.post(`{{ route('admin.purchase-orders.send') }}`.replace('{purchaseOrder}', id), function(data) {
                alert('Purchase order sent successfully');
                loadPurchaseOrders();
            });
        }
    }

    function approvePO(id) {
        if (confirm('Approve this purchase order?')) {
            $.post(`{{ route('admin.purchase-orders.approve') }}`.replace('{purchaseOrder}', id), function(data) {
                alert('Purchase order approved successfully');
                loadPurchaseOrders();
            });
        }
    }

    function rejectPO(id) {
        const reason = prompt('Please enter rejection reason:');
        if (reason) {
            $.post(`{{ route('admin.purchase-orders.reject') }}`.replace('{purchaseOrder}', id), { reason: reason }, function(data) {
                alert('Purchase order rejected successfully');
                loadPurchaseOrders();
            });
        }
    }

    function cancelPO(id) {
        if (confirm('Cancel this purchase order?')) {
            $.post(`{{ route('admin.purchase-orders.cancel') }}`.replace('{purchaseOrder}', id), function(data) {
                alert('Purchase order cancelled successfully');
                loadPurchaseOrders();
            });
        }
    }

    function convertToPurchase(id) {
        if (confirm('Convert this PO to Purchase?')) {
            $.post(`{{ route('admin.purchase-orders.convert') }}`.replace('{purchaseOrder}', id), function(data) {
                alert('Purchase order converted to purchase successfully');
                loadPurchaseOrders();
            });
        }
    }

    function viewPO(id) {
        window.location.href = `{{ route('admin.purchase-orders.show') }}`.replace('{purchaseOrder}', id);
    }
</script>
@endpush
