<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy POS</title>
    <style>
        :root { --bg:#f2f7ff; --card:#ffffff; --text:#0f172a; --muted:#64748b; --primary:#2563eb; --accent:#0f766e; --danger:#dc2626; --border:#dbeafe; --success:#22c55e; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Inter, Arial, sans-serif; background:linear-gradient(135deg,#f8fbff 0%, #eef6ff 100%); color:var(--text); }
        .app-shell { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { display:flex; flex-wrap:wrap; gap:16px; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .header h1 { margin:0 0 6px; font-size:26px; }
        .header p { margin:0; color:var(--muted); }
        .hero-badge { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; font-size:12px; font-weight:700; background:#ecfeff; color:var(--accent); border:1px solid #a7f3d0; }
        .hero-badge::before { content:''; width:8px; height:8px; border-radius:999px; background:var(--accent); display:inline-block; }

        .pos-grid { display:grid; grid-template-columns: 1fr 380px; gap:20px; }

        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:20px; box-shadow:0 10px 30px rgba(15,23,42,.06); }
        .section-title { font-size:15px; font-weight:700; margin:0 0 12px; color:var(--text); }

        /* Scanner Section */
        .scanner-section { margin-bottom:20px; }
        .scanner-inputs { display:grid; grid-template-columns: 1fr 1fr auto; gap:10px; align-items:end; }
        .scanner-box { border:1px dashed var(--border); border-radius:12px; background:#fbfdff; min-height:180px; display:flex; align-items:center; justify-content:center; margin-top:12px; overflow:hidden; }
        #scannerVideo { width:100%; max-height:200px; background:#000; }

        /* Cart Section */
        .cart-section { }
        .cart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
        .cart-count { background:var(--primary); color:white; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .cart-items { max-height:400px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; }
        .cart-item { display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--border); border-radius:12px; background:#fcfdff; transition:all 0.2s; }
        .cart-item:hover { border-color:var(--primary); background:#f8fbff; }
        .cart-item-info { flex:1; min-width:0; }
        .cart-item-name { font-weight:600; font-size:14px; margin-bottom:4px; }
        .cart-item-details { font-size:12px; color:var(--muted); }
        .cart-item-price { font-weight:700; color:var(--primary); font-size:14px; }

        /* Quantity Controls */
        .qty-control { display:flex; align-items:center; gap:8px; }
        .qty-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:white; cursor:pointer; font-weight:700; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .qty-btn:hover { background:var(--primary); color:white; border-color:var(--primary); }
        .qty-display { min-width:40px; text-align:center; font-weight:700; font-size:14px; }

        /* Remove Button */
        .remove-btn { width:28px; height:28px; border-radius:6px; border:none; background:#fee2e2; color:var(--danger); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .remove-btn:hover { background:var(--danger); color:white; }

        /* Checkout Section */
        .checkout-section { position:sticky; top:20px; }
        .checkout-summary { background:#f8fbff; border-radius:12px; padding:16px; margin-bottom:16px; }
        .summary-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px; }
        .summary-row.total { font-weight:700; font-size:18px; color:var(--primary); border-top:1px solid var(--border); padding-top:12px; margin-top:12px; margin-bottom:0; }

        .checkout-actions { display:flex; flex-direction:column; gap:10px; }
        .checkout-btn { background:var(--primary); color:white; border:none; cursor:pointer; font-weight:700; font-size:15px; padding:14px; border-radius:10px; transition:all 0.2s; }
        .checkout-btn:hover { background:#1d4ed8; }
        .checkout-btn:disabled { background:var(--muted); cursor:not-allowed; }
        .secondary-btn { background:var(--accent); color:white; border:none; cursor:pointer; font-weight:700; font-size:14px; padding:12px; border-radius:10px; transition:all 0.2s; }
        .secondary-btn:hover { background:#0d5f56; }

        /* Payment Methods */
        .payment-methods { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:12px; }
        .payment-method { padding:12px; border:2px solid var(--border); border-radius:10px; background:white; cursor:pointer; text-align:center; font-weight:600; font-size:13px; transition:all 0.2s; }
        .payment-method:hover { border-color:var(--primary); background:#f8fbff; }
        .payment-method.selected { border-color:var(--primary); background:#eff6ff; color:var(--primary); }
        .payment-method-icon { font-size:20px; margin-bottom:4px; display:block; }

        /* Modal */
        .modal-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:16px; padding:24px; max-width:500px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-title { font-size:20px; font-weight:700; margin:0; }
        .modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:var(--muted); }
        .modal-close:hover { color:var(--danger); }

        /* Receipt */
        .receipt { background:#f8fbff; border:1px dashed var(--border); border-radius:12px; padding:20px; font-family:monospace; font-size:13px; }
        .receipt-header { text-align:center; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border); }
        .receipt-header h2 { margin:0 0 4px; font-size:16px; }
        .receipt-items { margin:16px 0; }
        .receipt-item { display:flex; justify-content:space-between; margin-bottom:8px; }
        .receipt-item-name { flex:1; }
        .receipt-item-qty { text-align:center; min-width:60px; }
        .receipt-item-price { text-align:right; min-width:80px; }
        .receipt-totals { border-top:1px solid var(--border); padding-top:12px; }
        .receipt-total { display:flex; justify-content:space-between; font-weight:700; font-size:16px; margin-top:8px; }
        .receipt-footer { text-align:center; margin-top:16px; padding-top:12px; border-top:1px solid var(--border); font-size:12px; color:var(--muted); }

        /* Customer Info */
        .customer-info { margin-bottom:16px; }
        .customer-info input { margin-bottom:8px; }

        /* Recent Sales */
        .sale-item { transition:all 0.2s; }
        .sale-item:hover { border-color:var(--primary) !important; background:#f8fbff !important; }

        /* Stats & Forecast */
        .stats-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
        .stat-card { padding:14px; border-radius:12px; background:#f8fbff; border:1px solid var(--border); }
        .stat-card strong { display:block; font-size:22px; margin-bottom:4px; color:var(--primary); }
        .stat-card span { font-size:12px; color:var(--muted); }

        .forecast-list { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; max-height:300px; overflow-y:auto; }
        .forecast-item { border:1px solid var(--border); border-radius:10px; padding:12px; background:#fcfdff; }
        .forecast-item-header { display:flex; justify-content:space-between; align-items:start; gap:10px; margin-bottom:6px; }
        .forecast-item-name { font-weight:600; font-size:13px; }
        .forecast-item-details { font-size:12px; color:var(--muted); }

        /* Status Messages */
        .status { padding:10px 14px; border-radius:10px; font-size:13px; margin-top:10px; }
        .status.info { background:#eff6ff; color:#1d4ed8; }
        .status.success { background:#dcfce7; color:#166534; }
        .status.error { background:#fee2e2; color:#991b1b; }

        /* Form Elements */
        label { display:block; margin-bottom:6px; font-weight:600; font-size:12px; color:var(--text); }
        input, select { width:100%; padding:10px; border-radius:8px; border:1px solid var(--border); font-size:14px; transition:border-color 0.2s; }
        input:focus, select:focus { outline:none; border-color:var(--primary); }

        .muted { color:var(--muted); }

        @media (max-width: 1024px){
            .pos-grid{grid-template-columns:1fr;}
            .checkout-section { position:static; }
            .stats-grid{grid-template-columns:1fr;}
        }
        @media (max-width: 768px){
            .scanner-inputs { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="header">
            <div>
                <h1>Pharmacy POS</h1>
                <p>Scan items, manage cart, and process prescriptions quickly</p>
            </div>
            <div class="hero-badge">Live • Barcode-ready</div>
        </div>

        <div class="pos-grid">
            <!-- Left Column: Scanner & Cart -->
            <div class="left-column">
                <!-- Scanner Section -->
                <div class="card scanner-section">
                    <h3 class="section-title">Add items to cart</h3>
                    <div class="scanner-inputs">
                        <div>
                            <label>Prescription ID</label>
                            <input id="prescriptionId" placeholder="Optional" />
                        </div>
                        <div>
                            <label>Barcode</label>
                            <input id="barcode" placeholder="Scan or type" autofocus />
                        </div>
                        <button onclick="addToCart()" style="margin-top:0; height:42px;">Add</button>
                    </div>
                    <button class="secondary-btn" onclick="toggleScanner()" style="width:auto; margin-top:10px;">📷 Toggle scanner</button>

                    <div class="scanner-box">
                        <video id="scannerVideo" autoplay playsinline muted></video>
                    </div>
                    <div class="status info" id="scanStatus">Ready to scan items</div>
                </div>

                <!-- Cart Section -->
                <div class="card cart-section">
                    <div class="cart-header">
                        <h3 class="section-title" style="margin:0;">Scanned items</h3>
                        <div>
                            <span class="cart-count" id="cartCount">0 items</span>
                            <button onclick="clearCart()" style="background:none; border:none; color:var(--danger); font-size:12px; cursor:pointer; margin-left:8px;">Clear all</button>
                        </div>
                    </div>
                    <div class="cart-items" id="cartItems">
                        <div class="muted" style="text-align:center; padding:20px;">No items in cart. Scan or add items above.</div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Checkout & Info -->
            <div class="right-column">
                <!-- Checkout Section -->
                <div class="card checkout-section">
                    <h3 class="section-title">Checkout</h3>

                    <div class="customer-info">
                        <label>Customer Name (Optional)</label>
                        <input id="customerName" placeholder="Enter customer name" />
                    </div>

                    <label>Payment Method</label>
                    <div class="payment-methods">
                        <div class="payment-method selected" onclick="selectPaymentMethod('cash')" data-method="cash">
                            <span class="payment-method-icon">💵</span>
                            Cash
                        </div>
                        <div class="payment-method" onclick="selectPaymentMethod('card')" data-method="card">
                            <span class="payment-method-icon">💳</span>
                            Card
                        </div>
                        <div class="payment-method" onclick="selectPaymentMethod('insurance')" data-method="insurance">
                            <span class="payment-method-icon">🏥</span>
                            Insurance
                        </div>
                    </div>

                    <div class="checkout-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (8%)</span>
                            <span id="tax">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount</span>
                            <span id="discount">$0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="total">$0.00</span>
                        </div>
                    </div>

                    <div class="checkout-actions">
                        <button class="checkout-btn" onclick="initiateCheckout()" id="checkoutBtn" disabled>Complete Sale</button>
                        <button class="secondary-btn" onclick="holdOrder()">Hold Order</button>
                    </div>

                    <div class="status info" id="checkoutStatus">Add items to begin checkout</div>
                </div>

                <!-- Stats Section -->
                <div class="card" style="margin-top:20px;">
                    <h3 class="section-title">Operations snapshot</h3>
                    <div class="stats-grid" id="stats"></div>
                    <div class="status info" id="summaryStatus">Loading summary…</div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
                        <h3 class="section-title" style="margin:0;">Forecast</h3>
                        <button onclick="refreshSummary()" style="width:auto; padding:8px 12px; border-radius:8px; background:var(--accent); color:white; border:none; cursor:pointer; font-size:12px;">Refresh</button>
                    </div>
                    <div class="muted" id="forecastMeta" style="margin-top:6px; font-size:12px;"></div>
                    <ul class="forecast-list" id="forecastList"></ul>
                </div>

                <!-- Recent Sales Section -->
                <div class="card" style="margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 class="section-title" style="margin:0;">Recent Sales</h3>
                        <button onclick="loadRecentSales()" style="width:auto; padding:8px 12px; border-radius:8px; background:var(--primary); color:white; border:none; cursor:pointer; font-size:12px;">Refresh</button>
                    </div>
                    <div class="muted" style="margin-top:6px; font-size:12px;">Latest completed transactions</div>
                    <div id="recentSales" style="margin-top:12px; max-height:250px; overflow-y:auto;">
                        <div class="muted" style="text-align:center; padding:20px;">Loading recent sales...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sale Confirmation Modal -->
    <div class="modal-overlay" id="saleModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Sale</h3>
                <button class="modal-close" onclick="closeSaleModal()">×</button>
            </div>
            <div id="saleModalContent">
                <!-- Dynamic content will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal-overlay" id="receiptModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Sale Completed</h3>
                <button class="modal-close" onclick="closeReceiptModal()">×</button>
            </div>
            <div id="receiptModalContent">
                <!-- Receipt will be inserted here -->
            </div>

            <div style="margin-top:16px;">
                <div class="customer-info" style="margin-bottom:10px;">
                    <label for="customerPhoneForWhatsApp">Customer Phone (for WhatsApp)</label>
                    <input id="customerPhoneForWhatsApp" placeholder="e.g. 01234567890" />
                </div>
            </div>

            <div class="checkout-actions" style="margin-top:20px;">
                <button class="checkout-btn" onclick="closeReceiptModal()">New Sale</button>
                <button class="secondary-btn" onclick="printReceipt()">🖨️ Print Receipt</button>
                <button class="secondary-btn" style="background:#1d4ed8;" onclick="sendReceiptViaWhatsApp()">📱 Send via WhatsApp</button>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        // Cart State
        let cart = [];
        let scannerActive = false;
        let refreshInFlight = false;
        let selectedPaymentMethod = 'cash';
        let currentSale = null;

        // DOM Elements
        const summaryStatus = document.getElementById('summaryStatus');
        const stats = document.getElementById('stats');
        const forecastList = document.getElementById('forecastList');
        const scanStatus = document.getElementById('scanStatus');
        const checkoutStatus = document.getElementById('checkoutStatus');
        const video = document.getElementById('scannerVideo');
        const barcodeInput = document.getElementById('barcode');
        const prescriptionIdInput = document.getElementById('prescriptionId');
        const customerNameInput = document.getElementById('customerName');
        const cartItemsContainer = document.getElementById('cartItems');
        const cartCount = document.getElementById('cartCount');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const saleModal = document.getElementById('saleModal');
        const receiptModal = document.getElementById('receiptModal');

        // Cart Functions
        // Fetch product by barcode from API
        async function fetchProductByBarcode(barcode) {
            try {
                const response = await fetch(`/api/v1/products?barcode=${encodeURIComponent(barcode)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    const data = await response.json();
                    // Handle paginated response or direct product
                    const product = data.data?.data?.[0] || data.data?.[0] || data[0];
                    if (product) {
                        return {
                            id: product.id,
                            name: product.productName || product.name,
                            price: parseFloat(product.sales_price || product.price || 0),
                            stock: parseInt(product.stocks?.sum_productStock || product.productStock || product.stock || 0),
                            barcode: product.barcode
                        };
                    }
                }
            } catch (error) {
                console.error('Fetch product error:', error);
            }
            return null;
        }

        // Fetch product by barcode using direct lookup
        async function fetchProductDirect(barcode) {
            try {
                // Try to find product via search endpoint
                const response = await fetch(`/api/v1/products?search=${encodeURIComponent(barcode)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    const data = await response.json();
                    const products = data.data?.data || data.data || data || [];
                    const product = products.find(p => p.barcode === barcode) || products[0];

                    if (product) {
                        // Get stock for this product
                        const stockResponse = await fetch(`/api/v1/product-stocks?product_id=${product.id}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        let stock = 0;
                        if (stockResponse.ok) {
                            const stockData = await stockResponse.json();
                            stock = stockData.data?.sum_productStock || stockData.sum_productStock || 0;
                        }

                        return {
                            id: product.id,
                            name: product.productName || product.name,
                            price: parseFloat(product.sales_price || product.price || 0),
                            stock: parseInt(stock),
                            barcode: product.barcode
                        };
                    }
                }
            } catch (error) {
                console.error('Fetch product direct error:', error);
            }
            return null;
        }

        // Add item to cart via API
        async function addToCart() {
            const barcode = barcodeInput.value.trim();
            const prescriptionId = prescriptionIdInput.value.trim();

            if (!barcode) {
                showStatus(scanStatus, 'Please enter or scan a barcode', 'error');
                return;
            }

            showStatus(scanStatus, 'Fetching product...', 'info');

            // Try to fetch product from API
            let product = await fetchProductByBarcode(barcode);

            if (!product) {
                product = await fetchProductDirect(barcode);
            }

            // Check if item already exists in cart
            const existingItem = cart.find(item => item.barcode === barcode);

            if (existingItem) {
                existingItem.quantity += 1;
                showStatus(scanStatus, 'Quantity updated for existing item', 'success');
            } else if (product) {
                cart.push({
                    id: Date.now() + Math.random(),
                    productId: product.id,
                    barcode: barcode,
                    name: product.name,
                    price: product.price,
                    quantity: 1,
                    prescriptionId: prescriptionId || null,
                    stock: product.stock
                });

                showStatus(scanStatus, `Added ${product.name} to cart`, 'success');
            } else {
                // Fallback to demo data if API not available
                const demoProduct = {
                    name: `Product ${barcode}`,
                    price: Math.floor(Math.random() * 20) + 5,
                    stock: Math.floor(Math.random() * 50) + 10
                };

                cart.push({
                    id: Date.now() + Math.random(),
                    barcode: barcode,
                    name: demoProduct.name,
                    price: demoProduct.price,
                    quantity: 1,
                    prescriptionId: prescriptionId || null,
                    stock: demoProduct.stock
                });

                showStatus(scanStatus, 'Item added to cart (demo mode)', 'success');
            }

            // Clear inputs
            barcodeInput.value = '';
            prescriptionIdInput.value = '';
            barcodeInput.focus();

            renderCart();
        }

        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            renderCart();
            showStatus(checkoutStatus, 'Item removed from cart', 'info');
        }

        function updateQuantity(itemId, change) {
            const item = cart.find(item => item.id === itemId);
            if (item) {
                const newQuantity = item.quantity + change;

                // Validate stock when increasing quantity
                if (change > 0 && item.stock !== undefined && newQuantity > item.stock) {
                    showStatus(checkoutStatus, `Only ${item.stock} units available in stock`, 'error');
                    return;
                }

                item.quantity = newQuantity;
                if (item.quantity <= 0) {
                    removeFromCart(itemId);
                } else {
                    renderCart();
                }
            }
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm('Are you sure you want to clear all items from the cart?')) {
                cart = [];
                renderCart();
                showStatus(checkoutStatus, 'Cart cleared', 'info');
            }
        }

        function renderCart() {
            // Update cart count
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;

            // Enable/disable checkout button
            checkoutBtn.disabled = cart.length === 0;

            // Render cart items
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<div class="muted" style="text-align:center; padding:20px;">No items in cart. Scan or add items above.</div>';
                updateTotals();
                return;
            }

            cartItemsContainer.innerHTML = cart.map(item => {
                const stockInfo = item.stock !== undefined ? `<span style="margin-left:8px; color:${item.stock < 10 ? 'var(--danger)' : 'var(--muted)'}">${item.stock} in stock</span>` : '';
                return `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-details">Barcode: ${item.barcode}${stockInfo}</div>
                        <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                    </div>
                    <div class="qty-control">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">−</button>
                        <div class="qty-display">${item.quantity}</div>
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    </div>
                    <button class="remove-btn" onclick="removeFromCart(${item.id})">×</button>
                </div>
            `}).join('');

            updateTotals();
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.08; // 8% tax
            const discount = 0; // Could implement discount logic
            const total = subtotal + tax - discount;

            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('discount').textContent = `$${discount.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;

            if (cart.length > 0) {
                showStatus(checkoutStatus, `${cart.length} item(s) in cart - Ready for checkout`, 'info');
            } else {
                showStatus(checkoutStatus, 'Add items to begin checkout', 'info');
            }
        }

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
                if (el.dataset.method === method) {
                    el.classList.add('selected');
                }
            });
        }

        function initiateCheckout() {
            if (cart.length === 0) {
                showStatus(checkoutStatus, 'Cart is empty', 'error');
                return;
            }

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.08;
            const total = subtotal + tax;
            const customerName = customerNameInput.value.trim() || 'Guest';

            // Show confirmation modal
            const modalContent = document.getElementById('saleModalContent');
            modalContent.innerHTML = `
                <div style="margin-bottom:16px;">
                    <p><strong>Customer:</strong> ${customerName}</p>
                    <p><strong>Payment Method:</strong> ${selectedPaymentMethod.charAt(0).toUpperCase() + selectedPaymentMethod.slice(1)}</p>
                    <p><strong>Items:</strong> ${cart.length}</p>
                </div>

                <div style="background:#f8fbff; padding:12px; border-radius:8px; margin-bottom:16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span>Subtotal:</span>
                        <span>$${subtotal.toFixed(2)}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span>Tax (8%):</span>
                        <span>$${tax.toFixed(2)}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-weight:700; font-size:16px; border-top:1px solid var(--border); padding-top:8px; margin-top:8px;">
                        <span>Total:</span>
                        <span>$${total.toFixed(2)}</span>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <h4 style="margin:0 0 8px;">Items:</h4>
                    ${cart.map(item => `
                        <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                            <span>${item.name} x${item.quantity}</span>
                            <span>$${(item.price * item.quantity).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>

                <div class="checkout-actions">
                    <button class="checkout-btn" onclick="confirmSale()">Confirm & Complete Sale</button>
                    <button class="secondary-btn" onclick="closeSaleModal()">Cancel</button>
                </div>
            `;

            saleModal.classList.add('active');
        }

        function closeSaleModal() {
            saleModal.classList.remove('active');
        }

        async function validateCartInventory() {
            if (cart.length === 0) return { all_available: true, items: [] };

            try {
                const response = await fetch('/api/v1/pos/sales/validate-inventory', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        items: cart.map(item => ({
                            barcode: item.barcode,
                            quantity: item.quantity
                        }))
                    })
                });

                if (response.ok) {
                    return await response.json();
                } else {
                    // If validation fails, assume available for demo mode
                    console.log('Inventory validation unavailable, proceeding with sale');
                    return { all_available: true, items: [] };
                }
            } catch (error) {
                console.error('Inventory validation failed:', error);
                // Assume available if validation fails
                return { all_available: true, items: [] };
            }
        }

        async function confirmSale() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.08;
            const total = subtotal + tax;
            const customerName = customerNameInput.value.trim() || 'Guest';

            // Validate inventory first
            showStatus(checkoutStatus, 'Validating inventory...', 'info');

            const inventoryValidation = await validateCartInventory();

            if (!inventoryValidation.all_available) {
                const unavailableItems = inventoryValidation.items.filter(item => !item.available);
                const message = unavailableItems.map(item =>
                    `${item.name || item.barcode}: requested ${item.requested_quantity}, available ${item.available_quantity}`
                ).join('\n');

                alert(`Insufficient inventory for the following items:\n\n${message}\n\nPlease adjust quantities or contact inventory manager.`);
                showStatus(checkoutStatus, 'Inventory validation failed - adjust quantities', 'error');
                return;
            }

            // Prepare sale data
            const saleData = {
                customer_name: customerName,
                status: 'completed',
                subtotal: subtotal,
                tax_amount: tax,
                total_amount: total,
                payment_method: selectedPaymentMethod,
                items: cart.map(item => ({
                    barcode: item.barcode,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    prescription_id: item.prescriptionId
                }))
            };

            try {
                showStatus(checkoutStatus, 'Processing sale and updating inventory...', 'info');

                // Call the real API
                const response = await fetch('/api/v1/pos/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(saleData)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Sale processing failed');
                }

                const sale = await response.json();

                currentSale = sale;
                closeSaleModal();
                showReceipt(sale);

                // Process prescription dispenses if applicable
                for (const item of cart) {
                    if (item.prescriptionId) {
                        try {
                            const dispenseResponse = await fetch(`/api/v1/prescriptions/${item.prescriptionId}/dispense-by-barcode`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    barcode: item.barcode,
                                    quantity: item.quantity
                                })
                            });
                            if (dispenseResponse.ok) {
                                console.log(`Dispensed ${item.quantity}x ${item.name} for prescription ${item.prescriptionId}`);
                            }
                        } catch (e) {
                            console.error('Dispense failed:', e);
                        }
                    }
                }

                // Clear cart after successful sale
                cart = [];
                customerNameInput.value = '';
                renderCart();

                // Refresh summary to update stock
                loadSummary();

                // Refresh recent sales
                loadRecentSales();

                showStatus(checkoutStatus, 'Sale completed successfully! Inventory updated.', 'success');

            } catch (error) {
                console.error('Sale failed:', error);
                showStatus(checkoutStatus, `Sale failed: ${error.message}`, 'error');
                closeSaleModal();
            }
        }

        function showReceipt(sale) {
            const receiptContent = document.getElementById('receiptModalContent');
            const date = new Date(sale.created_at || Date.now()).toLocaleString();

            receiptContent.innerHTML = `
                <div class="receipt">
                    <div class="receipt-header">
                        <h2>PHARMACY RECEIPT</h2>
                        <p>Order #${sale.id}</p>
                        <p>${date}</p>
                    </div>

                    <div style="margin-bottom:12px;">
                        <p><strong>Customer:</strong> ${sale.customer_name || 'Guest'}</p>
                        <p><strong>Payment:</strong> ${sale.payment_method?.toUpperCase() || 'CASH'}</p>
                    </div>

                    <div class="receipt-items">
                        <div style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:8px;">
                            <span class="receipt-item-name">Item</span>
                            <span class="receipt-item-qty">Qty</span>
                            <span class="receipt-item-price">Price</span>
                        </div>
                        ${(() => {
                            // Use sale_items relationship if available, otherwise fall back to items JSON or cart
                            const items = sale.sale_items && Array.isArray(sale.sale_items) ? sale.sale_items :
                                           (Array.isArray(sale.items) ? sale.items : (sale.items ? JSON.parse(sale.items) : cart));
                            return items.map(item => `
                                <div class="receipt-item">
                                    <span class="receipt-item-name">${item.name}</span>
                                    <span class="receipt-item-qty">${item.quantity}</span>
                                    <span class="receipt-item-price">$${((item.unit_price || item.price) * item.quantity).toFixed(2)}</span>
                                </div>
                            `).join('');
                        })()}
                    </div>

                    <div class="receipt-totals">
                        <div style="display:flex; justify-content:space-between;">
                            <span>Subtotal:</span>
                            <span>$${Number(sale.subtotal || 0).toFixed(2)}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span>Tax (8%):</span>
                            <span>$${Number(sale.tax_amount || 0).toFixed(2)}</span>
                        </div>
                        <div class="receipt-total">
                            <span>TOTAL:</span>
                            <span>$${Number(sale.total_amount || 0).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="receipt-footer">
                        <p>Thank you for your purchase!</p>
                        <p>For questions, contact pharmacy support</p>
                    </div>
                </div>
            `;

            receiptModal.classList.add('active');
        }

        function closeReceiptModal() {
            receiptModal.classList.remove('active');
            currentSale = null;
        }

        async function sendReceiptViaWhatsApp() {
            if (!currentSale) return;

            const phoneInputId = 'customerPhoneForWhatsApp';
            const phoneEl = document.getElementById(phoneInputId);
            const customerPhone = phoneEl ? (phoneEl.value || '').trim() : '';

            if (!customerPhone) {
                alert('Please enter customer phone number to send via WhatsApp');
                return;
            }

            // Convert receipt HTML to image (lightweight fallback).
            // NOTE: This uses a simple canvas render if html2canvas is available.
            // Otherwise, it will error and ask user to enable html2canvas.
            if (typeof window.html2canvas !== 'function') {
                alert('html2canvas is required for WhatsApp sending from POS. Please ensure it is available on the page.');
                return;
            }

            const receiptEl = document.querySelector('#receiptModalContent .receipt');
            if (!receiptEl) {
                alert('Receipt content not found');
                return;
            }

            try {
                showStatus(checkoutStatus, 'Preparing receipt image…', 'info');
                const canvas = await window.html2canvas(receiptEl, { scale: 2, useCORS: true });

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                if (!blob) throw new Error('Could not generate receipt image');

                const formData = new FormData();
                formData.append('invoice_image', blob, `receipt_${currentSale.id}.png`);
                formData.append('customer_phone', customerPhone);
                formData.append('invoice_id', currentSale.id);

                const res = await fetch('/api/v1/send-invoice-whatsapp', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    throw new Error(data.message || 'WhatsApp send failed');
                }

                showStatus(checkoutStatus, 'Receipt sent via WhatsApp successfully', 'success');
                alert('تم إرسال الفاتورة عبر الواتساب بنجاح');
            } catch (e) {
                console.error(e);
                showStatus(checkoutStatus, `WhatsApp send failed: ${e.message}`, 'error');
                alert('فشل إرسال الفاتورة عبر الواتساب');
            }
        }

        function printReceipt() {
            if (currentSale) {
                // In production, this would trigger actual printing
                const receiptContent = document.getElementById('receiptModalContent').innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Receipt #${currentSale.id}</title>
                        <style>
                            body { font-family: monospace; padding: 20px; }
                            .receipt { border: 1px dashed #ccc; padding: 20px; }
                            .receipt-header { text-align: center; margin-bottom: 20px; }
                            .receipt-items { margin: 20px 0; }
                            .receipt-item { display: flex; justify-content: space-between; margin-bottom: 8px; }
                            .receipt-totals { border-top: 1px solid #ccc; padding-top: 20px; }
                            .receipt-total { font-weight: bold; font-size: 16px; }
                        </style>
                    </head>
                    <body>${receiptContent}</body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }

        function holdOrder() {
            if (cart.length === 0) {
                showStatus(checkoutStatus, 'Cart is empty', 'error');
                return;
            }

            // In production, this would save the order to database
            showStatus(checkoutStatus, 'Order held for later', 'success');

            // Store in localStorage for persistence
            localStorage.setItem('heldOrder', JSON.stringify(cart));

            cart = [];
            renderCart();
        }

        // Status Messages
        function showStatus(element, message, type = 'info') {
            element.textContent = message;
            element.className = `status ${type}`;
        }

        // Scanner Functions
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
                showStatus(scanStatus, 'Camera scanner stopped', 'info');
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showStatus(scanStatus, 'Camera access is unavailable in this browser', 'error');
                return;
            }
            if (window.Quagga) {
                Quagga.init({
                    inputStream: { name: 'Live', type: 'LiveStream', target: video },
                    decoder: { readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'code_39_reader', 'upc_reader'] },
                    locate: true
                }, function (err) {
                    if (err) {
                        showStatus(scanStatus, 'Camera could not be started', 'error');
                        return;
                    }
                    scannerActive = true;
                    Quagga.start();
                    showStatus(scanStatus, 'Scanner active - point camera at barcode', 'success');
                    Quagga.onDetected(function (result) {
                        const code = result.codeResult.code;
                        barcodeInput.value = code;
                        showStatus(scanStatus, `Barcode detected: ${code}`, 'success');
                        // Auto-add to cart after short delay
                        setTimeout(() => {
                            if (barcodeInput.value === code) {
                                addToCart();
                            }
                        }, 500);
                    });
                });
            } else {
                showStatus(scanStatus, 'Barcode library not loaded; you can still enter the code manually', 'error');
            }
        }

        // Summary & Forecast Functions
        function renderDemoSummary() {
            stats.innerHTML = `
                <div class="stat-card"><strong>0</strong><span class="muted">Pending prescriptions</span></div>
                <div class="stat-card"><strong>0</strong><span class="muted">Low stock alerts</span></div>
                <div class="stat-card"><strong>0</strong><span class="muted">Forecast items</span></div>
            `;
            forecastList.innerHTML = '<li class="forecast-item">No forecast data available yet. The page will display live data once the API is reachable.</li>';
            const meta = document.getElementById('forecastMeta');
            if (meta) meta.textContent = 'Tip: refresh to apply latest dispensed history (last 30 days).';

        }

        function renderForecastSkeleton() {
            forecastList.innerHTML = [0, 1, 2, 3, 4].map(() => `
                <li class="forecast-item" style="opacity:.8">
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
                    <div class="stat-card"><strong>${data.pending_prescriptions_count ?? 0}</strong><span class="muted">Pending prescriptions</span></div>
                    <div class="stat-card"><strong>${(data.low_stock_products || []).length}</strong><span class="muted">Low stock alerts</span></div>
                    <div class="stat-card"><strong>${(data.forecast || []).length}</strong><span class="muted">Forecast items</span></div>
                `;

                const items = (data.forecast || []).slice(0, 10);
                forecastList.innerHTML = items.map(item => {
                    const name = item.product_name ? `${item.product_name}` : `Product #${item.product_id || 'n/a'}`;
                    const demand = item.average_daily_demand ?? 0;
                    const reorder = item.recommended_reorder_quantity ?? 0;
                    const safety = item.safety_stock ?? null;
                    const confidence = confidencePill(item.confidence);
                    const trend = item.trend ? ` • Trend: ${item.trend}` : '';
                    const barcode = item.barcode ? ` • Barcode: ${item.barcode}` : '';

                    return `
                        <li class="forecast-item">
                            <div class="forecast-item-header">
                                <div class="forecast-item-name">${name}</div>
                                ${confidence}
                            </div>
                            <div class="forecast-item-details">
                                Demand ${demand} / day • Reorder ${reorder}
                                ${safety !== null ? `• Safety ${safety}` : ''}${trend}${barcode}
                            </div>
                        </li>
                    `;
                }).join('');

                summaryStatus.textContent = `Summary loaded • ${data.generated_at || ''}`;
            } catch (error) {
                renderDemoSummary();
                summaryStatus.textContent = 'Demo mode: API unavailable. You can still use the scanner and cart.';
            } finally {
                refreshInFlight = false;
                document.querySelector('button[onclick="refreshSummary()"]')?.removeAttribute('disabled');
            }
        }

        async function refreshSummary() {
            await loadSummary();
        }

        async function loadRecentSales() {
            const recentSalesContainer = document.getElementById('recentSales');

            try {
                const response = await fetch('/api/v1/pos/sales', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    const sales = await response.json();

                    if (sales.length === 0) {
                        recentSalesContainer.innerHTML = '<div class="muted" style="text-align:center; padding:20px;">No sales recorded yet</div>';
                        return;
                    }

                    recentSalesContainer.innerHTML = sales.slice(0, 5).map(sale => {
                        const date = new Date(sale.created_at).toLocaleString();
                        // Use sale_items relationship if available, otherwise fall back to items JSON
                        const items = sale.sale_items && Array.isArray(sale.sale_items) ? sale.sale_items :
                                       (Array.isArray(sale.items) ? sale.items : (sale.items ? JSON.parse(sale.items) : []));
                        const itemCount = items.length > 0 ? items.reduce((sum, item) => sum + (item.quantity || 1), 0) : 0;

                        return `
                            <div class="sale-item" style="padding:12px; border:1px solid var(--border); border-radius:10px; background:#fcfdff; margin-bottom:8px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <strong>#${sale.id}</strong>
                                    <span style="font-size:12px; color:var(--muted);">${date}</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; font-size:13px;">
                                    <span>${sale.customer_name || 'Guest'}</span>
                                    <span style="font-weight:700; color:var(--primary);">$${Number(sale.total_amount || 0).toFixed(2)}</span>
                                </div>
                                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                                    ${itemCount} item(s) • ${sale.payment_method?.toUpperCase() || 'CASH'}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    recentSalesContainer.innerHTML = '<div class="muted" style="text-align:center; padding:20px;">Unable to load sales data</div>';
                }
            } catch (error) {
                console.error('Failed to load recent sales:', error);
                recentSalesContainer.innerHTML = '<div class="muted" style="text-align:center; padding:20px;">Sales data unavailable</div>';
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement === barcodeInput) {
                addToCart();
            }
        });

        // Initialize
        loadSummary();
        renderCart();
        loadRecentSales();

        // Check for held order
        const heldOrder = localStorage.getItem('heldOrder');
        if (heldOrder) {
            try {
                const parsedOrder = JSON.parse(heldOrder);
                if (confirm('You have a held order. Would you like to restore it?')) {
                    cart = parsedOrder;
                    renderCart();
                    showStatus(checkoutStatus, 'Held order restored', 'success');
                }
                localStorage.removeItem('heldOrder');
            } catch (e) {
                console.error('Error parsing held order:', e);
            }
        }
    </script>
</body>
</html>
