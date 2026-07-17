<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Admin Panel</title>
    <link rel="stylesheet" href="{{ asset('css/pharmacy-pos.css') }}">
    <style>
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar select, .filter-bar input {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background: var(--muted); color: white; }
        .status-pending { background: var(--warning); color: white; }
        .status-approved { background: var(--primary); color: white; }
        .status-sent { background: var(--info); color: white; }
        .status-received { background: var(--success); color: white; }
        .status-cancelled { background: var(--danger); color: white; }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Purchase Orders</h1>
                <p>Manage purchase orders from suppliers</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn" style="background: var(--muted); color: white;">← Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="filter-bar">
                <select id="statusFilter" onchange="loadPurchaseOrders()">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="sent">Sent</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input type="text" id="searchFilter" placeholder="Search PO number or supplier..." onkeyup="debounceSearch()">
                <input type="date" id="dateFromFilter" onchange="loadPurchaseOrders()">
                <input type="date" id="dateToFilter" onchange="loadPurchaseOrders()">
                <button class="btn" onclick="openCreateModal()">+ New Purchase Order</button>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <h3 class="card-title">Purchase Orders List</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Delivery Date</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrdersTable">
                        <tr>
                            <td colspan="8" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination" style="padding: 16px; text-align: center;"></div>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 50px auto; padding: 24px; border-radius: 16px; max-width: 800px; max-height: 80vh; overflow-y: auto;">
            <div class="card-header">
                <h3 class="card-title">Create Purchase Order</h3>
                <button onclick="closeCreateModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form id="createPOForm" onsubmit="createPurchaseOrder(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label>Supplier *</label>
                        <select name="supplier_id" id="supplierSelect" required class="form-control">
                            <option value="">Select Supplier</option>
                        </select>
                    </div>
                    <div>
                        <label>Branch *</label>
                        <select name="branch_id" id="branchSelect" required class="form-control">
                            <option value="">Select Branch</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_delivery_date" class="form-control">
                </div>
                <div id="itemsContainer" style="margin-bottom: 16px;">
                    <label>Items</label>
                    <div id="itemsList"></div>
                    <button type="button" class="btn" onclick="addItemRow()" style="margin-top: 8px;">+ Add Item</button>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn">Create Purchase Order</button>
            </form>
        </div>
    </div>

    <script>
        let searchTimeout;
        
        async function loadPurchaseOrders(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    status: document.getElementById('statusFilter').value,
                    search: document.getElementById('searchFilter').value,
                    date_from: document.getElementById('dateFromFilter').value,
                    date_to: document.getElementById('dateToFilter').value
                });
                
                const response = await fetch('/api/v1/purchase-orders?' + params, {
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                renderPurchaseOrders(data.data || data);
                renderPagination(data);
            } catch (error) {
                console.error('Failed to load purchase orders:', error);
            }
        }

        function renderPurchaseOrders(orders) {
            const tbody = document.getElementById('purchaseOrdersTable');
            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No purchase orders found</td></tr>';
                return;
            }
            
            tbody.innerHTML = orders.map(o => `
                <tr>
                    <td>${o.po_number || '#' + o.id}</td>
                    <td>${o.supplier?.name || 'N/A'}</td>
                    <td>$${Number(o.total || 0).toFixed(2)}</td>
                    <td><span class="status-badge status-${o.status}">${o.status}</span></td>
                    <td>${o.expected_delivery_date ? new Date(o.expected_delivery_date).toLocaleDateString() : 'N/A'}</td>
                    <td>${o.created_by?.name || 'N/A'}</td>
                    <td>${new Date(o.created_at).toLocaleDateString()}</td>
                    <td>
                        <button onclick="viewPO(${o.id})" class="btn" style="padding: 4px 8px; font-size: 12px;">View</button>
                        ${o.status === 'draft' || o.status === 'pending' ? `<button onclick="approvePO(${o.id})" class="btn" style="padding: 4px 8px; font-size: 12px; margin-left: 4px;">Approve</button>` : ''}
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const pagination = document.getElementById('pagination');
            if (data.last_page && data.last_page > 1) {
                let html = '';
                for (let i = 1; i <= data.last_page; i++) {
                    html += `<button onclick="loadPurchaseOrders(${i})" class="btn" style="margin: 0 4px; padding: 6px 12px;">${i}</button>`;
                }
                pagination.innerHTML = html;
            }
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadPurchaseOrders, 300);
        }

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function addItemRow() {
            const container = document.getElementById('itemsList');
            const div = document.createElement('div');
            div.innerHTML = `
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <input type="number" placeholder="Product ID" name="items[][product_id]" required>
                    <input type="number" placeholder="Qty" name="items[][quantity_ordered]" required>
                    <input type="number" placeholder="Unit Cost" name="items[][unit_cost]" step="0.01" required>
                    <input type="number" placeholder="Discount" name="items[][discount]" step="0.01">
                </div>
            `;
            container.appendChild(div);
        }

        async function createPurchaseOrder(e) {
            e.preventDefault();
            const form = e.target;
            const data = {};
            new FormData(form).forEach((value, key) => {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            });
            
            try {
                const response = await fetch('/api/v1/purchase-orders', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    closeCreateModal();
                    loadPurchaseOrders();
                }
            } catch (error) {
                console.error('Failed to create PO:', error);
            }
        }

        async function approvePO(id) {
            try {
                const response = await fetch(`/api/v1/purchase-orders/${id}/approve`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                if (response.ok) {
                    loadPurchaseOrders();
                }
            } catch (error) {
                console.error('Failed to approve PO:', error);
            }
        }

        function viewPO(id) {
            window.location.href = `/api/v1/purchase-orders/${id}`;
        }

        document.addEventListener('DOMContentLoaded', loadPurchaseOrders);
    </script>
</body>
</html>