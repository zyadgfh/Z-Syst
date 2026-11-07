@extends('layouts.master')

@section('title')
    {{ __('Barcode') }} — {{ $barcode->barcode_number }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 font-monospace">{{ $barcode->barcode_number }}</h4>
                <div>
                    <a href="{{ route('admin.barcodes.labels', ['ids' => $barcode->id]) }}" class="btn btn-primary" target="_blank">{ __('Print') }</a>
                    <a href="{{ route('admin.barcodes.index') }}" class="btn btn-light">{ __('Back') }</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><th class="text-muted w-25">{ __('Type') }</th><td><span class="badge bg-dark">{{ $barcode->barcode_type }}</span></td></tr>
                        <tr><th class="text-muted">{ __('Product') }</th><td>{{ $barcode->product?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">{ __('Batch') }</th><td>{{ $barcode->batch?->batch_no ?? '—' }}</td></tr>
                        <tr><th class="text-muted">{ __('Expiry') }</th><td>{{ $barcode->batch?->expire_date?->format('Y-m-d') ?? '—' }}</td></tr>
                        <tr><th class="text-muted">{ __('Status') }</th><td>{{ Str::of($barcode->print_status)->replace('_', ' ')->title() }}</td></tr>
                        <tr><th class="text-muted">{ __('Prints') }</th><td>{{ $barcode->print_count }}</td></tr>
                        <tr><th class="text-muted">{ __('Last printed') }</th><td>{{ $barcode->printed_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6 text-center">
                    <div class="p-3 bg-white border rounded d-inline-block">
                        <img src="{{ route('admin.barcodes.image', $barcode) }}" alt="{{ $barcode->barcode_number }}" style="max-width: 100%; height: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
