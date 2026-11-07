@extends('layouts.master')
@section('title') {{ __('طلبات المتجر الإلكتروني') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="card"><div class="card-body table-responsive">
        <h5>🛒 {{ __('طلبات المتجر') }} <a class="btn btn-sm btn-outline-primary ms-2" href="/store/{{ auth()->user()->business?->store_slug }}" target="_blank">{{ __('عرض المتجر') }} ↗</a></h5>
        <table class="table table-sm align-middle">
            <tr><th>#</th><th>{{ __('العميل') }}</th><th>{{ __('الاستلام') }}</th><th>{{ __('الإجمالي') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('أكشنز') }}</th></tr>
            @forelse($orders as $o)
            <tr>
                <td>{{ $o->order_number }}</td>
                <td>{{ $o->customer_name }}<br><small class="text-muted">{{ $o->customer_phone }}</small></td>
                <td>{{ $o->fulfillment === 'delivery' ? '🛵 توصيل' : '🏬 استلام' }}</td>
                <td>{{ number_format($o->total, 2) }}</td>
                <td><span class="badge bg-secondary">{{ $o->status }}</span></td>
                <td class="d-flex gap-1" x-data>
                    <button class="btn btn-sm btn-outline-success" onclick="setStatus({{ $o->id }}, 'confirmed')">{{ __('تأكيد') }}</button>
                    <button class="btn btn-sm btn-outline-primary" onclick="setStatus({{ $o->id }}, 'ready')">{{ __('جاهز') }}</button>
                    <button class="btn btn-sm btn-success" onclick="sellOrder({{ $o->id }})" {{ $o->sale_id ? 'disabled' : '' }}>{{ __('تحويل لمبيعة') }}</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="setStatus({{ $o->id }}, 'cancelled')">{{ __('إلغاء') }}</button>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-muted">{{ __('لا طلبات — شارك رابط متجرك مع عملائك') }}</td></tr>
            @endforelse
        </table>
        {{ $orders->links() }}
    </div></div>
</div>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
async function setStatus(id, status) {
    const r = await fetch('{{ url('admin/store-orders') }}/' + id + '/status', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}, body: JSON.stringify({status})});
    if (r.ok) location.reload();
}
async function sellOrder(id) {
    const r = await fetch('{{ url('admin/store-orders') }}/' + id + '/sell', {method: 'POST', headers: {'X-CSRF-TOKEN': CSRF}});
    const j = await r.json();
    alert(j.success ? 'تم البيع #' + j.sale_id : (j.message || 'فشل'));
    if (j.success) location.reload();
}
</script>
@endsection
