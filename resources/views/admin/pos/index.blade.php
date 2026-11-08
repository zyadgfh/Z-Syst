@extends('layouts.master')

@section('title')
    {{ __('Sales Board (POS)') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="row g-3">

        {{-- Shift / connection strip --}}
        <div class="col-12">
            <div class="card"><div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                <span class="badge {{ $shiftOpen ? 'bg-success' : 'bg-secondary' }} p-2" id="shiftBadge">
                    {{ $shiftOpen ? '💰 '.__('وردية مفتوحة: ').$shiftNumber : '💰 '.__('لا وردية مفتوحة — افتحها من الورديات') }}
                </span>
                <span class="badge bg-secondary p-2" id="netBadge">…</span>
                <input id="couponCode" class="form-control form-control-sm d-inline-block" style="width: 160px" placeholder="{{ __('كوبون خصم (اختياري)') }}">
                <span id="promoInfo" class="small text-success"></span>
                <span id="interactionWarnings" class="small text-danger flex-grow-1"></span>
            </div></div>
        </div>

        {{-- Status strip --}}
        <div class="col-12">
            <div class="card"><div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                <span class="badge {{ $approved ? 'bg-success' : 'bg-danger' }} p-2">
                    {{ $approved ? __('الجهاز مُعتمد') : __('الجهاز بانتظار الاعتماد — إدارة الأجهزة') }}
                </span>
                @if($device)
                    <span class="text-muted small">🖥 {{ $device->name }} ({{ $device->type }})</span>
                    <span class="text-muted small">{{ __('ماسح') }}: {{ $device->has_scanner ? '✓' : '—' }}</span>
                @endif
                <span class="text-muted small">
                    {{ __('طابعة باركود') }}: {{ $devicePayload['printers']['barcode']['name'] ?? '—' }}
                </span>
                <span class="text-muted small">
                    {{ __('طابعة إيصالات') }}: {{ $devicePayload['printers']['receipt']['name'] ?? '—' }}
                </span>
                <a href="{{ route('admin.device-setup.index') }}" class="btn btn-sm btn-light ms-auto">⚙ {{ __('إعدادات الأجهزة') }}</a>
            </div></div>
        </div>

        {{-- Scan box + cart --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <label class="form-label fw-bold">{ __('امسح الباركود أو اكتب الكود ثم Enter') }</label>
                    <input type="text" id="scanBox" class="form-control form-control-lg font-monospace"
                           placeholder="⌨ {{ __('حقل الماسح — يعمل تلقائيًا عند المسح') }}" autocomplete="off">
                    <div id="scanHint" class="form-text">
                        {{ __('الماسح يكتب الكود كضربات لوحة مفاتيح وينهيه بـ Enter — سيتعرف عليه الحقل ويفرد الصنف في الفاتورة.') }}
                    </div>

                    <table class="table table-hover align-middle mt-3">
                        <thead class="table-light"><tr>
                            <th>{{ __('الصنف') }}</th><th style="width:110px">{{ __('الدفعة') }}</th>
                            <th style="width:90px">{{ __('الكمية') }}</th><th style="width:110px">{{ __('السعر') }}</th>
                            <th class="text-end">{{ __('الإجمالي') }}</th><th></th>
                        </tr></thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow"><td colspan="6" class="text-center text-muted py-4">{{ __('لا أصناف بعد — امسح أول باركود') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Totals + checkout --}}
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5>{{ __('الفاتورة') }}</h5>
                <table class="table table-sm">
                    <tr><td>{{ __('عدد الأصناف') }}</td><td class="text-end" id="sumLines">0</td></tr>
                    <tr><td>{{ __('إجمالي الكمية') }}</td><td class="text-end" id="sumQty">0</td></tr>
                    <tr class="fs-5 fw-bold"><td>{{ __('الإجمالي') }}</td><td class="text-end" id="sumTotal">0.00</td></tr>
                </table>

                <label class="form-label">{{ __('المدفوع') }}</label>
                <input type="number" id="paidAmount" step="0.01" min="0" class="form-control form-control-lg mb-2" value="0">

                <select id="paymentType" class="form-select mb-3">
                    <option value="cash">{{ __('نقدي') }}</option>
                    <option value="card">{{ __('بطاقة') }}</option>
                    <option value="wallet">{{ __('محفظة') }}</option>
                </select>

                <button id="checkoutBtn" class="btn btn-success btn-lg w-100">✓ {{ __('إتمام البيع') }}</button>
                <div id="posMsg" class="alert d-none mt-3 mb-0 py-2"></div>
            </div></div>
        </div>
    </div>
</div>

@push('modal')
<script>
(function () {
    const CFG = @json($settings);
    const DEVICE = @json($devicePayload);
    window.DOSE_SCHEDULES = @json($schedules);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const cart = new Map();   // productId → line

    const scanBox = document.getElementById('scanBox');
    const cartBody = document.getElementById('cartBody');
    const msg = document.getElementById('posMsg');

    // ---------- scanner capture (keyboard-wedge) ----------
    let buffer = '', lastKey = 0;
    const MAX_MS = CFG.scanner.max_input_ms || 150;

    if (CFG.scanner.auto_focus) { setInterval(() => { if (document.activeElement !== scanBox && !document.activeElement.closest('input,select')) scanBox.focus(); }, 2500); scanBox.focus(); }

    scanBox.addEventListener('keydown', async (e) => {
        const now = Date.now();
        if (now - lastKey > MAX_MS) buffer = '';   // slow typing ≠ scanner
        lastKey = now;

        if (e.key === 'Enter') {
            e.preventDefault();
            const code = (buffer || scanBox.value).trim();
            buffer = ''; scanBox.value = '';
            if (code.length >= (CFG.scanner.min_chars || 4)) await handleScan(code);
            return;
        }
        if (e.key.length === 1) buffer += e.key;
    });

    async function handleScan(code) {
        const res = await fetch('{{ route("admin.pos.lookup") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ code }),
        }).then(r => r.json()).catch(() => null);

        if (!res || !res.success) { flash('danger', res?.message || '{{ __('كود غير معروف') }}'); return; }
        if (!CFG.scanner.auto_add) { flash('info', res.data.name + ' — ' + res.data.price); return; }
        addToCart(res.data);
        if (CFG.scanner.beep) beep();
        checkInteractions();
    }

    // Drug–drug interaction screening for the whole cart (pharmacist net)
    let interactionsTimer = null;
    function checkInteractions() {
        clearTimeout(interactionsTimer);
        interactionsTimer = setTimeout(async () => {
            const ids = [...cart.values()].map(l => l.product_id);
            const box = document.getElementById('interactionWarnings');
            if (ids.length < 2) { box.textContent = ''; return; }
            const res = await fetch('{{ route('admin.pos.interactions') }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ product_ids: [...new Set(ids)] }),
            }).then(r => r.json()).catch(() => null);
            if (!res || !res.success) return;
            box.textContent = res.data.length
                ? '⚠️ ' + res.data.map(w => w.severity.toUpperCase() + ': ' + w.between.join(' + ') + ' — ' + w.description).join(' | ')
                : '';
            if (res.data.length) beep(320);
        }, 400);
    }

    // ---------- cart ----------
    function addToCart(item) {
        const key = item.product_id + '|' + (item.batch_no ?? '');
        if (cart.has(key)) {
            const line = cart.get(key);
            line.qty += 1;
        } else {
            cart.set(key, { product_id: item.product_id, name: item.name, price: item.price,
                            qty: 1, batch_no: item.batch_no ?? '', stock: item.stock });
        }
        renderCart(key);
    }

    function renderCart(flashKey) {
        cartBody.innerHTML = '';
        if (cart.size === 0) { cartBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">{{ __('لا أصناف بعد') }}</td></tr>'; totals(); return; }

        for (const [key, line] of cart) {
            const tr = document.createElement('tr');
            if (key === flashKey) tr.className = 'table-success fw-bold';
            const doseOpts = (window.DOSE_SCHEDULES || []).map(s =>
                '<option value="' + s.code + '"' + (line.dose_code === s.code ? ' selected' : '') + '>' + s.label + '</option>').join('');
            tr.innerHTML =
                '<td>' + line.name + '</td>' +
                '<td class="font-monospace small">' + (line.batch_no || '—') + '</td>' +
                '<td><input type="number" min="1" class="form-control form-control-sm qty" value="' + line.qty + '" data-key="' + key + '"></td>' +
                '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm price" value="' + line.price + '" data-key="' + key + '"></td>' +
                '<td>' +
                    '<select class="form-select form-select-sm dose-code" data-key="' + key + '">' +
                        '<option value="">{{ __('بدون جرعة') }}</option>' + doseOpts +
                    '</select>' +
                    '<input class="form-control form-control-sm dose-text mt-1" data-key="' + key + '" placeholder="{{ __('جرعة مخصصة — 1-0-1 بعد الأكل') }}" value="' + (line.dose || '').replace(/"/g, '&quot;') + '">' +
                '</td>' +
                '<td class="text-end line-total">' + (line.qty * line.price).toFixed(2) + '</td>' +
                '<td><button class="btn btn-sm btn-light rm" data-key="' + key + '">✕</button></td>';
            cartBody.appendChild(tr);
        }
        cartBody.querySelectorAll('.qty').forEach(i => i.onchange = () => { cart.get(i.dataset.key).qty = Math.max(1, +i.value); renderCart(); });
        cartBody.querySelectorAll('.price').forEach(i => i.onchange = () => { cart.get(i.dataset.key).price = +i.value; renderCart(); });
        cartBody.querySelectorAll('.dose-code').forEach(sel => sel.onchange = () => {
            const l = cart.get(sel.dataset.key);
            if (!l) return;
            l.dose_code = sel.value;
            l.dose = sel.value ? scheduleLabel(sel.value) : '';
            const txt = cartBody.querySelector('.dose-text[data-key="' + CSS.escape(sel.dataset.key) + '"]');
            if (txt) txt.value = l.dose;
        });
        cartBody.querySelectorAll('.dose-text').forEach(inp => inp.onchange = () => {
            const l = cart.get(inp.dataset.key);
            if (!l) return;
            l.dose = inp.value;
            if (!inp.value) l.dose_code = '';
        });
        cartBody.querySelectorAll('.rm').forEach(b => b.onclick = () => { cart.delete(b.dataset.key); renderCart(); });
        totals();
    }

    function totals() {
        let qty = 0, total = 0;
        for (const line of cart.values()) { qty += line.qty; total += line.qty * line.price; }
        document.getElementById('sumLines').textContent = cart.size;
        document.getElementById('sumQty').textContent = qty;
        document.getElementById('sumTotal').textContent = total.toFixed(2);
        if (document.getElementById('paidAmount').value === '0') document.getElementById('paidAmount').value = total.toFixed(2);
    }

    // ---------- checkout ----------
    document.getElementById('checkoutBtn').onclick = async () => {
        if (cart.size === 0) return;
        const products = [...cart.values()].map(l => ({
            product_id: l.product_id, price: l.price, lossProfit: 0,
            quantities: l.qty, batch_no: l.batch_no || null,
            dose: l.dose || null, dose_code: l.dose_code || null,
        }));
        const payload = { products, paidAmount: +document.getElementById('paidAmount').value,
                          paymentType: document.getElementById('paymentType').value,
                          coupon_code: document.getElementById('couponCode').value.trim() || null,
                          isPaid: +document.getElementById('paidAmount').value >= totals0() };

        function totals0() { let t = 0; for (const l of cart.values()) t += l.qty * l.price; return t; }
        payload.dueAmount = Math.max(0, totals0() - payload.paidAmount);

        let res;
        try {
            const r = await fetch('{{ route("admin.pos.checkout") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            res = await r.json();
        } catch (e) {
            res = null;  // network failed → offline path below
        }

        if (!res) {
            queueOffline(payload);
            return;
        }

        if (!res.success) { flash('danger', res.message || '{{ __('فشل البيع') }}'); return; }
        if (res.promotion && res.promotion.discount > 0) {
            flash('success', '{{ __('خصم مطبّق') }}: ' + res.promotion.discount + ' EGP' + (res.promotion.bonus_units ? ' +' + res.promotion.bonus_units + ' {{ __('وحدة مجانية') }}' : ''));
        }

        cart.clear(); renderCart();
        document.getElementById('paidAmount').value = 0;
        flash('success', res.message);

        // Print rules — the server decides per business settings.
        if (res.print?.barcode_url) window.open(res.print.barcode_url, '_blank');
        if (res.print?.receipt_on_sale === 'auto' && res.print?.receipt_url) window.open(res.print.receipt_url, '_blank');
        if (res.dose_labels_url) window.open(res.dose_labels_url, '_blank');
    };

    function flash(kind, text) {
        msg.className = 'alert alert-' + kind + ' mt-3 mb-0 py-2';
        msg.textContent = text;
        setTimeout(() => msg.classList.add('d-none'), 4000);
    }

    function scheduleLabel(code) {
        const hit = (window.DOSE_SCHEDULES || []).find(s => s.code === code);
        return hit ? hit.label : code;
    }

    // ---------- offline PWA queue ----------
    function queueOffline(payload) {
        payload.client_uuid = crypto.randomUUID();
        payload.saleDate = new Date().toISOString().slice(0, 10);
        const q = JSON.parse(localStorage.getItem('zsyst_offline_sales') || '[]');
        q.push(payload);
        localStorage.setItem('zsyst_offline_sales', JSON.stringify(q));
        cart.clear(); renderCart();
        document.getElementById('paidAmount').value = 0;
        flash('warning', '📡 {{ __('لا اتصال — حُفظ الفاتورة وستتزامن تلقائيًا عند عودة الشبكة') }} (' + q.length + ')');
    }

    async function replayQueue() {
        const q = JSON.parse(localStorage.getItem('zsyst_offline_sales') || '[]');
        if (!q.length) return;
        const res = await fetch('{{ route('admin.pos.sync') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ sales: q.map(p => ({ client_uuid: p.client_uuid, products: p.products, paidAmount: p.paidAmount, dueAmount: p.dueAmount, isPaid: p.isPaid, paymentType: p.paymentType, saleDate: p.saleDate })) }),
        }).then(r => r.json()).catch(() => null);
        if (res && res.success) {
            const remain = res.data.failed || [];
            localStorage.setItem('zsyst_offline_sales', JSON.stringify(q.filter(p => remain.some(f => f.client_uuid === p.client_uuid))));
            flash('success', '✅ {{ __('تمت مزامنة فواتير الأوفلاين') }}: ' + res.data.accepted.length + ' ✓, ' + res.data.duplicates.length + ' {{ __('مكررة تم تجاهلها') }}');
        }
    }

    const netBadge = document.getElementById('netBadge');
    function paintNet() {
        netBadge.className = 'badge p-2 ' + (navigator.onLine ? 'bg-success' : 'bg-danger');
        netBadge.textContent = navigator.onLine ? '🌐 {{ __('متصل') }}' : '📡 {{ __('أوفلاين — يُخزن محليًا') }}';
        if (navigator.onLine) replayQueue();
    }
    window.addEventListener('online', paintNet);
    window.addEventListener('offline', paintNet);
    paintNet();
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});

    function beep(freq) { beep.frequency = freq;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.frequency.value = beep.frequency || 880; gain.gain.value = 0.08;
            osc.connect(gain); gain.connect(ctx.destination);
            osc.start(); osc.stop(ctx.currentTime + 0.09);
        } catch (e) {}
    }
})();
</script>
@endpush
@endsection
