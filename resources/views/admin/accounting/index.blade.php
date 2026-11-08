@extends('layouts.master')
@section('title') {{ __('المحاسبة') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-body">
                <h5>📒 {{ __('قيد يدوي') }}</h5>
                <form method="POST" action="{{ route('admin.accounting.entries.store') }}">
                    @csrf
                    <input type="date" name="entry_date" class="form-control mb-2" value="{{ now()->toDateString() }}">
                    <input name="description" class="form-control mb-2" placeholder="{{ __('الوصف') }}">
                    <input name="amount" type="number" step="0.01" class="form-control mb-2" placeholder="{{ __('المبلغ') }}" required>
                    <div class="row g-2 mb-3">
                        <div class="col"><input name="debit_account" class="form-control" placeholder="مدين 1010" required></div>
                        <div class="col"><input name="credit_account" class="form-control" placeholder="دائن 4010" required></div>
                    </div>
                    <button class="btn btn-success w-100">{{ __('سجل') }}</button>
                </form>
            </div></div>
            <div class="card"><div class="card-body" x-data="vat()">
                <h5>🧮 {{ __('تقرير القيمة المضافة') }}</h5>
                <button class="btn btn-outline-primary btn-sm mb-2" @click="load()">{{ __('تحديث') }}</button>
                <template x-if="d">
                    <div class="small">
                        <div>{{ __('إيراد المبيعات') }}: <b x-text="d.sales_revenue"></b></div>
                        <div>{{ __('ضريبة مخرجة') }}: <b x-text="d.output_vat"></b></div>
                        <div>{{ __('ضريبة داخلة') }}: <b x-text="d.input_vat"></b></div>
                        <div class="fw-bold mt-1">{{ __('الصافي المستحق') }}: <span x-text="d.net_vat_due"></span></div>
                    </div>
                </template>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body table-responsive">
                <h5>{{ __('ميزان المراجعة') }}</h5>
                <table class="table table-sm">
                    <tr><th>{{ __('الكود') }}</th><th>{{ __('الحساب') }}</th><th>{{ __('مدين') }}</th><th>{{ __('دائن') }}</th><th>{{ __('الرصيد') }}</th></tr>
                    @foreach($accounts as $a)
                    <tr><td>{{ $a['code'] }}</td><td>{{ $a['name'] }}</td>
                        <td>{{ number_format($a['debit'], 2) }}</td><td>{{ number_format($a['credit'], 2) }}</td>
                        <td class="fw-bold">{{ number_format($a['balance'], 2) }}</td></tr>
                    @endforeach
                </table>
            </div></div>
            <div class="card"><div class="card-body table-responsive">
                <h5>{{ __('أحدث القيود') }}</h5>
                <table class="table table-sm">
                    <tr><th>{{ __('التاريخ') }}</th><th>{{ __('المصدر') }}</th><th>{{ __('الوصف') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('البنود') }}</th></tr>
                    @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->entry_date?->format('m/d') }}</td><td>{{ $e->source }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($e->description, 40) }}</td>
                        <td>{{ number_format($e->amount, 2) }}</td>
                        <td><small>@foreach($e->lines ?? [] as $l) {{ $l['account_code'] }}: {{ $l['debit'] > 0 ? 'مدين '.number_format($l['debit'], 0) : 'دائن '.number_format($l['credit'], 0) }} @endforeach</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-muted">{{ __('المبيعات ستنشئ قيودها تلقائيًا') }}</td></tr>
                    @endforelse
                </table>
                {{ $entries->links() }}
            </div></div>
        </div>
    </div>
</div>
<script>
function vat() {
    return { d: null,
        async load() {
            this.d = (await (await fetch('{{ route('admin.accounting.vat') }}')).json()).data;
        }
    }
}
document.addEventListener('DOMContentLoaded', () => { const el = document.querySelector('[x-data="vat()"]'); el && Alpine.data && 0; });
</script>
@endsection
