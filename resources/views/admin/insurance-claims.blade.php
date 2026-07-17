<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Claims - Admin Panel</title>
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
        .status-submitted { background: var(--info); color: white; }
        .status-approved { background: var(--success); color: white; }
        .status-rejected { background: var(--danger); color: white; }
        .status-paid { background: #8b5cf6; color: white; }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Insurance Claims</h1>
                <p>Manage insurance claims for patients</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn" style="background: var(--muted); color: white;">← Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="filter-bar">
                <select id="statusFilter" onchange="loadInsuranceClaims()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="paid">Paid</option>
                </select>
                <select id="insuranceCompanyFilter" onchange="loadInsuranceClaims()">
                    <option value="">All Insurance Companies</option>
                </select>
                <input type="text" id="searchFilter" placeholder="Search patient or claim..." onkeyup="debounceSearch()">
                <input type="date" id="dateFromFilter" onchange="loadInsuranceClaims()">
                <input type="date" id="dateToFilter" onchange="loadInsuranceClaims()">
                <button class="btn" onclick="openCreateModal()">+ New Claim</button>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <h3 class="card-title">Claims List</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th>Patient</th>
                            <th>Insurance Company</th>
                            <th>Amount Claimed</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="insuranceClaimsTable">
                        <tr>
                            <td colspan="7" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination" style="padding: 16px; text-align: center;"></div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 50px auto; padding: 24px; border-radius: 16px; max-width: 500px;">
            <div class="card-header">
                <h3 class="card-title">Approve Claim</h3>
                <button onclick="closeApproveModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form id="approveClaimForm" onsubmit="approveInsuranceClaim(event)">
                <input type="hidden" id="claimId" name="claim_id">
                <div style="margin-bottom: 16px;">
                    <label>Amount Approved *</label>
                    <input type="number" name="amount_approved" step="0.01" min="0" required class="form-control">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Settlement Amount</label>
                    <input type="number" name="settlement_amount" step="0.01" min="0" class="form-control">
                </div>
                <button type="submit" class="btn">Approve Claim</button>
            </form>
        </div>
    </div>

    <script>
        let searchTimeout;
        
        async function loadInsuranceClaims(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    status: document.getElementById('statusFilter').value,
                    insurance_company_id: document.getElementById('insuranceCompanyFilter').value,
                    search: document.getElementById('searchFilter').value,
                    date_from: document.getElementById('dateFromFilter').value,
                    date_to: document.getElementById('dateToFilter').value
                });
                
                const response = await fetch('/api/v1/insurance-claims?' + params, {
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                renderInsuranceClaims(data.data || data);
                renderPagination(data);
            } catch (error) {
                console.error('Failed to load insurance claims:', error);
            }
        }

        function renderInsuranceClaims(claims) {
            const tbody = document.getElementById('insuranceClaimsTable');
            if (!claims || claims.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No insurance claims found</td></tr>';
                return;
            }
            
            tbody.innerHTML = claims.map(c => `
                <tr>
                    <td>${c.claim_number || '#' + c.id}</td>
                    <td>${c.patient?.name || 'N/A'}</td>
                    <td>${c.insurance_company?.name || 'N/A'}</td>
                    <td>$${Number(c.amount_claimed || 0).toFixed(2)}</td>
                    <td><span class="status-badge status-${c.status}">${c.status}</span></td>
                    <td>${new Date(c.created_at).toLocaleDateString()}</td>
                    <td>
                        <button onclick="viewClaim(${c.id})" class="btn" style="padding: 4px 8px; font-size: 12px;">View</button>
                        ${c.status === 'submitted' || c.status === 'pending' ? `<button onclick="openApproveModal(${c.id})" class="btn" style="padding: 4px 8px; font-size: 12px; margin-left: 4px;">Approve</button>` : ''}
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const pagination = document.getElementById('pagination');
            if (data.last_page && data.last_page > 1) {
                let html = '';
                for (let i = 1; i <= data.last_page; i++) {
                    html += `<button onclick="loadInsuranceClaims(${i})" class="btn" style="margin: 0 4px; padding: 6px 12px;">${i}</button>`;
                }
                pagination.innerHTML = html;
            }
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadInsuranceClaims, 300);
        }

        function openCreateModal() {
            // Would open create modal
            console.log('Create modal not implemented');
        }

        function openApproveModal(claimId) {
            document.getElementById('claimId').value = claimId;
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        async function approveInsuranceClaim(e) {
            e.preventDefault();
            const claimId = document.getElementById('claimId').value;
            
            try {
                const response = await fetch(`/api/v1/insurance-claims/${claimId}/approve`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                if (response.ok) {
                    closeApproveModal();
                    loadInsuranceClaims();
                }
            } catch (error) {
                console.error('Failed to approve claim:', error);
            }
        }

        function viewClaim(id) {
            window.location.href = `/api/v1/insurance-claims/${id}`;
        }

        document.addEventListener('DOMContentLoaded', loadInsuranceClaims);
    </script>
</body>
</html>