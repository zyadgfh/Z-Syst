<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfers - Admin Panel</title>
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
        .status-pending { background: var(--warning); color: white; }
        .status-approved { background: var(--primary); color: white; }
        .status-shipped { background: var(--info); color: white; }
        .status-received { background: var(--success); color: white; }
        .status-rejected, .status-cancelled { background: var(--danger); color: white; }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Stock Transfers</h1>
                <p>Manage stock transfer requests between branches</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn" style="background: var(--muted); color: white;">← Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="filter-bar">
                <select id="statusFilter" onchange="loadStockTransfers()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="shipped">Shipped</option>
                    <option value="received">Received</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input type="date" id="dateFromFilter" onchange="loadStockTransfers()">
                <input type="date" id="dateToFilter" onchange="loadStockTransfers()">
                <input type="text" id="searchFilter" placeholder="Search..." onkeyup="debounceSearch()">
                <button class="btn" onclick="openCreateModal()">+ New Transfer</button>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <h3 class="card-title">Stock Transfers List</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From Branch</th>
                            <th>To Branch</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stockTransfersTable">
                        <tr>
                            <td colspan="7" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination" style="padding: 16px; text-align: center;"></div>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 50px auto; padding: 24px; border-radius: 16px; max-width: 700px; max-height: 80vh; overflow-y: auto;">
            <div class="card-header">
                <h3 class="card-title">Create Stock Transfer</h3>
                <button onclick="closeCreateModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form id="createTransferForm" onsubmit="createStockTransfer(event)">
                <div style="margin-bottom: 16px;">
                    <label>From Branch *</label>
                    <select name="from_branch_id" id="fromBranch" required class="form-control">
                        <option value="">Select Branch</option>
                    </select>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>To Branch *</label>
                    <select name="to_branch_id" id="toBranch" required class="form-control">
                        <option value="">Select Branch</option>
                    </select>
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
                <button type="submit" class="btn">Create Transfer</button>
            </form>
        </div>
    </div>

    <script>
        let searchTimeout;
        
        async function loadStockTransfers(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    status: document.getElementById('statusFilter').value,
                    date_from: document.getElementById('dateFromFilter').value,
                    date_to: document.getElementById('dateToFilter').value,
                    search: document.getElementById('searchFilter').value
                });
                
                const response = await fetch('/api/v1/stock-transfers?' + params, {
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                renderStockTransfers(data.data || data);
                renderPagination(data);
            } catch (error) {
                console.error('Failed to load stock transfers:', error);
            }
        }

        function renderStockTransfers(transfers) {
            const tbody = document.getElementById('stockTransfersTable');
            if (!transfers || transfers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No stock transfers found</td></tr>';
                return;
            }
            
            tbody.innerHTML = transfers.map(t => `
                <tr>
                    <td>${t.id}</td>
                    <td>${t.from_branch?.name || 'N/A'}</td>
                    <td>${t.to_branch?.name || 'N/A'}</td>
                    <td><span class="status-badge status-${t.status}">${t.status}</span></td>
                    <td>${t.requested_by?.name || 'N/A'}</td>
                    <td>${new Date(t.created_at).toLocaleDateString()}</td>
                    <td>
                        <button onclick="viewTransfer(${t.id})" class="btn" style="padding: 4px 8px; font-size: 12px;">View</button>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const pagination = document.getElementById('pagination');
            if (data.last_page && data.last_page > 1) {
                let html = '';
                for (let i = 1; i <= data.last_page; i++) {
                    html += `<button onclick="loadStockTransfers(${i})" class="btn" style="margin: 0 4px; padding: 6px 12px;">${i}</button>`;
                }
                pagination.innerHTML = html;
            }
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadStockTransfers, 300);
        }

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
            loadBranches();
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        async function loadBranches() {
            // This would load branches from API
            console.log('Loading branches...');
        }

        function addItemRow() {
            const container = document.getElementById('itemsList');
            const div = document.createElement('div');
            div.innerHTML = `
                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <input type="text" placeholder="Product ID" name="items[][product_id]" style="flex: 1;">
                    <input type="number" placeholder="Quantity" name="items[][quantity_requested]" style="flex: 1;">
                    <input type="number" placeholder="Unit Cost" name="items[][unit_cost]" step="0.01" style="flex: 1;">
                </div>
            `;
            container.appendChild(div);
        }

        async function createStockTransfer(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            try {
                const response = await fetch('/api/v1/stock-transfers', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });
                
                if (response.ok) {
                    closeCreateModal();
                    loadStockTransfers();
                }
            } catch (error) {
                console.error('Failed to create transfer:', error);
            }
        }

        function viewTransfer(id) {
            window.location.href = `/api/v1/stock-transfers/${id}`;
        }

        document.addEventListener('DOMContentLoaded', loadStockTransfers);
    </script>
</body>
</html>