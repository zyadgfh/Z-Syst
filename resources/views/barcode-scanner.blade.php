<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Scanner</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #0f172a; }
        .card { max-width: 720px; margin: 0 auto; background: white; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(15,23,42,.08); }
        input, button { width: 100%; padding: 12px; margin-top: 12px; border-radius: 8px; border: 1px solid #cbd5e1; }
        button { background: #2563eb; color: white; border: none; cursor: pointer; }
        .status { margin-top: 16px; padding: 12px; border-radius: 8px; background: #eff6ff; }
        video { width: 100%; border-radius: 10px; margin-top: 12px; background: black; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Barcode-based prescription dispensing</h2>
        <p>Scan or type a barcode to dispense a prescribed item.</p>

        <label for="prescriptionId">Prescription ID</label>
        <input id="prescriptionId" type="number" placeholder="Prescription ID" />

        <label for="barcode">Barcode</label>
        <input id="barcode" type="text" placeholder="Scan or type barcode" />

        <label for="quantity">Quantity</label>
        <input id="quantity" type="number" min="1" value="1" />

        <button onclick="submitDispense()">Dispense</button>

        <video id="video" autoplay playsinline></video>

        <div id="status" class="status">Waiting for input…</div>
    </div>

    <script>
        const video = document.getElementById('video');
        const barcodeInput = document.getElementById('barcode');
        const status = document.getElementById('status');

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                status.textContent = 'Camera ready. Point at a barcode.';
            } catch (error) {
                status.textContent = 'Camera unavailable. You can still type the barcode manually.';
            }
        }

        async function submitDispense() {
            const prescriptionId = document.getElementById('prescriptionId').value;
            const barcode = document.getElementById('barcode').value;
            const quantity = document.getElementById('quantity').value;

            if (!prescriptionId || !barcode || !quantity) {
                status.textContent = 'Please provide prescription ID, barcode, and quantity.';
                return;
            }

            status.textContent = 'Submitting dispense request…';

            try {
                const response = await fetch(`/api/v1/prescriptions/${prescriptionId}/dispense-by-barcode`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ barcode, quantity: Number(quantity) })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Request failed');
                }

                status.textContent = `Dispense succeeded. Status: ${data.status || 'updated'}`;
            } catch (error) {
                status.textContent = error.message;
            }
        }

        startCamera();
    </script>
</body>
</html>
