@extends('layouts.admin')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ $supplier->company_name }}</h2>
                <div class="btn-group">
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @can('suppliers-edit')
                    <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('purchases.Supplier Details') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('purchases.Company Name:') }}</strong> {{ $supplier->company_name }}</p>
                            <p><strong>{{ __('purchases.Contact Person:') }}</strong> {{ $supplier->contact_person }}</p>
                            <p><strong>{{ __('purchases.Email:') }}</strong> {{ $supplier->email }}</p>
                            <p><strong>{{ __('purchases.Phone:') }}</strong> {{ $supplier->phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('purchases.Tax ID:') }}</strong> {{ $supplier->tax_id ?? 'N/A' }}</p>
                            <p><strong>{{ __('purchases.License Number:') }}</strong> {{ $supplier->license_number ?? 'N/A' }}</p>
                            <p><strong>{{ __('purchases.Payment Terms:') }}</strong> {{ ucfirst(str_replace('_', ' ', $supplier->payment_terms)) }}</p>
                            <p><strong>{{ __('purchases.Credit Limit:') }}</strong> {{ number_format($supplier->credit_limit, 2) }}</p>
                        </div>
                    </div>

                    @if($supplier->address)
                        <div class="mt-3">
                            <p><strong>{{ __('purchases.Address:') }}</strong></p>
                            <p>{{ $supplier->address }}</p>
                        </div>
                    @endif

                    @if($supplier->notes)
                        <div class="mt-3">
                            <p><strong>{{ __('purchases.Notes:') }}</strong></p>
                            <p>{{ $supplier->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>{{ __('purchases.Performance Metrics') }}</h4>
                </div>
                <div class="card-body">
                    @if($supplier->performance)
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3>{{ $supplier->performance->on_time_delivery_rate }}%</h3>
                                    <p>{{ __('purchases.On-Time Delivery') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3>{{ $supplier->performance->quality_score }}%</h3>
                                    <p>{{ __('purchases.Quality Score') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3>{{ $supplier->performance->price_competitiveness }}%</h3>
                                    <p>{{ __('purchases.Price Competitiveness') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3>{{ $supplier->performance->responsiveness }}%</h3>
                                    <p>{{ __('purchases.Responsiveness') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>{{ __('purchases.Total Orders:') }}</strong> {{ $supplier->performance->total_orders }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>{{ __('purchases.Total Disputes:') }}</strong> {{ $supplier->performance->total_disputes }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-center">No performance data available. Click "Calculate Performance" to generate.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('common.Actions') }}</h4>
                </div>
                <div class="card-body">
                    <button class="btn btn-success btn-block mb-2" onclick="calculatePerformance()">
                        <i class="fas fa-chart-line"></i> Calculate Performance
                    </button>
                    <button class="btn btn-info btn-block mb-2" onclick="addRating()">
                        <i class="fas fa-star"></i> Add Rating
                    </button>
                    <button class="btn btn-warning btn-block mb-2" onclick="addContract()">
                        <i class="fas fa-file-contract"></i> Add Contract
                    </button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>{{ __('purchases.Statistics') }}</h4>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('purchases.Average Rating:') }}</strong> {{ number_format($supplier->average_rating, 1) }}/5</p>
                    <p><strong>{{ __('purchases.Performance Score:') }}</strong> {{ number_format($supplier->performance_score, 1) }}/100</p>
                    <p><strong>{{ __('purchases.Total Orders:') }}</strong> {{ $supplier->purchaseOrders->count() }}</p>
                    <p><strong>{{ __('purchases.Ratings:') }}</strong> {{ $supplier->ratings->count() }}</p>
                    <p><strong>{{ __('purchases.Contracts:') }}</strong> {{ $supplier->contracts->count() }}</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>{{ __('orders.Recent Orders') }}</h4>
                </div>
                <div class="card-body">
                    @if($supplier->purchaseOrders->count() > 0)
                        <ul class="list-unstyled">
                            @foreach($supplier->purchaseOrders->take(5) as $order)
                                <li>
                                    <a href="{{ route('admin.purchase-orders.show', $order) }}">
                                        {{ $order->po_number }}
                                    </a>
                                    <span class="badge badge-{{ $order->status === 'received' ? 'success' : 'warning' }}">
                                        {{ $order->status }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-center">No orders yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function calculatePerformance() {
        if (confirm('Calculate performance for this supplier?')) {
            $.post('{{ route('admin.suppliers.calculate-performance', $supplier) }}', function(data) {
                alert('Performance calculated successfully');
                location.reload();
            });
        }
    }

    function addRating() {
        const rating = prompt('Enter rating (1-5):');
        if (rating && rating >= 1 && rating <= 5) {
            const category = prompt('Enter category (quality, delivery, price, service):');
            const review = prompt('Enter review (optional):');
            
            $.post('/api/v1/suppliers/{{ $supplier->id }}/ratings', {
                rating: rating,
                category: category,
                review: review
            }, function(data) {
                alert('Rating added successfully');
                location.reload();
            });
        }
    }

    function addContract() {
        alert('Contract creation form would open here');
    }
</script>
@endpush
@endsection
