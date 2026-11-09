@extends('layouts.master')
@section('title') {{ __('الفاتورة الإلكترونية (ETA)') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="row g-3" x-data="etaSettings()">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h5>🧾 {{ __('إعدادات منظومة الضرائب') }}</h5>
            <template x-if="s">
            <div>
                <label class="form-label">{{ __('الوضع') }}</label>
                <select x-model="s.eta_driver" class="form-select mb-2">
                    <option value="simulation">{{ __('محاكاة (بدون بيانات دخول)') }}</option>
                    <option value="api">{{ __('اتصال حقيقي بـ ETA') }}</option>
                </select>
                <input x-model="s.eta_issuer_name" class="form-control mb-2" placeholder="{{ __('اسم المنشأ') }}">
                <input x-model="s.eta_issuer_rin" class="form-control mb-2" placeholder="{{ __('الرقم الضريبي RIN') }}">
                <input x-model="s.eta_activity_code" class="form-control mb-2" placeholder="{{ __('كود النشاط') }}">
                <input x-model="s.eta_client_id" class="form-control mb-2" placeholder="Client ID (API mode)">
                <input x-model="s.eta_client_secret" type="password" class="form-control mb-2" placeholder="Client Secret (API mode)">
                <label class="form-check mb-2"><input type="checkbox" x-model="s.eta_sandbox" class="form-check-input"> {{ __('بيئة الاختبار (sandbox)') }}</label>
                <button class="btn btn-success w-100" @click="save()">{{ __('حفظ الإعدادات') }}</button>
            </div>
            </template>
        </div></div>
        <div class="card mt-3"><div class="card-body">
            <h6>{{ __('إرسال فاتورة بيع') }}</h6>
            <form onsubmit="submitEta(this.sale_id.value); return false;">
                <select name="sale_id" class="form-select mb-2">
                    @foreach($recentSales as $sale)<option value="{{ $sale->id }}">{{ $sale->invoice_number ?? '#'.$sale->id }} — {{ number_format($sale->total_amount, 2) }}</option>@endforeach
                </select>
                <button class="btn btn-primary w-100">{{ __('إرسال للضرائب') }}</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-body table-responsive">
            <h5>{{ __('مستندات مُرسلة') }} <span class="badge bg-info">{{ $driver === 'simulation' ? 'محاكاة' : 'API' }}</span></h5>
            <table class="table table-sm">
                <tr><th>#</th><th>{{ __('الفاتورة') }}</th><th>{{ __('UUID') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('الخطأ') }}</th></tr>
                @forelse($invoices as $inv)
                <tr>
                    <td>{{ $inv->id }}</td>
                    <td>{{ $inv->sale?->invoice_number ?? ('#'.$inv->sale_id) }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($inv->submission_uuid, 18) }}</small></td>
                    <td><span class="badge {{ $inv->status === 'accepted' ? 'bg-success' : ($inv->status === 'error' ? 'bg-danger' : 'bg-secondary') }}">{{ $inv->status }}</span></td>
                    <td><small class="text-danger">{{ \Illuminate\Support\Str::limit($inv->error_message, 60) }}</small></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted">{{ __('لا مستندات — أرسل أول فاتورة') }}</td></tr>
                @endforelse
            </table>
            {{ $invoices->links() }}
        </div></div>
    </div>
    </div>
</div>
<script>
function etaSettings() {
    return {
        s: null,
        async init() { this.s = (await (await fetch('{{ route('admin.eta.settings') }}')).json()).data; },
        async save() {
            const r = await fetch('{{ route('admin.eta.settings.save') }}', {method: 'PUT',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(this.s)});
            alert(r.ok ? 'تم الحفظ' : 'فشل');
        }
    }
}
async function submitEta(saleId) {
    const r = await fetch('{{ url('admin/eta/sales') }}/' + saleId + '/submit', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
    const j = await r.json();
    alert(j.success ? 'الحالة: ' + j.data.status : 'فشل');
    if (j.success) location.reload();
}
</script>
@endsection
