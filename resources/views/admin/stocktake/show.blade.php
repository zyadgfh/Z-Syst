@extends('layouts.master')
@section('title') {{ __('جرد') }} {{ $audit->audit_number }} @endsection
@section('main_content')
<div class="container-fluid" x-data="{ code: '', beep(qty, variance) { } }">
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <h5 class="mb-0">{{ $audit->audit_number }}</h5>
            <span class="badge {{ $audit->status === 'completed' ? 'bg-success' : 'bg-warning' }}">{{ $audit->status }}</span>
            <span>{{ __('جُرد') }}: {{ $summary['counted'] }} / {{ $summary['lines'] }}</span>
            <span class="text-danger">{{ __('فروقات') }}: {{ $summary['variance_lines'] }}</span>
            <div class="ms-auto">
                @if($audit->status !== 'completed')
                <input id="scan-input" class="form-control d-inline-block w-auto" placeholder="{{ __('امسح هنا أو بالجهاز') }}" autofocus>
                @else
                <span class="text-success fw-bold">{{ __('مكتمل') }}</span>
                @endif
            </div>
        </div>
    </div></div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <tr><th>{{ __('الصنف') }}</th><th>{{ __('الدفعة') }}</th><th>{{ __('النظامي') }}</th><th>{{ __('المعدود') }}</th><th>{{ __('الفرق') }}</th></tr>
            @forelse($details as $d)
            <tr id="line-{{ $d->product_id }}">
                <td>{{ $d->product?->name }}</td>
                <td>{{ $d->batch_no }}</td>
                <td>{{ $d->system_quantity }}</td>
                <td class="fw-bold">{{ $d->physical_quantity }}</td>
                <td class="{{ $d->variance > 0 ? 'text-success' : ($d->variance < 0 ? 'text-danger' : 'text-muted') }}">{{ $d->variance }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted">{{ __('لا بنود') }}</td></tr>
            @endforelse
        </table>
        {{ $details->links() }}
        @if($audit->status !== 'completed')
        <form method="POST" action="{{ route('admin.stocktake.apply', $audit) }}" class="mt-2" onsubmit="return confirm('{{ __('سيتم تعديل المخزون حسب العد — متأكد؟') }}')">
            @csrf
            <button class="btn btn-danger">{{ __('تطبيق الجرد على المخزون') }}</button>
        </form>
        @endif
    </div></div>
</div>
@if($audit->status !== 'completed')
<script>
const scanInput = document.getElementById('scan-input');
scanInput?.addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter' || !scanInput.value.trim()) return;
    const code = scanInput.value.trim();
    scanInput.value = '';
    const r = await fetch('{{ route('admin.stocktake.scan') }}', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({code})});
    if (!r.ok) { alert('كود غير معروف: ' + code); return; }
    const j = await r.json();
    const row = document.getElementById('line-' + j.data.product_id);
    if (row) row.scrollIntoView({behavior: 'smooth', block: 'center'});
    location.reload();
});
</script>
@endif
@endsection
