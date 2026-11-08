<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $business->companyName }} — {{ __('متجر') }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<style>
:root {
    --ap-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --ap-bg: #f5f5f7; --ap-surface: #ffffff; --ap-text: #1d1d1f; --ap-text-2: #6e6e73;
    --ap-hairline: rgba(0,0,0,.08); --ap-accent: #0071e3; --ap-green: #34c759;
    --ap-spring: cubic-bezier(0.32, 0.72, 0, 1);
}
body { font-family: var(--ap-font); background: var(--ap-bg); color: var(--ap-text); letter-spacing: 0; }
.navbar { background: rgba(255,255,255,.78) !important; -webkit-backdrop-filter: saturate(180%) blur(20px); backdrop-filter: saturate(180%) blur(20px); border-bottom: 1px solid var(--ap-hairline); }
.navbar-brand { font-weight: 700; letter-spacing: -0.022em; }
.card { background: var(--ap-surface); border: 1px solid var(--ap-hairline); border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.06); transition: transform .35s var(--ap-spring); }
.card:hover { transform: translateY(-2px); }
.btn { border-radius: 980px; font-weight: 600; transition: transform .25s var(--ap-spring); }
.btn:active { transform: scale(.96); }
.btn-success { background: var(--ap-green); border-color: var(--ap-green); }
.btn-outline-success { color: var(--ap-green); border-color: var(--ap-green); }
.position-fixed.bg-white { border-inline-start: 1px solid var(--ap-hairline); border-radius: 22px 0 0 22px; }
.text-success { color: var(--ap-green) !important; }
</style>
</head>
<body class="bg-light" x-data="cart()">
<nav class="navbar bg-white shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand fw-bold">💊 {{ $business->companyName }}</span>
        <button class="btn btn-success position-relative" @click="open = true">
            🛒 {{ __('السلة') }}
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="count()"></span>
        </button>
    </div>
</nav>
<div class="container">
    <div class="row g-3">
        @foreach($products as $p)
        <div class="col-6 col-md-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-1">{{ $p->name }}</h6>
                    <div class="text-success fw-bold">{{ number_format($p->sales_price, 2) }} EGP</div>
                    <button class="btn btn-sm btn-outline-success mt-2" @click="add({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->sales_price }})">+ {{ __('أضف') }}</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- cart drawer --}}
<div class="position-fixed top-0 end-0 h-100 bg-white shadow p-3" style="width: 320px; z-index: 1050" x-show="open" x-transition>
    <div class="d-flex justify-content-between"><b>{{ __('سلة المشتريات') }}</b><button class="btn-close" @click="open = false"></button></div>
    <template x-for="i in items" :key="i.id">
        <div class="d-flex justify-content-between border-bottom py-2">
            <span x-text="i.name"></span>
            <span><button class="btn btn-sm" @click="i.qty > 1 && i.qty--">−</button>
            <b x-text="i.qty"></b>
            <button class="btn btn-sm" @click="i.qty++">+</button></span>
        </div>
    </template>
    <div class="mt-3">
        <input class="form-control mb-2" x-model="name" placeholder="{{ __('الاسم') }}">
        <input class="form-control mb-2" x-model="phone" placeholder="{{ __('الهاتف') }}">
        <select class="form-select mb-2" x-model="fulfillment">
            <option value="pickup">{{ __('استلام من الفرع') }}</option>
            <option value="delivery">{{ __('توصيل (+15 جنيه)') }}</option>
        </select>
        <input class="form-control mb-2" x-model="address" placeholder="{{ __('العنوان (للتوصيل)') }}" x-show="fulfillment === 'delivery'">
        <div class="d-flex justify-content-between fw-bold mb-2"><span>{{ __('الإجمالي') }}</span><span x-text="total().toFixed(2) + ' EGP'"></span></div>
        <button class="btn btn-success w-100" @click="checkout('{{ $business->store_slug }}')" :disabled="!items.length">{{ __('إتمام الطلب') }}</button>
        <p class="msg small mt-2 text-center" x-text="msg"></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function cart() {
    return {
        items: JSON.parse(localStorage.getItem('store_cart_{{ $business->id }}') || '[]'),
        open: false, name: '', phone: '', address: '', fulfillment: 'pickup', msg: '',
        add(id, name, price) {
            const found = this.items.find(i => i.id === id);
            found ? found.qty++ : this.items.push({id, name, price, qty: 1});
            this.save();
        },
        count() { return this.items.reduce((s, i) => s + i.qty, 0); },
        total() { return this.items.reduce((s, i) => s + i.price * i.qty, 0); },
        save() { localStorage.setItem('store_cart_{{ $business->id }}', JSON.stringify(this.items)); },
        async checkout(slug) {
            const r = await fetch(`/store/${slug}/checkout`, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({customer_name: this.name, customer_phone: this.phone, address: this.address, fulfillment: this.fulfillment,
                    items: this.items.map(i => ({product_id: i.id, qty: i.qty}))})});
            const j = await r.json();
            this.msg = j.success ? `✅ تم الطلب ${j.order_number} — الإجمالي ${j.total} EGP` : (j.message || 'فشل');
            if (j.success) { this.items = []; this.save(); }
        }
    }
}
</script>
</body>
</html>
