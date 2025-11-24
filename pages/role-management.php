<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Only super_admin and master can access
if (!in_array($_SESSION['role'], ['super_admin', 'master'])) {
    header("Location: ../index.php");
    exit;
}

include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Role Management</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4">
                <i class="fas fa-user-shield"></i>
                <span id="formTitle">Add New Role</span>
            </h2>
            <form id="roleForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="roleId">
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Role Code <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-code"></i></span>
                        <input type="text" name="role_code" id="roleCode" class="input input-bordered join-item flex-1" placeholder="e.g., admin, buyer" required pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed">
                    </div>
                    <label class="label">
                        <span class="label-text-alt text-warning">Used in code (cannot be changed after creation)</span>
                    </label>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Role Name <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                        <input type="text" name="role_name" id="roleName" class="input input-bordered join-item flex-1" placeholder="e.g., Administrator" required>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea name="description" id="description" class="textarea textarea-bordered" placeholder="Role description"></textarea>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Display Order</span>
                    </label>
                    <input type="number" name="display_order" id="displayOrder" class="input input-bordered" value="0" min="0">
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active</span>
                        <input type="checkbox" name="is_active" id="isActive" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="form-control sm:col-span-2 lg:col-span-3">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Role</span>
                        </button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    Roles List
                </h2>
                <div class="flex gap-2">
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-success btn-sm sm:btn-md">
                            <i class="fas fa-file-export"></i> <span class="hidden sm:inline">Export</span>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a id="exportExcel"><i class="fas fa-file-excel text-success"></i> Export as Excel</a></li>
                            <li><a id="exportCSV"><i class="fas fa-file-csv text-primary"></i> Export as CSV</a></li>
                        </ul>
                    </div>
                    <button id="refreshBtn" class="btn btn-outline btn-sm sm:btn-md">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <input type="text" id="searchInput" placeholder="Search roles..." class="input input-bordered w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Code</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Display Order</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <tr>
                            <td colspan="7" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script>
        let roles = [];
        let editingId = null;

        // Load roles
        function loadRoles() {
            fetch('../api/admin/roles.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        roles = data.data;
                        renderRoles();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    showToast('Failed to load roles', 'error');
                });
        }

        // Render roles table
        function renderRoles() {
            const tbody = document.getElementById('rolesTableBody');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filteredRoles = roles;
            if (searchTerm) {
                filteredRoles = roles.filter(role => 
                    role.role_code.toLowerCase().includes(searchTerm) ||
                    role.role_name.toLowerCase().includes(searchTerm) ||
                    (role.description && role.description.toLowerCase().includes(searchTerm))
                );
            }

            if (filteredRoles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No roles found</td></tr>';
                return;
            }

            tbody.innerHTML = filteredRoles.map(role => `
                <tr>
                    <td>${role.id}</td>
                    <td><code class="badge badge-outline">${role.role_code}</code></td>
                    <td><strong>${role.role_name}</strong></td>
                    <td>${role.description || '-'}</td>
                    <td>${role.display_order}</td>
                    <td>
                        <span class="badge ${role.is_active ? 'badge-success' : 'badge-error'}">
                            ${role.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${role.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${role.id}" data-role-code="${role.role_code}" ${role.role_code === 'super_admin' || role.role_code === 'master' ? 'disabled title="Cannot delete system roles"' : ''}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Edit role - make it global for event delegation
        window.editRole = function(id) {
            const role = roles.find(r => r.id == id || r.id === id);
            if (!role) {
                showToast('Role not found', 'error');
                return;
            }

            editingId = id;
            document.getElementById('roleId').value = role.id;
            document.getElementById('roleCode').value = role.role_code;
            document.getElementById('roleCode').disabled = true; // Cannot change role code
            document.getElementById('roleName').value = role.role_name;
            document.getElementById('description').value = role.description || '';
            document.getElementById('displayOrder').value = role.display_order;
            document.getElementById('isActive').checked = role.is_active == 1;
            
            document.getElementById('formTitle').textContent = 'Edit Role';
            document.getElementById('submitBtnText').textContent = 'Update Role';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            
            document.getElementById('roleForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        // Delete role - make it global for event delegation
        window.deleteRole = async function(id) {
            const role = roles.find(r => r.id == id || r.id === id);
            if (!role) {
                showToast('Role not found', 'error');
                return;
            }

            const roleCode = role.role_code;
            if (roleCode === 'super_admin' || roleCode === 'master') {
                showToast('Cannot delete system roles', 'error');
                return;
            }

            const confirmResult = await showConfirm(
                'Delete Role?',
                `This will permanently delete the role: ${roleCode}\n\nWarning: This action cannot be undone!`,
                'Yes, delete it!',
                'Cancel'
            );

            if (!confirmResult.isConfirmed) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('../api/admin/roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success', 2000);
                        loadRoles();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting role:', error);
                    showToast('Failed to delete role', 'error');
                });
        };

        // Cancel edit
        document.getElementById('cancelBtn').addEventListener('click', () => {
            editingId = null;
            document.getElementById('roleForm').reset();
            document.getElementById('roleCode').disabled = false;
            document.getElementById('formTitle').textContent = 'Add New Role';
            document.getElementById('submitBtnText').textContent = 'Add Role';
            document.getElementById('cancelBtn').style.display = 'none';
        });

        // Form submit
        document.getElementById('roleForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', editingId ? 'update' : 'create');
            if (editingId) {
                formData.append('id', editingId);
            }

            fetch('../api/admin/roles.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success', 2000);
                        document.getElementById('roleForm').reset();
                        document.getElementById('roleCode').disabled = false;
                        editingId = null;
                        document.getElementById('formTitle').textContent = 'Add New Role';
                        document.getElementById('submitBtnText').textContent = 'Add Role';
                        document.getElementById('cancelBtn').style.display = 'none';
                        loadRoles();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error saving role:', error);
                    showToast('Failed to save role', 'error');
                });
        });

        // Event delegation for edit and delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.editRole(id);
                }
            } else if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const roleCode = btn.getAttribute('data-role-code');
                if (id && roleCode !== 'super_admin' && roleCode !== 'master') {
                    window.deleteRole(id);
                } else if (roleCode === 'super_admin' || roleCode === 'master') {
                    showToast('Cannot delete system roles', 'error');
                }
            }
        });

        // Search
        document.getElementById('searchInput').addEventListener('input', renderRoles);

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            loadRoles();
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 700);
        });

        // Export functions
        function exportToExcel() {
            if (typeof XLSX === 'undefined') {
                showToast('Excel export library not loaded. Please refresh the page.', 'error');
                return;
            }
            
            try {
                const filteredRoles = getFilteredRoles();
                if (filteredRoles.length === 0) {
                    showToast('No roles found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role Code', 'Role Name', 'Description', 'Display Order', 'Active'];
                const rows = filteredRoles.map(role => [
                    role.id,
                    role.role_code,
                    role.role_name,
                    role.description || '-',
                    role.display_order,
                    role.is_active ? 'Yes' : 'No'
                ]);
                
                const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Roles");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Roles_${dateStr}.xlsx`);
                
                showToast('Roles have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function exportToCSV() {
            if (typeof saveAs === 'undefined') {
                showToast('FileSaver library not loaded. Please refresh the page.', 'error');
                return;
            }
            
            try {
                const filteredRoles = getFilteredRoles();
                if (filteredRoles.length === 0) {
                    showToast('No roles found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role Code', 'Role Name', 'Description', 'Display Order', 'Active'];
                const csvRows = filteredRoles.map(role => {
                    const row = [
                        role.id,
                        role.role_code,
                        role.role_name,
                        (role.description || '-').replace(/"/g, '""'),
                        role.display_order,
                        role.is_active ? 'Yes' : 'No'
                    ];
                    return row.map(cell => {
                        let text = String(cell);
                        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                            text = `"${text}"`;
                        }
                        return text;
                    }).join(',');
                });
                
                csvRows.unshift(headers.join(','));
                const csvContent = csvRows.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                saveAs(blob, `Roles_${dateStr}.csv`);
                
                showToast('Roles have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function getFilteredRoles() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            if (searchTerm) {
                return roles.filter(role => 
                    role.role_code.toLowerCase().includes(searchTerm) ||
                    role.role_name.toLowerCase().includes(searchTerm) ||
                    (role.description && role.description.toLowerCase().includes(searchTerm))
                );
            }
            return roles;
        }

        // Export event listeners
        document.getElementById('exportExcel').addEventListener('click', function(e) {
            e.preventDefault();
            exportToExcel();
        });

        document.getElementById('exportCSV').addEventListener('click', function(e) {
            e.preventDefault();
            exportToCSV();
        });

        // Load on page load
        loadRoles();
    </script>
<?php include '../common/layout-footer.php'; ?>

