<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy Operations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
        .shell { max-width: 960px; margin: 32px auto; padding: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 8px 24px rgba(15,23,42,.08); }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
        code { background: #eff6ff; padding: 2px 6px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="shell">
        <h1>Pharmacy operations center</h1>
        <p>Use these entry points to manage prescriptions, barcode dispensing, and demand forecasting.</p>

        <div class="grid">
            <div class="card">
                <h3>Electronic prescriptions</h3>
                <p>Create and review prescriptions through the API.</p>
                <a href="/api/v1/prescriptions">Open prescriptions API</a>
            </div>
            <div class="card">
                <h3>Barcode scanner</h3>
                <p>Scan or type a barcode to dispense an item attached to a prescription.</p>
                <a href="/pharmacy/barcode-scanner">Open scanner page</a>
            </div>
            <div class="card">
                <h3>Demand forecast</h3>
                <p>View a simple reorder recommendation based on recent prescription demand.</p>
                <a href="/api/v1/pharmacy/demand-forecast">Open forecast API</a>
            </div>
        </div>
    </div>
</body>
</html>
