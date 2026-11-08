@extends('layouts.master')
@section('title') {{ __('الدفع الإلكتروني') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-body" x-data="subPay()">
                <h5>💳 {{ __('دفع الاشتراك أونلاين') }}</h5>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" @click="pay('paymob')">Paymob</button>
                    <button class="btn btn-outline-primary" @click="pay('fawry')">Fawry</button>
                    <button class="btn btn-outline-dark" @click="pay('instapay')">InstaPay</button>
                </div>
                <p class="msg small mt-2" x-text="msg"></p>
                <p class="small text-muted">{{ __('بدون مفاتيح API يعمل الوضع في «محاكاة» كاملة — أضف المفاتيح من إعدادات الأعمال للتحويل الحقيقي') }}</p>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card"><div class="card-body table-responsive">
                <h5>{{ __('معاملات الدفع') }}</h5>
                <table class="table table-sm">
                    <tr><th>#</th><th>{{ __('البوابة') }}</th><th>{{ __('الغرض') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('مرجع') }}</th></tr>
                    @forelse($transactions as $t)
                    <tr>
                        <td>{{ $t->id }}</td><td>{{ $t->gateway }}</td><td>{{ $t->purpose }}</td>
                        <td>{{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                        <td><span class="badge {{ $t->status === 'paid' ? 'bg-success' : ($t->status === 'pending' ? 'bg-warning' : 'bg-danger') }}">{{ $t->status }}</span></td>
                        <td><small>{{ $t->reference }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted">{{ __('لا معاملات') }}</td></tr>
                    @endforelse
                </table>
                {{ $transactions->links() }}
            </div></div>
        </div>
    </div>
</div>
<script>
function subPay() {
    return { msg: '',
        async pay(gateway) {
            const r = await fetch('{{ route('admin.subscription.pay') }}', {method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({gateway})});
            const j = await r.json();
            if (j.success) {
                this.msg = (j.data.mode === 'simulation' ? '[محاكاة] ' : '') + 'معاملة #' + j.data.transaction_id + ' — ' + j.data.amount + ' EGP';
                if (j.data.mode === 'simulation') setTimeout(() => location.reload(), 1200);
            } else this.msg = j.message || 'فشل';
        }
    }
}
</script>
@endsection
