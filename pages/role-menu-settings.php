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
        <h1 class="text-2xl sm:text-3xl font-bold">Role Menu Settings</h1>
    </div>

    <!-- Role Filter -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold capitalize">Filter by Role</span>
                </label>
                <select id="roleFilter" class="select select-bordered w-full max-w-xs">
                    <option value="">All Roles</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-bars"></i>
                <span id="formTitle">Add New Menu Item</span>
            </h2>
            <form id="menuForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="menuId">
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Role <span class="text-error">*</span></span>
                    </label>
                    <select name="role" id="menuRole" class="select select-bordered" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Menu Label <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="menu_item_label" id="menuLabel" class="input input-bordered" placeholder="e.g., Dashboard" required>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Menu URL <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="menu_item_url" id="menuUrl" class="input input-bordered" placeholder="e.g., dashboard.php" required>
                </div>
                
                <div class="form-control sm:col-span-2 lg:col-span-3">
                    <label class="label">
                        <span class="label-text">Menu Icon (SVG Code)</span>
                    </label>
                    <textarea name="menu_item_icon" id="menuIcon" class="textarea textarea-bordered font-mono text-sm" rows="3" placeholder='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">...</svg>'></textarea>
                    <label class="label">
                        <span class="label-text-alt">Paste SVG icon code here</span>
                    </label>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Menu Order</span>
                    </label>
                    <input type="number" name="menu_order" id="menuOrder" class="input input-bordered" value="0" min="0">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Menu Group</span>
                    </label>
                    <select name="menu_group" id="menuGroup" class="select select-bordered">
                        <option value="main">Main</option>
                        <option value="master_management">Master Management</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Visible</span>
                        <input type="checkbox" name="is_visible" id="menuVisible" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="form-control sm:col-span-2 lg:col-span-3">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Menu Item</span>
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

    <!-- Menu Items Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Menu Items
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
                    <input type="text" id="searchInput" placeholder="Search menu items..." class="input input-bordered w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Order</th>
                            <th>Group</th>
                            <th>Visible</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menuTableBody">
                        <tr>
                            <td colspan="9" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script>
        let menus = [];
        let roles = [];
        let editingId = null;

        // Load roles
        function loadRoles() {
            fetch('../api/admin/roles.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        roles = data.data;
                        populateRoleSelects();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    showToast('Failed to load roles', 'error');
                });
        }

        // Populate role selects
        function populateRoleSelects() {
            const roleFilter = document.getElementById('roleFilter');
            const menuRole = document.getElementById('menuRole');
            
            const roleOptions = roles.map(role => 
                `<option value="${role.role_code}">${role.role_name} (${role.role_code})</option>`
            ).join('');
            
            roleFilter.innerHTML = '<option value="">All Roles</option>' + roleOptions;
            menuRole.innerHTML = '<option value="">Select Role</option>' + roleOptions;
        }

        // Load menu items
        function loadMenus() {
            fetch('../api/admin/role-menu-settings.php?action=list_all')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        menus = data.data;
                        renderMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading menus:', error);
                    showToast('Failed to load menu items', 'error');
                });
        }

        // Render menu items table
        function renderMenus() {
            const tbody = document.getElementById('menuTableBody');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            
            let filteredMenus = menus;
            
            if (roleFilter) {
                filteredMenus = filteredMenus.filter(menu => menu.role === roleFilter);
            }
            
            if (searchTerm) {
                filteredMenus = filteredMenus.filter(menu => 
                    menu.menu_item_label.toLowerCase().includes(searchTerm) ||
                    menu.menu_item_url.toLowerCase().includes(searchTerm) ||
                    menu.role.toLowerCase().includes(searchTerm)
                );
            }

            if (filteredMenus.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No menu items found</td></tr>';
                return;
            }

            tbody.innerHTML = filteredMenus.map(menu => `
                <tr>
                    <td>${menu.id}</td>
                    <td><code class="badge badge-outline">${menu.role}</code></td>
                    <td><strong>${menu.menu_item_label}</strong></td>
                    <td><code class="text-xs">${menu.menu_item_url}</code></td>
                    <td>${menu.menu_order}</td>
                    <td><span class="badge badge-ghost">${menu.menu_group || 'main'}</span></td>
                    <td>
                        <span class="badge ${menu.is_visible ? 'badge-success' : 'badge-error'}">
                            ${menu.is_visible ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${menu.is_active ? 'badge-success' : 'badge-error'}">
                            ${menu.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${menu.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${menu.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Edit menu item - make it global for event delegation
        window.editMenu = function(id) {
            const menu = menus.find(m => m.id == id || m.id === id);
            if (!menu) {
                showToast('Menu item not found', 'error');
                return;
            }

            editingId = id;
            document.getElementById('menuId').value = menu.id;
            document.getElementById('menuRole').value = menu.role;
            document.getElementById('menuLabel').value = menu.menu_item_label;
            document.getElementById('menuUrl').value = menu.menu_item_url;
            document.getElementById('menuIcon').value = menu.menu_item_icon || '';
            document.getElementById('menuOrder').value = menu.menu_order;
            document.getElementById('menuGroup').value = menu.menu_group || 'main';
            document.getElementById('menuVisible').checked = menu.is_visible == 1;
            
            document.getElementById('formTitle').textContent = 'Edit Menu Item';
            document.getElementById('submitBtnText').textContent = 'Update Menu Item';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            
            document.getElementById('menuForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        // Delete menu item - make it global for event delegation
        window.deleteMenu = async function(id) {
            const confirmResult = await showConfirm(
                'Delete Menu Item?',
                'This will permanently delete this menu item.',
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

            fetch('../api/admin/role-menu-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success', 2000);
                        loadMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting menu item:', error);
                    showToast('Failed to delete menu item', 'error');
                });
        };

        // Cancel edit
        document.getElementById('cancelBtn').addEventListener('click', () => {
            editingId = null;
            document.getElementById('menuForm').reset();
            document.getElementById('formTitle').textContent = 'Add New Menu Item';
            document.getElementById('submitBtnText').textContent = 'Add Menu Item';
            document.getElementById('cancelBtn').style.display = 'none';
        });

        // Form submit
        document.getElementById('menuForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            
            const formData = new FormData(e.target);
            formData.append('action', editingId ? 'update' : 'create');
            formData.append('csrf_token', csrfToken);
            if (editingId) {
                formData.append('id', editingId);
                formData.append('is_active', '1'); // Keep active when updating
            }

            fetch('../api/admin/role-menu-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        document.getElementById('menuForm').reset();
                        editingId = null;
                        document.getElementById('formTitle').textContent = 'Add New Menu Item';
                        document.getElementById('submitBtnText').textContent = 'Add Menu Item';
                        document.getElementById('cancelBtn').style.display = 'none';
                        loadMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error saving menu item:', error);
                    showToast('Failed to save menu item', 'error');
                });
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            loadMenus();
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
                const filteredMenus = getFilteredMenus();
                if (filteredMenus.length === 0) {
                    showToast('No menu items found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Label', 'URL', 'Order', 'Group', 'Visible', 'Active'];
                const rows = filteredMenus.map(menu => [
                    menu.id,
                    menu.role,
                    menu.menu_item_label,
                    menu.menu_item_url,
                    menu.menu_order,
                    menu.menu_group || 'main',
                    menu.is_visible ? 'Yes' : 'No',
                    menu.is_active ? 'Yes' : 'No'
                ]);
                
                const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "MenuItems");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Menu_Items_${dateStr}.xlsx`);
                
                showToast('Menu items have been exported to Excel', 'success', 1500);
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
                const filteredMenus = getFilteredMenus();
                if (filteredMenus.length === 0) {
                    showToast('No menu items found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Label', 'URL', 'Order', 'Group', 'Visible', 'Active'];
                const csvRows = filteredMenus.map(menu => {
                    const row = [
                        menu.id,
                        menu.role,
                        menu.menu_item_label.replace(/"/g, '""'),
                        menu.menu_item_url.replace(/"/g, '""'),
                        menu.menu_order,
                        (menu.menu_group || 'main').replace(/"/g, '""'),
                        menu.is_visible ? 'Yes' : 'No',
                        menu.is_active ? 'Yes' : 'No'
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
                saveAs(blob, `Menu_Items_${dateStr}.csv`);
                
                showToast('Menu items have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function getFilteredMenus() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            
            let filteredMenus = menus;
            
            if (roleFilter) {
                filteredMenus = filteredMenus.filter(menu => menu.role === roleFilter);
            }
            
            if (searchTerm) {
                filteredMenus = filteredMenus.filter(menu => 
                    menu.menu_item_label.toLowerCase().includes(searchTerm) ||
                    menu.menu_item_url.toLowerCase().includes(searchTerm) ||
                    menu.role.toLowerCase().includes(searchTerm)
                );
            }
            
            return filteredMenus;
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

        // Event delegation for edit and delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.editMenu(id);
                }
            } else if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.deleteMenu(id);
                }
            }
        });

        // Search and filter
        document.getElementById('searchInput').addEventListener('input', renderMenus);
        document.getElementById('roleFilter').addEventListener('change', renderMenus);

        // Load on page load
        loadRoles();
        loadMenus();
    </script>
<?php include '../common/layout-footer.php'; ?>

