@extends('layouts.master')

@section('title')
    {{ __('Product Movement & Sales Analytics') }}
@endsection

@section('main_content')
<div class="container-fluid py-3" id="product-analytics">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">{{ __('Product Movement & Sales Analytics') }}</h3>
            <p class="text-muted mb-0">{{ __('Track sales averages and stock movement by week, month or year.') }}</p>
        </div>
        <div class="btn-group" role="group">
            <button class="btn btn-outline-primary period-btn" data-period="week">{{ __('Week') }}</button>
            <button class="btn btn-outline-primary period-btn active" data-period="month">{{ __('Month') }}</button>
            <button class="btn btn-outline-primary period-btn" data-period="year">{{ __('Year') }}</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Product ID (optional)') }}</label>
                    <input id="product-id" type="number" min="1" class="form-control" placeholder="{{ __('All products') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Warehouse ID (optional)') }}</label>
                    <input id="warehouse-id" type="number" min="1" class="form-control" placeholder="{{ __('All warehouses') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('From') }}</label>
                    <input id="date-from" type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('To') }}</label>
                    <input id="date-to" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <button id="custom-load" class="btn btn-primary w-100">{{ __('Custom range') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div id="analytics-alert" class="alert alert-danger d-none"></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Total Sales') }}</small><h3 id="revenue">0</h3><span id="revenue-change" class="small"></span></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Average Daily Sales') }}</small><h3 id="avg-daily">0</h3><span class="small text-muted" id="period-label"></span></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Average Order Value') }}</small><h3 id="avg-order">0</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Sold Quantity') }}</small><h3 id="sold-qty">0</h3><span class="small text-muted" id="avg-qty"></span></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Average Weekly Sales') }}</small><h4 id="avg-weekly">0</h4></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Average Monthly Sales') }}</small><h4 id="avg-monthly">0</h4></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('Average Yearly Sales') }}</small><h4 id="avg-yearly">0</h4></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><strong>{{ __('Sales Trend') }}</strong></div>
                <div class="card-body"><canvas id="sales-chart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><strong>{{ __('Stock Movement') }}</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>{{ __('Incoming') }}</span><strong id="m-in">0</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>{{ __('Outgoing') }}</span><strong id="m-out">0</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>{{ __('Returns') }}</span><strong id="m-return">0</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>{{ __('Transfers') }}</span><strong id="m-transfer">0</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>{{ __('Adjustments') }}</span><strong id="m-adjustment">0</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>{{ __('Net Movement') }}</span><strong id="m-net">0</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Top Selling Products') }}</strong>
            <span class="text-muted small">{{ __('Up to 50 products') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Code') }}</th><th>{{ __('Quantity Sold') }}</th><th>{{ __('Revenue') }}</th></tr></thead>
                <tbody id="products-body"><tr><td colspan="4" class="text-center text-muted py-4">{{ __('Loading...') }}</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const root = document.getElementById('product-analytics');
    if (!root) return;

    let chart = null;
    const money = value => new Intl.NumberFormat(undefined, {maximumFractionDigits: 2}).format(Number(value || 0));

    async function load(period, custom = false) {
        const params = new URLSearchParams();
        params.set('period', custom ? 'custom' : period);

        const productId = document.getElementById('product-id').value.trim();
        if (productId) params.set('product_id', productId);

        const warehouseId = document.getElementById('warehouse-id').value.trim();
        if (warehouseId) params.set('warehouse_id', warehouseId);

        if (custom) {
            const from = document.getElementById('date-from').value;
            const to = document.getElementById('date-to').value;
            if (!from || !to) return;
            params.set('date_from', from);
            params.set('date_to', to);
        }

        document.querySelectorAll('.period-btn').forEach(btn =>
            btn.classList.toggle('active', !custom && btn.dataset.period === period)
        );

        const alert = document.getElementById('analytics-alert');
        alert.classList.add('d-none');

        try {
            const response = await fetch('{{ url('/api/v1/product-analytics') }}?' + params.toString(), {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);

            const json = await response.json();
            const data = json.data;

            document.getElementById('revenue').textContent = money(data.sales.revenue);
            document.getElementById('avg-daily').textContent = money(data.sales.average_daily_revenue);
            document.getElementById('avg-order').textContent = money(data.sales.average_order_value);
            document.getElementById('sold-qty').textContent = money(data.sales.quantity);
            document.getElementById('avg-weekly').textContent = money(data.sales.averages.weekly_revenue);
            document.getElementById('avg-monthly').textContent = money(data.sales.averages.monthly_revenue);
            document.getElementById('avg-yearly').textContent = money(data.sales.averages.yearly_revenue);
            document.getElementById('avg-qty').textContent = money(data.sales.average_daily_quantity) + ' / day';
            document.getElementById('period-label').textContent = data.period.from + ' → ' + data.period.to;

            const change = Number(data.sales.comparison.revenue_change_percent || 0);
            document.getElementById('revenue-change').textContent =
                (change >= 0 ? '+' : '') + change + '% vs previous period';

            ['in','out','return','transfer','adjustment','net'].forEach(type => {
                document.getElementById('m-' + type).textContent = money(data.movements[type]);
            });

            const body = document.getElementById('products-body');
            body.innerHTML = data.products.length
                ? data.products.map(p =>
                    '<tr><td>' + escapeHtml(p.name || '') + '</td>' +
                    '<td>' + escapeHtml(p.code || '') + '</td>' +
                    '<td>' + money(p.quantity) + '</td>' +
                    '<td>' + money(p.revenue) + '</td></tr>'
                ).join('')
                : '<tr><td colspan="4" class="text-center text-muted py-4">No sales data</td></tr>';

            const chartRows = data.period.type === 'year' ? data.sales.buckets : data.sales.daily;
            const labels = chartRows.map(row => data.period.type === 'year' ? row.period : row.date);
            const values = chartRows.map(row => row.revenue);

            if (chart) chart.destroy();
            chart = new Chart(document.getElementById('sales-chart'), {
                type: 'line',
                data: {labels, datasets: [{label: 'Sales', data: values, tension: .3, fill: true}]},
                options: {
                    responsive: true,
                    interaction: {mode: 'index', intersect: false},
                    plugins: {legend: {display: false}},
                    scales: {y: {beginAtZero: true}}
                }
            });
        } catch (error) {
            alert.textContent = 'Unable to load analytics data.';
            alert.classList.remove('d-none');
        }
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    document.querySelectorAll('.period-btn').forEach(btn =>
        btn.addEventListener('click', () => load(btn.dataset.period))
    );
    document.getElementById('custom-load').addEventListener('click', () => load('custom', true));

    load('month');
})();
</script>
@endpush
