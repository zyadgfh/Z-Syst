@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Purchase Order #{{ $purchaseOrder->po_number }}</h2>
                <div class="btn-group">
                    <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @if($purchaseOrder->status === 'draft')
                        <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endif
                    <button class="btn btn-info" onclick="printPO()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-success" onclick="sendPDF()">
                        <i class="fas fa-file-pdf"></i> Send PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Purchase Order Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>PO Number:</strong> {{ $purchaseOrder->po_number }}</p>
                            <p><strong>Supplier:</strong> {{ $purchaseOrder->supplier ? $purchaseOrder->supplier->name : 'N/A' }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge badge-{{ getStatusClass($purchaseOrder->status) }}">
                                    {{ ucfirst($purchaseOrder->status) }}
                                </span>
                            </p>
                            <p><strong>Priority:</strong>
                                <span class="badge badge-{{ getPriorityClass($purchaseOrder->priority) }}">
                                    {{ ucfirst($purchaseOrder->priority) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> {{ formatDate($purchaseOrder->order_date) }}</p>
                            <p><strong>Expected Delivery:</strong> {{ formatDate($purchaseOrder->expected_delivery_date) }}</p>
                            <p><strong>Created By:</strong> {{ $purchaseOrder->createdBy ? $purchaseOrder->createdBy->name : 'N/A' }}</p>
                            <p><strong>Approved By:</strong> {{ $purchaseOrder->approvedBy ? $purchaseOrder->approvedBy->name : 'N/A' }}</p>
                        </div>
                    </div>

                    @if($purchaseOrder->shipping_address)
                        <div class="mt-3">
                            <p><strong>Shipping Address:</strong></p>
                            <p>{{ $purchaseOrder->shipping_address }}</p>
                        </div>
                    @endif

                    @if($purchaseOrder->terms)
                        <div class="mt-3">
                            <p><strong>Terms:</strong></p>
                            <p>{{ $purchaseOrder->terms }}</p>
                        </div>
                    @endif

                    @if($purchaseOrder->notes)
                        <div class="mt-3">
                            <p><strong>Notes:</strong></p>
                            <p>{{ $purchaseOrder->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Order Items</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Discount (%)</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                                        <td>{{ $item->product ? $item->product->sku : 'N/A' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ formatCurrency($item->unit_price) }}</td>
                                        <td>{{ $item->discount }}%</td>
                                        <td>{{ formatCurrency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                                    <td>{{ formatCurrency($purchaseOrder->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Tax:</strong></td>
                                    <td>{{ formatCurrency($purchaseOrder->tax) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Shipping:</strong></td>
                                    <td>{{ formatCurrency($purchaseOrder->shipping_cost) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                    <td><strong>{{ formatCurrency($purchaseOrder->total_amount) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Progress</h4>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar" style="width: {{ $purchaseOrder->completion_percentage }}%">
                            {{ $purchaseOrder->completion_percentage }}%
                        </div>
                    </div>
                    <p><strong>Total Quantity:</strong> {{ $purchaseOrder->total_quantity }}</p>
                    <p><strong>Received Quantity:</strong> {{ $purchaseOrder->received_quantity }}</p>
                    <p><strong>Pending Quantity:</strong> {{ $purchaseOrder->total_quantity - $purchaseOrder->received_quantity }}</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Actions</h4>
                </div>
                <div class="card-body">
                    @if($purchaseOrder->status === 'draft')
                        <button class="btn btn-success btn-block mb-2" onclick="sendPO()">
                            <i class="fas fa-paper-plane"></i> Send to Supplier
                        </button>
                    @endif

                    @if($purchaseOrder->status === 'sent')
                        <button class="btn btn-success btn-block mb-2" onclick="approvePO()">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-block mb-2" onclick="rejectPO()">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    @endif

                    @if(in_array($purchaseOrder->status, ['accepted', 'partially_received']))
                        <button class="btn btn-primary btn-block mb-2" onclick="convertToPurchase()">
                            <i class="fas fa-exchange-alt"></i> Convert to Purchase
                        </button>
                    @endif

                    @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                        <button class="btn btn-warning btn-block mb-2" onclick="cancelPO()">
                            <i class="fas fa-ban"></i> Cancel
                        </button>
                    @endif

                    @if($purchaseOrder->status === 'cancelled')
                        <button class="btn btn-info btn-block mb-2" onclick="restorePO()">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Timeline</h4>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <strong>Created:</strong> {{ formatDateTime($purchaseOrder->created_at) }}
                        </li>
                        @if($purchaseOrder->sent_at)
                            <li>
                                <strong>Sent:</strong> {{ formatDateTime($purchaseOrder->sent_at) }}
                            </li>
                        @endif
                        @if($purchaseOrder->approved_at)
                            <li>
                                <strong>Approved:</strong> {{ formatDateTime($purchaseOrder->approved_at) }}
                            </li>
                        @endif
                        @if($purchaseOrder->cancelled_at)
                            <li>
                                <strong>Cancelled:</strong> {{ formatDateTime($purchaseOrder->cancelled_at) }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function sendPO() {
        if (confirm('Send this purchase order to supplier?')) {
            $.post('{{ route('admin.purchase-orders.send', $purchaseOrder) }}', function(data) {
                alert('Purchase order sent successfully');
                location.reload();
            });
        }
    }

    function approvePO() {
        if (confirm('Approve this purchase order?')) {
            $.post('{{ route('admin.purchase-orders.approve', $purchaseOrder) }}', function(data) {
                alert('Purchase order approved successfully');
                location.reload();
            });
        }
    }

    function rejectPO() {
        const reason = prompt('Please enter rejection reason:');
        if (reason) {
            $.post('{{ route('admin.purchase-orders.reject', $purchaseOrder) }}', { reason: reason }, function(data) {
                alert('Purchase order rejected successfully');
                location.reload();
            });
        }
    }

    function cancelPO() {
        if (confirm('Cancel this purchase order?')) {
            $.post('{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}', function(data) {
                alert('Purchase order cancelled successfully');
                location.reload();
            });
        }
    }

    function restorePO() {
        if (confirm('Restore this purchase order?')) {
            $.post('{{ route('admin.purchase-orders.restore', $purchaseOrder) }}', function(data) {
                alert('Purchase order restored successfully');
                location.reload();
            });
        }
    }

    function convertToPurchase() {
        if (confirm('Convert this PO to Purchase?')) {
            $.post('{{ route('admin.purchase-orders.convert', $purchaseOrder) }}', function(data) {
                alert('Purchase order converted to purchase successfully');
                location.reload();
            });
        }
    }

    function printPO() {
        window.print();
    }

    function sendPDF() {
        window.open('{{ route('admin.purchase-orders.pdf', $purchaseOrder) }}', '_blank');
    }

    @php
        function getStatusClass($status) {
            $classes = [
                'draft' => 'secondary',
                'sent' => 'info',
                'accepted' => 'success',
                'partially_received' => 'warning',
                'received' => 'success',
                'cancelled' => 'danger',
                'rejected' => 'danger'
            ];
            return $classes[$status] ?? 'secondary';
        }

        function getPriorityClass($priority) {
            $classes = [
                'low' => 'secondary',
                'normal' => 'info',
                'high' => 'warning',
                'urgent' => 'danger'
            ];
            return $classes[$priority] ?? 'info';
        }
    @endphp
</script>
@endpush
