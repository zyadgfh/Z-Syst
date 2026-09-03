@extends('layouts.admin')

@section('title')
    {{ __('gateways.Receipt: ') }}{{ $receipt->receipt_number }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('gateways.Receipt #') }}{{ $receipt->receipt_number }}</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.receipts.index') }}" class="add-order-btn rounded-2 active">
                                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
                            </a>
                            <a href="{{ route('admin.receipts.show', $receipt) }}?download=1" class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-1"></i> {{ __('products.Download PDF') }}
                            </a>
                            <button class="btn btn-success btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> {{ __('common.Print') }}
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="receipt-container" style="max-width: 800px; margin: 0 auto;">
                            <div class="receipt-header text-center mb-4">
                                <h3 class="fw-bold">{{ $settings->receipt_header ?? config('app.name') }}</h3>
                                <p class="text-muted small mb-0">{{ $settings->tagline ?? '' }}</p>
                            </div>

                            <div class="receipt-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <strong>{{ __('gateways.Receipt Number:') }}</strong>
                                        <span>{{ $receipt->receipt_number }}</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>{{ __('purchases.Date:') }}</strong>
                                        <span>{{ \Carbon\Carbon::parse($receipt->created_at)->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <strong>{{ __('gateways.Customer:') }}</strong>
                                        <span>{{ $receipt->party->name ?? __('common.Unknown') }}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>{{ __('gateways.Type:') }}</strong>
                                        <span>
                                            @if ($receipt->receiptable_type === 'sale')
                                                {{ __('gateways.Sale') }}
                                            @else
                                                {{ __('gateways.Purchase') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="receipt-items mt-4">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('gateways.Item') }}</th>
                                                <th class="text-center">{{ __('common.Qty') }}</th>
                                                <th class="text-end">{{ __('common.Price') }}</th>
                                                <th class="text-end">{{ __('common.Total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $items = $receipt->receiptable ? $receipt->receiptable->details ?? [] : [];
                                            @endphp
                                            @forelse($items as $item)
                                                <tr>
                                                    <td>{{ $item->product->name ?? $item->product_name ?? $item->name ?? __('gateways.Unknown Item') }}</td>
                                                    <td class="text-center">{{ $item->qty ?? $item->quantity ?? 1 }}</td>
                                                    <td class="text-end">{{ $item->price ?? 0 }}</td>
                                                    <td class="text-end">{{ ($item->qty ?? 1) * ($item->price ?? 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">{{ __('common.No items available') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">{{ __('purchases.Total:') }}</td>
                                                <td class="text-end fw-bold">{{ $receipt->total_amount }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                @if($receipt->payment_status)
                                    <div class="receipt-payment mt-3">
                                        <strong>{{ __('gateways.Payment Status:') }}</strong>
                                        <span class="badge bg-success">{{ ucfirst($receipt->payment_status) }}</span>
                                    </div>
                                @endif

                                @if($settings->receipt_footer ?? false)
                                    <div class="receipt-footer mt-4 pt-3 border-top">
                                        <p class="text-muted small">{{ $settings->receipt_footer }}</p>
                                        @if($settings->tax_number ?? false)
                                            <p class="text-muted small">{{ __('purchases.Tax:') }} {{ $settings->tax_number }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
