<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy POS</title>
    <style>
        :root { --bg:#f2f7ff; --card:#ffffff; --text:#0f172a; --muted:#64748b; --primary:#2563eb; --accent:#0f766e; --danger:#dc2626; --border:#dbeafe; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Inter, Arial, sans-serif; background:linear-gradient(135deg,#f8fbff 0%, #eef6ff 100%); color:var(--text); }
        .app-shell { max-width: 1180px; margin: 0 auto; padding: 24px; }
        .hero { display:flex; flex-wrap:wrap; gap:16px; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .hero h1 { margin:0 0 6px; font-size:28px; }
        .hero p { margin:0; color:var(--muted); }
        .grid { display:grid; grid-template-columns: 1.2fr 0.8fr; gap:16px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:18px; box-shadow:0 10px 30px rgba(15,23,42,.06); }
        .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:12px; }
        .stat { padding:12px; border-radius:14px; background:#f8fbff; border:1px solid var(--border); }
        .stat strong { display:block; font-size:20px; margin-bottom:4px; }
        .section-title { font-size:16px; font-weight:700; margin:0 0 8px; }
        .pill { display:inline-block; margin-top:8px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600; background:#ecfeff; color:var(--accent); }
        .list { margin:0; padding:0; list-style:none; display:grid; gap:10px; }
        .list li { border:1px solid var(--border); border-radius:12px; padding:10px 12px; background:#fcfdff; }
        label { display:block; margin-top:10px; font-weight:600; font-size:13px; }
        input, button, select { width:100%; padding:12px; margin-top:6px; border-radius:10px; border:1px solid var(--border); font-size:14px; }
        button { background:var(--primary); color:white; border:none; cursor:pointer; font-weight:700; }
        button.secondary { background:#0f766e; }
        .scanner-box { border:1px dashed var(--border); border-radius:14px; background:#fbfdff; min-height:220px; display:flex; align-items:center; justify-content:center; margin-top:12px; overflow:hidden; }
        #scannerVideo { width:100%; max-height:240px; background:#000; }
        .status { margin-top:12px; padding:12px 14px; border-radius:12px; background:#eff6ff; color:#1d4ed8; font-size:14px; }
        .muted { color:var(--muted); }
        @media (max-width: 900px){ .grid{grid-template-columns:1fr;} .stats{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="hero">
            <div>
                <h1>Pharmacy POS workspace</h1>
                <p>Fast dispensing, real-time stock awareness, and smarter reorder guidance in one screen.</p>
            </div>
            <div class="pill">Live summary • Barcode-ready</div>
        </div>

        <div class="grid">
            <div class="card">
                <h3 class="section-title">Operations snapshot</h3>
                <div class="stats" id="stats"></div>
                <div class="status" id="summaryStatus">Loading summary…</div>

                <div class="d-flex align-items-center justify-content-between" style="gap:12px; margin-top:16px; flex-wrap:wrap;">
                    <h3 class="section-title" style="margin:0;">Forecast recommendations</h3>
                    <button type="button" onclick="refreshSummary()" style="width:auto; padding:10px 14px; border-radius:12px;">Refresh</button>
                </div>
                <div class="muted" id="forecastMeta" style="margin-top:6px; font-size:13px;"></div>

                <ul class="list" id="forecastList"></ul>

            </div>

            <div class="card">
                <h3 class="section-title">Quick dispense</h3>
                <label>Prescription ID</label>
                <input id="prescriptionId" placeholder="e.g. 12" />
                <label>Barcode</label>
                <input id="barcode" placeholder="Scan or type barcode" />
                <label>Quantity</label>
                <input id="quantity" type="number" min="1" value="1" />
                <button onclick="submitDispense()">Submit dispense</button>
                <button class="secondary" onclick="toggleScanner()" style="margin-top:8px;">Toggle camera scanner</button>

                <div class="scanner-box">
                    <video id="scannerVideo" autoplay playsinline muted></video>
                </div>
                <div class="status" id="dispenseStatus">Ready to scan or dispense.</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script>
        const summaryStatus = document.getElementById('summaryStatus');
        const stats = document.getElementById('stats');
        const forecastList = document.getElementById('forecastList');
        const dispenseStatus = document.getElementById('dispenseStatus');
        const video = document.getElementById('scannerVideo');
        const barcodeInput = document.getElementById('barcode');
        let scannerActive = false;
        let refreshInFlight = false;
        let dispenseInFlight = false;


        function renderDemoSummary() {
            stats.innerHTML = `
                <div class="stat"><strong>0</strong><span class="muted">Pending prescriptions</span></div>
                <div class="stat"><strong>0</strong><span class="muted">Low stock alerts</span></div>
                <div class="stat"><strong>0</strong><span class="muted">Forecast items</span></div>
            `;
            forecastList.innerHTML = '<li>No forecast data available yet. The page will display live data once the API is reachable.</li>';
            const meta = document.getElementById('forecastMeta');
            if (meta) meta.textContent = 'Tip: refresh to apply latest dispensed history.';
        }


        function renderForecastSkeleton() {
            forecastList.innerHTML = [0, 1, 2, 3, 4].map(() => `
                <li style="opacity:.8">
                    <div style="height:14px; width:55%; background:#e5f0ff; border-radius:8px; margin-bottom:10px;"></div>
                    <div style="height:12px; width:80%; background:#eef6ff; border-radius:8px;"></div>
                </li>
            `).join('');
        }

        function confidencePill(conf) {
            const c = (conf || 'low').toLowerCase();
            if (c === 'high') return `<span style="display:inline-block; padding:4px 9px; border-radius:999px; font-size:12px; font-weight:700; background:#dcfce7; color:#166534; border:1px solid #bbf7d0;">High</span>`;
            if (c === 'medium') return `<span style="display:inline-block; padding:4px 9px; border-radius:999px; font-size:12px; font-weight:700; background:#fef9c3; color:#854d0e; border:1px solid #fde68a;">Medium</span>`;
            return `<span style="display:inline-block; padding:4px 9px; border-radius:999px; font-size:12px; font-weight:700; background:#fee2e2; color:#991b1b; border:1px solid #fecaca;">Low</span>`;
        }

        async function loadSummary() {
            if (refreshInFlight) return;
            refreshInFlight = true;

            summaryStatus.textContent = 'Loading summary…';
            document.querySelector('button[onclick="refreshSummary()"]')?.setAttribute('disabled', 'disabled');
            renderForecastSkeleton();

            try {
                const response = await fetch('/api/v1/pharmacy/pos-summary', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Unavailable');

                const data = await response.json();
                stats.innerHTML = `
                    <div class="stat"><strong>${data.pending_prescriptions_count ?? 0}</strong><span class="muted">Pending prescriptions</span></div>
                    <div class="stat"><strong>${(data.low_stock_products || []).length}</strong><span class="muted">Low stock alerts</span></div>
                    <div class="stat"><strong>${(data.forecast || []).length}</strong><span class="muted">Forecast items</span></div>
                `;

                const items = (data.forecast || []).slice(0, 10);
                forecastList.innerHTML = items.map(item => {
                    const name = item.product_name ? `${item.product_name}` : `Product #${item.product_id || 'n/a'}`;
                    const demand = item.average_daily_demand ?? 0;
                    const reorder = item.recommended_reorder_quantity ?? 0;
                    const safety = item.safety_stock ?? null;
                    const confidence = confidencePill(item.confidence);
                    const trend = item.trend ? ` • Trend: ${item.trend}` : '';

                    return `
                        <li>
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                                <div>
                                    <strong>${name}</strong>
                                    <div class="muted" style="margin-top:6px; font-size:13px;">
                                        Demand ${demand} / day • Reorder ${reorder}
                                        ${safety !== null ? `• Safety ${safety}` : ''}${trend}
                                    </div>
                                </div>
                                <div>
                                    ${confidence}
                                </div>
                            </div>
                        </li>
                    `;
                }).join('');

                summaryStatus.textContent = `Summary loaded • ${data.generated_at || ''}`;
            } catch (error) {
                renderDemoSummary();
                summaryStatus.textContent = 'Demo mode: API unavailable. You can still use the scanner and dispense form.';
            } finally {
                refreshInFlight = false;
                document.querySelector('button[onclick="refreshSummary()"]')?.removeAttribute('disabled');
            }
        }

        async function refreshSummary() {
            await loadSummary();
        }


        async function submitDispense() {
            if (dispenseInFlight) return;

            const prescriptionId = document.getElementById('prescriptionId').value;
            const barcode = barcodeInput.value;
            const quantity = document.getElementById('quantity').value;
            if (!prescriptionId || !barcode || !quantity) {
                dispenseStatus.textContent = 'Please fill prescription ID, barcode, and quantity.';
                return;
            }

            dispenseInFlight = true;
            dispenseStatus.textContent = 'Submitting…';

            document.querySelectorAll('button[onclick="submitDispense()"], button[onclick="submitDispense()"').forEach(btn => btn.setAttribute('disabled', 'disabled'));


            try {
                const response = await fetch(`/api/v1/prescriptions/${prescriptionId}/dispense-by-barcode`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ barcode, quantity: Number(quantity) })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Dispense request failed');

                dispenseStatus.textContent = `Dispense succeeded • Status: ${data.status || 'updated'}`;
                await loadSummary();
            } catch (error) {
                dispenseStatus.textContent = error.message;
            } finally {
                dispenseInFlight = false;
                document.querySelectorAll('button[onclick="submitDispense()"], button[onclick="submitDispense()"').forEach(btn=>btn.removeAttribute('disabled'));


            }
        }


        function stopScanner() {
            if (window.Quagga) {
                Quagga.stop();
            }
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
            }
            scannerActive = false;
        }

        function toggleScanner() {
            if (scannerActive) {
                stopScanner();
                dispenseStatus.textContent = 'Camera scanner stopped.';
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                dispenseStatus.textContent = 'Camera access is unavailable in this browser.';
                return;
            }
            if (window.Quagga) {
                Quagga.init({
                    inputStream: { name: 'Live', type: 'LiveStream', target: video },
                    decoder: { readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'code_39_reader', 'upc_reader'] },
                    locate: true
                }, function (err) {
                    if (err) {
                        dispenseStatus.textContent = 'Camera could not be started.';
                        return;
                    }
                    scannerActive = true;
                    Quagga.start();
                    Quagga.onDetected(function (result) {
                        const code = result.codeResult.code;
                        barcodeInput.value = code;
                        dispenseStatus.textContent = `Barcode detected: ${code}`;
                    });
                });
            } else {
                dispenseStatus.textContent = 'Barcode library not loaded; you can still enter the code manually.';
            }
        }

        loadSummary();
    </script>
</body>
</html>
