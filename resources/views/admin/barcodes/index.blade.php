@extends('layouts.master')

@section('title')
    {{ __('Barcodes') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">{ __('Barcodes') }</h4>
                    <a href="{{ route('admin.barcodes.create') }}" class="btn btn-primary">
                        { __('Generate Barcodes') }
                    </a>
                </div>

                <form method="GET" class="row g-2 mb-3">
                    <div class="col-auto">
                        <select name="type" class="form-select form-select-sm">
                            <option value="">{ __('All types') }</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="print_status" class="form-select form-select-sm">
                            <option value="">{ __('All statuses') }</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('print_status') === $status)>{{ Str::of($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-light">{ __('Filter') }</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr>
                            <th>{ __('Number') }</th>
                            <th>{ __('Type') }</th>
                            <th>{ __('Product') }</th>
                            <th>{ __('Batch') }</th>
                            <th>{ __('Status') }</th>
                            <th>{ __('Prints') }</th>
                            <th>{ __('Created') }</th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                            @forelse($barcodes as $barcode)
                                <tr>
                                    <td class="font-monospace">{{ $barcode->barcode_number }}</td>
                                    <td><span class="badge bg-dark">{{ $barcode->barcode_type }}</span></td>
                                    <td>{{ $barcode->product?->name ?? '—' }}</td>
                                    <td>{{ $barcode->batch?->batch_no ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $barcode->print_status === \App\Models\Barcode::STATUS_NOT_PRINTED ? 'bg-warning text-dark' : 'bg-success' }}">
                                            {{ Str::of($barcode->print_status)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>
                                    <td>{{ $barcode->print_count }}</td>
                                    <td>{{ $barcode->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.barcodes.show', $barcode) }}" class="btn btn-sm btn-light">{ __('View') }</a>
                                        <a href="{{ route('admin.barcodes.labels', ['ids' => $barcode->id]) }}" class="btn btn-sm btn-light" target="_blank">{ __('Print') }</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">{ __('No barcodes yet — generate your first label.') }</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $barcodes->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
