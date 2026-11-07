@extends('layouts.master')
@section('title') {{ __('الورديات والدرج النقدي') }} @endsection
@section('main_content')
<div class="container-fluid" x-data="{ closeId: null }">
    <div class="row g-3">
        <div class="col-12">
            <div class="card"><div class="card-body">
                <h5 class="mb-3">💰 {{ __('الوردية الحالية') }}</h5>
                @if($current)
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <span class="badge bg-success p-2">{{ $current->shift_number }}</span>
                        <span>{{ __('افتتحت') }}: {{ $current->opened_at?->format('H:i') }}</span>
                        <span>{{ ['opening_float' => __('افتتاحي')][$current->opening_float < 0 ? 'opening_float' : 'opening_float'] }}: {{ number_format($current->opening_float, 2) }}</span>
                        <span>{{ __('المتوقع في الدرج') }}: <b>{{ number_format($current->expected_cash, 2) }}</b> EGP</span>
                        <div class="ms-auto d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="cashMove('cash_in')">{{ __('إيداع نقدي') }}</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="cashMove('cash_out')">{{ __('سحب نقدي') }}</button>
                            <button class="btn btn-sm btn-danger" @click="closeId = {{ $current->id }}">{{ __('إغلاق الوردية (جرد الدرج)') }}</button>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.shifts.open') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-auto"><label class="form-label">{{ __('نقدية افتتاحية') }}</label>
                            <input type="number" step="0.01" name="opening_float" class="form-control" value="0"></div>
                        <div class="col-auto"><label class="form-label">{{ __('ملاحظة') }}</label>
                            <input name="notes" class="form-control"></div>
                        <div class="col-auto"><button class="btn btn-success">{{ __('فتح وردية') }}</button></div>
                    </form>
                @endif
            </div></div>
        </div>

        <div class="col-12">
            <div class="card"><div class="card-body table-responsive">
                <h5>{{ __('سجل الورديات') }}</h5>
                <table class="table table-sm align-middle">
                    <tr><th>#</th><th>{{ __('الحالة') }}</th><th>{{ __('فتحت') }}</th><th>{{ __('أغلقت') }}</th><th>{{ __('متوقع') }}</th><th>{{ __('محصور') }}</th><th>{{ __('الفرق') }}</th><th></th></tr>
                    @forelse($shifts as $s)
                    <tr>
                        <td>{{ $s->shift_number }}</td>
                        <td><span class="badge {{ $s->status === 'open' ? 'bg-success' : 'bg-secondary' }}">{{ __('open' === $s->status ? 'مفتوحة' : 'مغلقة') }}</span></td>
                        <td>{{ $s->opened_at?->format('m/d H:i') }}</td>
                        <td>{{ $s->closed_at?->format('m/d H:i') }}</td>
                        <td>{{ number_format($s->expected_cash, 2) }}</td>
                        <td>{{ number_format($s->counted_cash, 2) }}</td>
                        <td class="{{ abs($s->difference) > 50 ? 'text-danger fw-bold' : '' }}">{{ number_format($s->difference, 2) }}</td>
                        <td><a href="#" class="btn btn-sm btn-outline-secondary" onclick="zReport({{ $s->id }});return false;">Z</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted">{{ __('لا ورديات بعد') }}</td></tr>
                    @endforelse
                </table>
                {{ $shifts->links() }}
            </div></div>
        </div>
    </div>

    {{-- close modal --}}
    <div class="modal fade" :class="closeId ? 'show d-block' : ''" style="background: rgba(0,0,0,.5)" @click.self="closeId = null">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-header">
            <h5 class="modal-title">{{ __('جرد الدرج وإغلاق الوردية') }}</h5>
            <button class="btn-close" @click="closeId = null"></button></div>
            <div class="modal-body">
                <label class="form-label">{{ __('النقدية المعدودة') }}</label>
                <input type="number" step="0.01" id="counted" class="form-control">
                <label class="form-label mt-2">{{ __('ملاحظات') }}</label>
                <input id="close-notes" class="form-control">
            </div>
            <div class="modal-footer"><button class="btn btn-danger" @click="doClose(closeId)">{{ __('إغلاق نهائي') }}</button></div>
        </div></div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
async function cashMove(type) {
    const amount = prompt(type === 'cash_in' ? 'مبلغ الإيداع:' : 'مبلغ السحب:');
    if (!amount) return;
    const r = await fetch('{{ url("admin/shifts") }}/{{ $current?->id }}/cash', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({type, amount: parseFloat(amount), reason: 'cash ' + type})});
    if (r.ok) location.reload(); else alert((await r.json()).message || 'خطأ');
}
async function doClose(id) {
    const r = await fetch('{{ url("admin/shifts") }}/' + id + '/close', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({counted_cash: parseFloat(document.getElementById('counted').value || 0), notes: document.getElementById('close-notes').value})});
    const j = await r.json();
    if (j.success) {
        const d = j.report.difference;
        alert(`تقرير Z\nمبيعات: ${j.report.sales_count} فاتورة = ${j.report.sales_total}\nمتوقع: ${j.report.expected_cash}\nفرق: ${d}`);
        location.href = '{{ route('admin.shifts.index') }}';
    } else alert(j.message);
}
async function zReport(id) {
    const r = await fetch('{{ url("admin/shifts") }}/' + id + '/report');
    const j = await r.json();
    alert(JSON.stringify(j.report, null, 1));
}
</script>
@endsection
