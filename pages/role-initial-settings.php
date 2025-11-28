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
        <h1 class="text-2xl sm:text-3xl font-bold">Role Initial Page Settings</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-cog"></i>
                <span id="formTitle">Add Initial Page Setting</span>
            </h2>
            <form id="initialSettingsForm" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="id" id="settingId">
                
                <div class="form-control flex-1 min-w-[150px]">
                    <label class="label">
                        <span class="label-text">Role <span class="text-error">*</span></span>
                    </label>
                    <select name="role" id="settingRole" class="select select-bordered w-full" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text">Initial Page URL <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="initial_page_url" id="initialPageUrl" class="input input-bordered w-full" placeholder="e.g., admin.php" required>
                </div>
                
                <div class="form-control flex-1 min-w-[150px]">
                    <label class="label">
                        <span class="label-text">Initial Status Filter</span>
                    </label>
                    <select name="initial_status_filter" id="initialStatusFilter" class="select select-bordered w-full">
                        <option value="">No Filter</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active</span>
                        <input type="checkbox" name="is_active" id="isActive" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="form-control">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Setting</span>
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

    <!-- Settings Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Initial Page Settings
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
                    <input type="text" id="searchInput" placeholder="Search settings..." class="input input-bordered w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Role Name</th>
                            <th>Initial Page URL</th>
                            <th>Status Filter</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="settingsTableBody">
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
        let settings = [];
        let roles = [];
        let statuses = [];
        let editingId = null;

        // Load roles
        function loadRoles() {
            fetch('../api/admin/roles.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        roles = data.data;
                        populateRoleSelect();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    showToast('Failed to load roles', 'error');
                });
        }

        // Load statuses
        function loadStatuses() {
            fetch('../api/admin/get-statuses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        statuses = data.data;
                        populateStatusSelect();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading statuses:', error);
                    showToast('Failed to load statuses', 'error');
                });
        }

        // Populate role select
        function populateRoleSelect() {
            const roleSelect = document.getElementById('settingRole');
            const roleOptions = roles.map(role => 
                `<option value="${role.role_code}">${role.role_name} (${role.role_code})</option>`
            ).join('');
            roleSelect.innerHTML = '<option value="">Select Role</option>' + roleOptions;
        }

        // Populate status select
        function populateStatusSelect() {
            const statusSelect = document.getElementById('initialStatusFilter');
            const statusOptions = statuses.map(status => 
                `<option value="${status.id}">${status.status}</option>`
            ).join('');
            statusSelect.innerHTML = '<option value="">No Filter</option>' + statusOptions;
        }

        // Load initial settings
        function loadSettings() {
            fetch('../api/admin/role-initial-settings.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        settings = data.data;
                        renderSettings();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading settings:', error);
                    showToast('Failed to load settings', 'error');
                });
        }

        // Render settings table
        function renderSettings() {
            const tbody = document.getElementById('settingsTableBody');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filteredSettings = settings;
            if (searchTerm) {
                filteredSettings = settings.filter(setting => 
                    (setting.role && setting.role.toLowerCase().includes(searchTerm)) ||
                    (setting.role_name && setting.role_name.toLowerCase().includes(searchTerm)) ||
                    (setting.initial_page_url && setting.initial_page_url.toLowerCase().includes(searchTerm))
                );
            }

            if (filteredSettings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No settings found</td></tr>';
                return;
            }

            tbody.innerHTML = filteredSettings.map(setting => {
                const statusName = setting.initial_status_filter ? 
                    (statuses.find(s => s.id == setting.initial_status_filter)?.status || setting.initial_status_filter) : 
                    'None';
                
                return `
                <tr>
                    <td>${setting.id}</td>
                    <td><code class="badge badge-outline">${setting.role}</code></td>
                    <td><strong>${setting.role_name || setting.role}</strong></td>
                    <td><code class="text-xs">${setting.initial_page_url}</code></td>
                    <td>${statusName}</td>
                    <td>
                        <span class="badge ${setting.is_active ? 'badge-success' : 'badge-error'}">
                            ${setting.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${setting.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${setting.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            }).join('');
        }

        // Edit setting - make it global
        window.editSetting = function(id) {
            console.log('editSetting called with id:', id, 'type:', typeof id);
            console.log('Settings array:', settings);
            
            // Convert id to number for comparison
            const settingId = typeof id === 'string' ? parseInt(id, 10) : id;
            const setting = settings.find(s => s.id == settingId || s.id === settingId);
            
            console.log('Found setting:', setting);
            
            if (!setting) {
                console.error('Setting not found for id:', settingId);
                showToast('Setting not found', 'error');
                return;
            }

            editingId = setting.id;
            document.getElementById('settingId').value = setting.id;
            document.getElementById('settingRole').value = setting.role || '';
            document.getElementById('settingRole').disabled = true; // Cannot change role
            document.getElementById('initialPageUrl').value = setting.initial_page_url || '';
            document.getElementById('initialStatusFilter').value = setting.initial_status_filter || '';
            document.getElementById('isActive').checked = setting.is_active == 1 || setting.is_active === 1;
            
            document.getElementById('formTitle').textContent = 'Edit Initial Page Setting';
            document.getElementById('submitBtnText').textContent = 'Update Setting';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            
            // Scroll to form
            document.getElementById('initialSettingsForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        // Delete setting - make it global
        window.deleteSetting = async function(id) {
            const confirmResult = await showConfirm(
                'Delete Setting?',
                'Are you sure you want to delete this initial page setting?',
                'Yes, delete it!',
                'Cancel'
            );
            
            if (!confirmResult.isConfirmed) {
                return;
            }

            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch('../api/admin/role-initial-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        loadSettings();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting setting:', error);
                    showToast('Failed to delete setting', 'error');
                });
        };

        // Cancel edit
        document.getElementById('cancelBtn').addEventListener('click', () => {
            editingId = null;
            document.getElementById('initialSettingsForm').reset();
            document.getElementById('settingRole').disabled = false;
            document.getElementById('formTitle').textContent = 'Add Initial Page Setting';
            document.getElementById('submitBtnText').textContent = 'Add Setting';
            document.getElementById('cancelBtn').style.display = 'none';
        });

        // Form submit
        document.getElementById('initialSettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            
            const formData = new FormData(e.target);
            const action = editingId ? 'update' : 'create';
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);
            
            // Ensure id is set for update
            if (editingId) {
                formData.set('id', editingId.toString());
            }

            fetch('../api/admin/role-initial-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        document.getElementById('initialSettingsForm').reset();
                        document.getElementById('settingId').value = '';
                        document.getElementById('settingRole').disabled = false;
                        editingId = null;
                        document.getElementById('formTitle').textContent = 'Add Initial Page Setting';
                        document.getElementById('submitBtnText').textContent = 'Add Setting';
                        document.getElementById('cancelBtn').style.display = 'none';
                        loadSettings();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error saving setting:', error);
                    showToast('Failed to save setting', 'error');
                });
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            loadSettings();
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
                const filteredSettings = getFilteredSettings();
                if (filteredSettings.length === 0) {
                    showToast('No settings found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Initial Page URL', 'Status Filter', 'Active'];
                const rows = filteredSettings.map(setting => [
                    setting.id,
                    setting.role_name || setting.role,
                    setting.initial_page_url,
                    setting.initial_status_filter || '-',
                    setting.is_active ? 'Yes' : 'No'
                ]);
                
                const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Settings");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Initial_Page_Settings_${dateStr}.xlsx`);
                
                showToast('Settings have been exported to Excel', 'success', 1500);
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
                const filteredSettings = getFilteredSettings();
                if (filteredSettings.length === 0) {
                    showToast('No settings found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Initial Page URL', 'Status Filter', 'Active'];
                const csvRows = filteredSettings.map(setting => {
                    const row = [
                        setting.id,
                        (setting.role_name || setting.role).replace(/"/g, '""'),
                        setting.initial_page_url.replace(/"/g, '""'),
                        (setting.initial_status_filter || '-').replace(/"/g, '""'),
                        setting.is_active ? 'Yes' : 'No'
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
                saveAs(blob, `Initial_Page_Settings_${dateStr}.csv`);
                
                showToast('Settings have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function getFilteredSettings() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            if (searchTerm) {
                return settings.filter(setting => 
                    (setting.role_name || setting.role).toLowerCase().includes(searchTerm) ||
                    setting.initial_page_url.toLowerCase().includes(searchTerm)
                );
            }
            
            return settings;
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

        // Search
        document.getElementById('searchInput').addEventListener('input', renderSettings);

        // Event delegation for edit and delete buttons (works with dynamically generated content)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.editSetting(id);
                }
            } else if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.deleteSetting(id);
                }
            }
        });

        // Load on page load
        loadRoles();
        loadStatuses();
        loadSettings();
    </script>
<?php include '../common/layout-footer.php'; ?>

