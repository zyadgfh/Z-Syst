@extends('layouts.master')
@section('title') {{ __('المبيعات الحية') }} @endsection
@section('main_content')
<div class="container-fluid" x-data="liveBoard({$lastId})">
    <div class="row g-3 mb-3">
        <div class="col-6">
            <div class="card text-bg-success"><div class="card-body">
                <h6>{{ __('مبيعات اليوم') }}</h6><h2 x-text="todayTotal.toFixed(2) + ' EGP'"></h2>
            </div></div>
        </div>
        <div class="col-6">
            <div class="card text-bg-primary"><div class="card-body">
                <h6>{{ __('عدد الفواتير') }}</h6><h2 x-text="todayCount"></h2>
            </div></div>
        </div>
    </div>
    <div class="card"><div class="card-body table-responsive">
        <h5>📡 {{ __('بث حي') }} <span class="badge bg-danger">LIVE</span></h5>
        <table class="table table-sm">
            <tr><th>{{ __('الفاتورة') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('الدفع') }}</th><th>{{ __('الوقت') }}</th></tr>
            <template x-for="s in sales" :key="s.id">
                <tr class="table-success">
                    <td x-text="s.invoice || ('#' + s.id)"></td>
                    <td x-text="s.total.toFixed(2)"></td>
                    <td x-text="s.payment"></td>
                    <td x-text="s.at"></td>
                </tr>
            </template>
        </table>
    </div></div>
</div>
<script>
function liveBoard(lastId) {
    return {
        sales: [], todayTotal: parseFloat('{{ $today->sum("totalAmount") }}'), todayCount: parseInt('{{ $today->count() }}'), cursor: lastId,
        init() {
            const es = new EventSource('{{ route('admin.live.stream') }}?after=' + this.cursor);
            es.addEventListener('sale', (e) => { const d = JSON.parse(e.data); this.sales.unshift(d); this.sales = this.sales.slice(0, 25); });
            es.addEventListener('totals', (e) => { const d = JSON.parse(e.data); this.todayTotal = d.today_total; this.todayCount = d.today_count; this.cursor = d.last_id; });
            es.onerror = () => es.close() || setTimeout(() => location.reload(), 3000);
        }
    }
}
</script>
@endsection
