<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors Management - Pharmacy Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pharmacy-pos.css') }}">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .doctors-table {
            width: 100%;
        }
        
        .doctors-table th,
        .doctors-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .specialization-badge {
            background: #eff6ff;
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Doctors Management</h1>
                <p>Manage doctor records and prescriptions</p>
            </div>
            <button class="btn btn-primary" onclick="openDoctorModal()">➕ Add Doctor</button>
        </div>

        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search doctors..." onkeyup="searchDoctors()">
                <select id="specializationFilter" onchange="filterSpecialization()">
                    <option value="">All Specializations</option>
                    <option value="Cardiology">Cardiology</option>
                    <option value="General Practice">General Practice</option>
                    <option value="Neurology">Neurology</option>
                    <option value="Pediatrics">Pediatrics</option>
                </select>
            </div>

            <div class="table-container">
                <table class="doctors-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>License #</th>
                            <th>Phone</th>
                            <th>Clinic</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="doctorsTableBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted">Loading doctors...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Doctor Modal -->
    <div class="modal-overlay" id="doctorModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Doctor</h3>
                <button class="modal-close" onclick="closeDoctorModal()">×</button>
            </div>
            <form id="doctorForm">
                <div style="margin-bottom: 16px;">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Specialization *</label>
                    <input type="text" name="specialization" required>
                </div>
                <div style="margin-bottom: 16px;">
                    <label>License Number *</label>
                    <input type="text" name="license_number" required>
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
                    <label>Clinic Name</label>
                    <input type="text" name="clinic_name">
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
                
                <div class="checkout-actions">
                    <button type="submit" class="checkout-btn">Save Doctor</button>
                    <button type="button" class="secondary-btn" onclick="closeDoctorModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadDoctors() {
            try {
                const response = await fetch('/api/v1/doctors', {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    renderDoctors(data.data || data);
                }
            } catch (error) {
                console.error('Failed to load doctors:', error);
                document.getElementById('doctorsTableBody').innerHTML = 
                    '<tr><td colspan="7" class="text-center text-muted">Failed to load doctors</td></tr>';
            }
        }

        function renderDoctors(doctors) {
            const tbody = document.getElementById('doctorsTableBody');
            if (!doctors || doctors.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No doctors found</td></tr>';
                return;
            }

            tbody.innerHTML = doctors.map(d => `
                <tr>
                    <td>${d.name}</td>
                    <td><span class="specialization-badge">${d.specialization}</span></td>
                    <td>${d.license_number}</td>
                    <td>${d.phone}</td>
                    <td>${d.clinic_name || '-'}</td>
                    <td><span class="badge ${d.is_active ? 'badge-success' : 'badge-danger'}">${d.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" onclick="editDoctor(${d.id})">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteDoctor(${d.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function searchDoctors() {
            const query = document.getElementById('searchInput').value;
            try {
                const response = await fetch(`/api/v1/doctors?search=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    renderDoctors(data.data || data);
                }
            } catch (error) {
                console.error('Search failed:', error);
            }
        }

        function openDoctorModal() {
            document.getElementById('doctorModal').classList.add('active');
        }

        function closeDoctorModal() {
            document.getElementById('doctorModal').classList.remove('active');
            document.getElementById('doctorForm').reset();
        }

        async function saveDoctor(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('/api/v1/doctors', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    closeDoctorModal();
                    loadDoctors();
                } else {
                    alert('Failed to save doctor');
                }
            } catch (error) {
                console.error('Save failed:', error);
                alert('Failed to save doctor');
            }
        }

        document.getElementById('doctorForm').addEventListener('submit', saveDoctor);

        document.addEventListener('DOMContentLoaded', loadDoctors);
    </script>
</body>
</html>