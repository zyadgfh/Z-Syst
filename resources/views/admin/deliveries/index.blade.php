@extends('layouts.master')
@section('title') {{ __('التوصيل والمناديب') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5>🛵 {{ __('طلب توصيل جديد') }}</h5>
                <form method="POST" action="{{ route('admin.deliveries.store') }}">
                    @csrf
                    <input name="customer_name" class="form-control mb-2" placeholder="{{ __('اسم العميل') }}" required>
                    <input name="customer_phone" class="form-control mb-2" placeholder="{{ __('الهاتف') }}">
                    <textarea name="address" class="form-control mb-2" placeholder="{{ __('العنوان') }}" required></textarea>
                    <input name="fee" type="number" step="0.01" class="form-control mb-2" placeholder="{{ __('رسوم التوصيل') }}" value="15">
                    <button class="btn btn-success w-100">{{ __('إنشاء') }}</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card"><div class="card-body table-responsive">
                <h5>{{ __('لوحة التوصيل') }}</h5>
                <table class="table table-sm align-middle">
                    <tr><th>#</th><th>{{ __('العميل') }}</th><th>{{ __('المندوب') }}</th><th>{{ __('الرسوم') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('أكشنز') }}</th></tr>
                    @forelse($deliveries as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td>{{ $d->customer_name }}<br><small class="text-muted">{{ $d->address }}</small></td>
                        <td>
                            <select class="form-select form-select-sm" onchange="assign({{ $d->id }}, this.value)">
                                <option value="">{{ __('— مندوب —') }}</option>
                                @foreach($couriers as $c)<option value="{{ $c->id }}" {{ $d->courier_user_id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                            </select>
                        </td>
                        <td>{{ number_format($d->fee, 2) }}</td>
                        <td><span class="badge {{ $d->status === 'delivered' ? 'bg-success' : 'bg-info' }}">{{ $d->status }}</span></td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="setStatus({{ $d->id }}, 'out_for_delivery')">🛵</button>
                            <button class="btn btn-sm btn-success" onclick="setStatus({{ $d->id }}, 'delivered')">✓</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="setStatus({{ $d->id }}, 'failed')">✕</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted">{{ __('لا طلبات توصيل') }}</td></tr>
                    @endforelse
                </table>
                {{ $deliveries->links() }}
            </div></div>
        </div>
    </div>
</div>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
async function assign(id, courierId) {
    if (!courierId) return;
    const r = await fetch('{{ url('admin/deliveries') }}/' + id + '/assign', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}, body: JSON.stringify({courier_user_id: +courierId})});
    if (r.ok) location.reload();
}
async function setStatus(id, status) {
    const r = await fetch('{{ url('admin/deliveries') }}/' + id + '/status', {method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}, body: JSON.stringify({status})});
    if (r.ok) location.reload();
}
</script>
@endsection
