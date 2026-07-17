<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Pharmacy Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pharmacy-pos.css') }}">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .products-table {
            width: 100%;
        }
        
        .products-table th,
        .products-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .price-cell {
            font-weight: 600;
            color: var(--primary);
        }
        
        .stock-warning {
            color: var(--danger);
            font-weight: 600;
        }
        
        .stock-ok {
            color: var(--success);
        }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Products Management</h1>
                <p>Manage pharmacy inventory and pricing</p>
            </div>
            <button class="btn btn-primary" onclick="openProductModal()">➕ Add Product</button>
        </div>

        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="searchProducts()">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="Medicine">Medicine</option>
                    <option value="Supplement">Supplement</option>
                </select>
            </div>

            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Barcode</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Alert Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal-overlay" id="productModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Product</h3>
                <button class="modal-close" onclick="closeProductModal()">×</button>
            </div>
            <form id="productForm">
                <div style="margin-bottom: 16px;">
                    <label>Product Name *</label>
                    <input type="text" name="productName" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Barcode</label>
                    <input type="text" name="barcode">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Category ID</label>
                    <input type="number" name="category_id">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Sales Price *</label>
                    <input type="number" step="0.01" name="sales_price" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Purchase Price</label>
                    <input type="number" step="0.01" name="purchase_with_tax">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Alert Quantity</label>
                    <input type="number" name="alert_qty" value="10">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Wholesale Price</label>
                    <input type="number" step="0.01" name="wholesale_price">
                </div>
                
                <div class="checkout-actions">
                    <button type="submit" class="checkout-btn">Save Product</button>
                    <button type="button" class="secondary-btn" onclick="closeProductModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadProducts() {
            try {
                const response = await fetch('/api/v1/products', {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    renderProducts(data.data || data);
                }
            } catch (error) {
                console.error('Failed to load products:', error);
                document.getElementById('productsTableBody').innerHTML = 
                    '<tr><td colspan="8" class="text-center text-muted">Failed to load products</td></tr>';
            }
        }

        function renderProducts(products) {
            const tbody = document.getElementById('productsTableBody');
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No products found</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(p => {
                const stock = p.stock || p.productStock || 0;
                const alertQty = p.alert_qty || 0;
                const isLowStock = stock <= alertQty;
                
                return `
                <tr>
                    <td>${p.productName}</td>
                    <td><code>${p.barcode || 'N/A'}</code></td>
                    <td>${p.category?.name || '-'}</td>
                    <td class="price-cell">$${Number(p.sales_price || 0).toFixed(2)}</td>
                    <td class="${isLowStock ? 'stock-warning' : 'stock-ok'}">${stock}</td>
                    <td>${alertQty}</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" onclick="editProduct(${p.id})">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteProduct(${p.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `}).join('');
        }

        async function searchProducts() {
            const query = document.getElementById('searchInput').value;
            try {
                const response = await fetch(`/api/v1/products?search=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    renderProducts(data.data || data);
                }
            } catch (error) {
                console.error('Search failed:', error);
            }
        }

        function openProductModal() {
            document.getElementById('productModal').classList.add('active');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
            document.getElementById('productForm').reset();
        }

        async function saveProduct(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('/api/v1/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    closeProductModal();
                    loadProducts();
                } else {
                    const error = await response.json();
                    alert('Failed to save product: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Save failed:', error);
                alert('Failed to save product');
            }
        }

        document.getElementById('productForm').addEventListener('submit', saveProduct);

        document.addEventListener('DOMContentLoaded', loadProducts);
    </script>
</body>
</html>