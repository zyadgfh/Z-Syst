<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management - Pharmacy Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pharmacy-pos.css') }}">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-bar input {
            flex: 1;
            max-width: 300px;
        }
        
        .patients-table {
            width: 100%;
        }
        
        .patients-table th,
        .patients-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }
        
        .edit-btn {
            background: var(--primary);
            color: white;
        }
        
        .delete-btn {
            background: var(--danger);
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Patients Management</h1>
                <p>Manage patient records and prescriptions</p>
            </div>
            <button class="btn btn-primary" onclick="openPatientModal()">➕ Add Patient</button>
        </div>

        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search patients..." onkeyup="searchPatients()">
                <select id="statusFilter" onchange="filterStatus()">
                    <option value="">All Status</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>

            <div class="table-container">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Last Visit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientsTableBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted">Loading patients...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination" style="margin-top: 20px; text-align: center;"></div>
        </div>
    </div>

    <!-- Patient Modal -->
    <div class="modal-overlay" id="patientModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Patient</h3>
                <button class="modal-close" onclick="closePatientModal()">×</button>
            </div>
            <form id="patientForm">
                <div style="margin-bottom: 16px;">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Phone *</label>
                    <input type="tel" name="phone" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">Select...</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Blood Group</label>
                    <input type="text" name="blood_group">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Allergies (JSON)</label>
                    <textarea name="allergies" placeholder='["penicillin", "sulfa"]'></textarea>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Notes</label>
                    <textarea name="notes"></textarea>
                </div>
                
                <div class="checkout-actions">
                    <button type="submit" class="checkout-btn">Save Patient</button>
                    <button type="button" class="secondary-btn" onclick="closePatientModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let patientsData = [];

        async function loadPatients(page = 1) {
            try {
                const response = await fetch(`/api/v1/patients?page=${page}`, {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    patientsData = data.data || data;
                    renderPatients(patientsData);
                    renderPagination(data.meta || {});
                }
            } catch (error) {
                console.error('Failed to load patients:', error);
                document.getElementById('patientsTableBody').innerHTML = 
                    '<tr><td colspan="6" class="text-center text-muted">Failed to load patients</td></tr>';
            }
        }

        function renderPatients(patients) {
            const tbody = document.getElementById('patientsTableBody');
            if (!patients || patients.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No patients found</td></tr>';
                return;
            }

            tbody.innerHTML = patients.map(p => `
                <tr>
                    <td>${p.name}</td>
                    <td>${p.phone || '-'}</td>
                    <td>${p.email || '-'}</td>
                    <td>${p.updated_at ? new Date(p.updated_at).toLocaleDateString() : '-'}</td>
                    <td><span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">${p.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" onclick="editPatient(${p.id})">Edit</button>
                            <button class="action-btn delete-btn" onclick="deletePatient(${p.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(meta) {
            const pagination = document.getElementById('pagination');
            if (!meta.last_page || meta.last_page <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';
            for (let i = 1; i <= meta.last_page; i++) {
                html += `<button onclick="loadPatients(${i})" style="padding: 8px 12px; margin: 0 4px; border: 1px solid var(--border); border-radius: 6px; cursor: pointer;">${i}</button>`;
            }
            pagination.innerHTML = html;
        }

        async function searchPatients() {
            const query = document.getElementById('searchInput').value;
            try {
                const response = await fetch(`/api/v1/patients?search=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    renderPatients(data.data || data);
                }
            } catch (error) {
                console.error('Search failed:', error);
            }
        }

        function filterStatus() {
            const status = document.getElementById('statusFilter').value;
            // In production, this would filter by API params
        }

        function openPatientModal() {
            document.getElementById('patientModal').classList.add('active');
        }

        function closePatientModal() {
            document.getElementById('patientModal').classList.remove('active');
            document.getElementById('patientForm').reset();
        }

        async function savePatient(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('/api/v1/patients', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    closePatientModal();
                    loadPatients();
                } else {
                    alert('Failed to save patient');
                }
            } catch (error) {
                console.error('Save failed:', error);
                alert('Failed to save patient');
            }
        }

        document.getElementById('patientForm').addEventListener('submit', savePatient);

        // Load on page ready
        document.addEventListener('DOMContentLoaded', () => loadPatients());
    </script>
</body>
</html>