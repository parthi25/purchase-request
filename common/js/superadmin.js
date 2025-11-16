document.addEventListener('DOMContentLoaded', function() {
    let roles = [];
    let statuses = [];
    let permissions = [];
    let flows = [];
    let prPermissions = [];

    // Tab switching
    document.querySelectorAll('[data-tab]').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Update tab UI
            document.querySelectorAll('[data-tab]').forEach(t => t.classList.remove('tab-active'));
            this.classList.add('tab-active');
            
            // Show/hide content
            document.getElementById('permissions-tab').classList.toggle('hidden', tabName !== 'permissions');
            document.getElementById('flow-tab').classList.toggle('hidden', tabName !== 'flow');
            document.getElementById('pr_permissions-tab').classList.toggle('hidden', tabName !== 'pr_permissions');
            
            // Clear search and load data for active tab
            if (tabName === 'permissions') {
                if (permissionSearch) permissionSearch.value = '';
                loadPermissions();
            } else if (tabName === 'flow') {
                if (flowSearch) flowSearch.value = '';
                loadFlows();
            } else if (tabName === 'pr_permissions') {
                if (prPermissionSearch) prPermissionSearch.value = '';
                loadPRPermissions();
            }
        });
    });

    // Load initial data
    loadRoles();
    loadStatuses();
    loadPermissions();

    // Search functionality
    const permissionSearch = document.getElementById('permissionSearch');
    const flowSearch = document.getElementById('flowSearch');
    const prPermissionSearch = document.getElementById('prPermissionSearch');

    if (permissionSearch) {
        permissionSearch.addEventListener('input', function() {
            filterPermissions(this.value);
        });
    }

    if (flowSearch) {
        flowSearch.addEventListener('input', function() {
            filterFlows(this.value);
        });
    }

    if (prPermissionSearch) {
        prPermissionSearch.addEventListener('input', function() {
            filterPRPermissions(this.value);
        });
    }

    // Add Permission button
    document.getElementById('addPermissionBtn').addEventListener('click', function() {
        openPermissionModal();
    });

    // Add Flow button
    document.getElementById('addFlowBtn').addEventListener('click', function() {
        openFlowModal();
    });

    // Add PR Permission button
    const addPRPermissionBtn = document.getElementById('addPRPermissionBtn');
    if (addPRPermissionBtn) {
        addPRPermissionBtn.addEventListener('click', function() {
            openPRPermissionModal();
        });
    }

    // Permission form submit
    document.getElementById('permissionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        savePermission();
    });

    // Flow form submit
    document.getElementById('flowForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveFlow();
    });

    // PR Permission form submit
    const prPermissionForm = document.getElementById('prPermissionForm');
    if (prPermissionForm) {
        prPermissionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            savePRPermission();
        });
    }

    // Load roles
    async function loadRoles() {
        try {
            const response = await fetch('../api/admin/get-roles.php');
            const result = await response.json();
            if (result.status === 'success') {
                roles = result.data;
                populateRoleSelects();
            }
        } catch (error) {
            console.error('Error loading roles:', error);
            // Silently handle - don't show alerts
        }
    }

    // Load statuses
    async function loadStatuses() {
        try {
            const response = await fetch('../api/admin/get-statuses.php');
            const result = await response.json();
            if (result.status === 'success') {
                statuses = result.data;
                populateStatusSelects();
            }
        } catch (error) {
            console.error('Error loading statuses:', error);
            // Silently handle - don't show alerts
        }
    }

    // Load permissions
    async function loadPermissions() {
        try {
            const response = await fetch('../api/admin/status-permissions.php?type=permissions');
            const result = await response.json();
            if (result.status === 'success') {
                permissions = result.data;
                renderPermissionsTable();
            }
        } catch (error) {
            console.error('Error loading permissions:', error);
            // Silently handle - don't show alerts
        }
    }

    // Load flows
    async function loadFlows() {
        try {
            const response = await fetch('../api/admin/status-permissions.php?type=flow');
            const result = await response.json();
            if (result.status === 'success') {
                flows = result.data;
                renderFlowsTable();
            }
        } catch (error) {
            console.error('Error loading flows:', error);
            // Silently handle - don't show alerts
        }
    }

    // Populate role selects
    function populateRoleSelects() {
        const selects = ['permissionRole', 'flowRole', 'prPermissionRole'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Select Role</option>';
                roles.forEach(role => {
                    const option = document.createElement('option');
                    option.value = role;
                    option.textContent = role;
                    select.appendChild(option);
                });
            }
        });
    }

    // Populate status selects
    function populateStatusSelects() {
        const selects = ['permissionStatus', 'flowFromStatus', 'flowToStatus', 'prPermissionEditStatus'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const placeholder = selectId === 'permissionStatus' ? 'Select Status' : 
                                  selectId === 'flowFromStatus' ? 'Select From Status' : 
                                  selectId === 'flowToStatus' ? 'Select To Status' :
                                  'Any Status';
                select.innerHTML = `<option value="">${placeholder}</option>`;
                statuses.forEach(status => {
                    const option = document.createElement('option');
                    option.value = status.id;
                    option.textContent = `${status.id} - ${status.status}`;
                    select.appendChild(option);
                });
            }
        });
    }

    // Render permissions table
    function renderPermissionsTable(filteredData = null) {
        const tbody = document.getElementById('permissionsTableBody');
        const dataToRender = filteredData !== null ? filteredData : permissions;
        
        if (dataToRender.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No permissions found</td></tr>';
            return;
        }

        tbody.innerHTML = dataToRender.map(perm => `
            <tr>
                <td>${perm.id}</td>
                <td>${perm.role}</td>
                <td>${perm.status_id} - ${perm.status_name || 'N/A'}</td>
                <td>
                    <span class="badge ${perm.is_active ? 'badge-success' : 'badge-error'}">
                        ${perm.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editPermission(${perm.id})">Edit</button>
                    <button class="btn btn-sm btn-error" onclick="deletePermission(${perm.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    // Filter permissions
    function filterPermissions(searchTerm) {
        if (!searchTerm || searchTerm.trim() === '') {
            renderPermissionsTable();
            return;
        }

        const term = searchTerm.toLowerCase().trim();
        const filtered = permissions.filter(perm => {
            return (
                perm.id.toString().includes(term) ||
                perm.role.toLowerCase().includes(term) ||
                perm.status_id.toString().includes(term) ||
                (perm.status_name && perm.status_name.toLowerCase().includes(term)) ||
                (perm.is_active == 1 ? 'active' : 'inactive').includes(term)
            );
        });

        renderPermissionsTable(filtered);
    }

    // Render flows table
    function renderFlowsTable(filteredData = null) {
        const tbody = document.getElementById('flowTableBody');
        const dataToRender = filteredData !== null ? filteredData : flows;
        
        if (dataToRender.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No flows found</td></tr>';
            return;
        }

        tbody.innerHTML = dataToRender.map(flow => `
            <tr>
                <td>${flow.id}</td>
                <td>${flow.from_status_id} - ${flow.from_status_name || 'N/A'}</td>
                <td>${flow.to_status_id} - ${flow.to_status_name || 'N/A'}</td>
                <td>${flow.role}</td>
                <td>
                    <span class="badge ${flow.requires_proforma ? 'badge-warning' : 'badge-info'}">
                        ${flow.requires_proforma ? 'Yes' : 'No'}
                    </span>
                </td>
                <td>${flow.priority}</td>
                <td>
                    <span class="badge ${flow.is_active ? 'badge-success' : 'badge-error'}">
                        ${flow.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editFlow(${flow.id})">Edit</button>
                    <button class="btn btn-sm btn-error" onclick="deleteFlow(${flow.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    // Filter flows
    function filterFlows(searchTerm) {
        if (!searchTerm || searchTerm.trim() === '') {
            renderFlowsTable();
            return;
        }

        const term = searchTerm.toLowerCase().trim();
        const filtered = flows.filter(flow => {
            return (
                flow.id.toString().includes(term) ||
                flow.from_status_id.toString().includes(term) ||
                (flow.from_status_name && flow.from_status_name.toLowerCase().includes(term)) ||
                flow.to_status_id.toString().includes(term) ||
                (flow.to_status_name && flow.to_status_name.toLowerCase().includes(term)) ||
                flow.role.toLowerCase().includes(term) ||
                flow.priority.toString().includes(term) ||
                (flow.requires_proforma == 1 ? 'yes' : 'no').includes(term) ||
                (flow.is_active == 1 ? 'active' : 'inactive').includes(term)
            );
        });

        renderFlowsTable(filtered);
    }

    // Load PR permissions
    async function loadPRPermissions() {
        try {
            const response = await fetch('../api/admin/status-permissions.php?type=pr_permissions');
            const result = await response.json();
            if (result.status === 'success') {
                prPermissions = result.data;
                renderPRPermissionsTable();
            }
        } catch (error) {
            console.error('Error loading PR permissions:', error);
            // Silently handle - don't show alerts
        }
    }

    // Render PR permissions table
    function renderPRPermissionsTable(filteredData = null) {
        const tbody = document.getElementById('prPermissionsTableBody');
        const dataToRender = filteredData !== null ? filteredData : prPermissions;
        
        if (dataToRender.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No PR permissions found</td></tr>';
            return;
        }

        tbody.innerHTML = dataToRender.map(perm => `
            <tr>
                <td>${perm.id}</td>
                <td>${perm.role}</td>
                <td>
                    <span class="badge ${perm.can_create == 1 ? 'badge-success' : 'badge-error'}">
                        ${perm.can_create == 1 ? 'Yes' : 'No'}
                    </span>
                </td>
                <td>
                    <span class="badge ${perm.can_edit == 1 ? 'badge-success' : 'badge-error'}">
                        ${perm.can_edit == 1 ? 'Yes' : 'No'}
                    </span>
                </td>
                <td>${perm.can_edit_status ? perm.can_edit_status + ' - ' + (statuses.find(s => s.id == perm.can_edit_status)?.status || 'N/A') : 'Any Status'}</td>
                <td>
                    <span class="badge ${perm.is_active == 1 ? 'badge-success' : 'badge-error'}">
                        ${perm.is_active == 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editPRPermission(${perm.id})">Edit</button>
                    <button class="btn btn-sm btn-error" onclick="deletePRPermission(${perm.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    // Filter PR permissions
    function filterPRPermissions(searchTerm) {
        if (!searchTerm || searchTerm.trim() === '') {
            renderPRPermissionsTable();
            return;
        }

        const term = searchTerm.toLowerCase().trim();
        const filtered = prPermissions.filter(perm => {
            return (
                perm.id.toString().includes(term) ||
                perm.role.toLowerCase().includes(term) ||
                (perm.can_create == 1 ? 'yes' : 'no').includes(term) ||
                (perm.can_edit == 1 ? 'yes' : 'no').includes(term) ||
                (perm.can_edit_status ? perm.can_edit_status.toString() : 'any').includes(term) ||
                (perm.is_active == 1 ? 'active' : 'inactive').includes(term)
            );
        });

        renderPRPermissionsTable(filtered);
    }

    // Open permission modal
    window.openPermissionModal = function(id = null) {
        const modal = document.getElementById('permissionModal');
        const form = document.getElementById('permissionForm');
        const title = document.getElementById('permissionModalTitle');
        
        form.reset();
        document.getElementById('permissionId').value = id || '';
        title.textContent = id ? 'Edit Permission' : 'Add Permission';
        
        if (id) {
            const perm = permissions.find(p => p.id == id);
            if (perm) {
                document.getElementById('permissionRole').value = perm.role;
                document.getElementById('permissionStatus').value = perm.status_id;
                document.getElementById('permissionActive').checked = perm.is_active == 1;
            }
        }
        
        modal.showModal();
    };

    // Open flow modal
    window.openFlowModal = function(id = null) {
        const modal = document.getElementById('flowModal');
        const form = document.getElementById('flowForm');
        const title = document.getElementById('flowModalTitle');
        
        form.reset();
        document.getElementById('flowId').value = id || '';
        title.textContent = id ? 'Edit Flow' : 'Add Flow';
        
        if (id) {
            const flow = flows.find(f => f.id == id);
            if (flow) {
                document.getElementById('flowFromStatus').value = flow.from_status_id;
                document.getElementById('flowToStatus').value = flow.to_status_id;
                document.getElementById('flowRole').value = flow.role;
                document.getElementById('flowRequiresProforma').checked = flow.requires_proforma == 1;
                document.getElementById('flowPriority').value = flow.priority;
                document.getElementById('flowActive').checked = flow.is_active == 1;
            }
        }
        
        modal.showModal();
    };

    // Save permission
    async function savePermission() {
        const form = document.getElementById('permissionForm');
        const formData = new FormData(form);
        const id = formData.get('id');
        
        // Add checkbox value
        formData.append('is_active', document.getElementById('permissionActive').checked ? '1' : '0');
        
        try {
            const url = '../api/admin/status-permissions.php';
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            const options = {
                method: id ? 'PUT' : 'POST',
                body: new URLSearchParams(data),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            };
            
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert(id ? 'Permission updated successfully' : 'Permission created successfully', 'success');
                document.getElementById('permissionModal').close();
                loadPermissions();
            } else {
                showAlert(result.message || 'Failed to save permission', 'error');
            }
        } catch (error) {
            console.error('Error saving permission:', error);
            showAlert('Failed to save permission', 'error');
        }
    }

    // Save flow
    async function saveFlow() {
        const form = document.getElementById('flowForm');
        const formData = new FormData(form);
        const id = formData.get('id');
        
        // Add checkbox values
        formData.append('requires_proforma', document.getElementById('flowRequiresProforma').checked ? '1' : '0');
        formData.append('is_active', document.getElementById('flowActive').checked ? '1' : '0');
        
        try {
            const url = '../api/admin/status-permissions.php';
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            const options = {
                method: id ? 'PUT' : 'POST',
                body: new URLSearchParams(data),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            };
            
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert(id ? 'Flow updated successfully' : 'Flow created successfully', 'success');
                document.getElementById('flowModal').close();
                loadFlows();
            } else {
                showAlert(result.message || 'Failed to save flow', 'error');
            }
        } catch (error) {
            console.error('Error saving flow:', error);
            showAlert('Failed to save flow', 'error');
        }
    }

    // Edit permission
    window.editPermission = function(id) {
        openPermissionModal(id);
    };

    // Edit flow
    window.editFlow = function(id) {
        openFlowModal(id);
    };

    // Delete permission
    window.deletePermission = async function(id) {
        if (typeof Swal === 'undefined') {
            if (!confirm('Are you sure you want to delete this permission?')) return;
        } else {
            const confirmResult = await Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to delete this permission?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });
            
            if (!confirmResult.isConfirmed) return;
        }
        
        try {
            const formData = new FormData();
            formData.append('type', 'permission');
            formData.append('id', id);
            
            const response = await fetch('../api/admin/status-permissions.php', {
                method: 'DELETE',
                body: new URLSearchParams(formData),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert('Permission deleted successfully', 'success');
                loadPermissions();
            } else {
                showAlert(result.message || 'Failed to delete permission', 'error');
            }
        } catch (error) {
            console.error('Error deleting permission:', error);
            showAlert('Failed to delete permission', 'error');
        }
    };

    // Delete flow
    window.deleteFlow = async function(id) {
        if (typeof Swal === 'undefined') {
            if (!confirm('Are you sure you want to delete this flow?')) return;
        } else {
            const confirmResult = await Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to delete this flow?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });
            
            if (!confirmResult.isConfirmed) return;
        }
        
        try {
            const formData = new FormData();
            formData.append('type', 'flow');
            formData.append('id', id);
            
            const response = await fetch('../api/admin/status-permissions.php', {
                method: 'DELETE',
                body: new URLSearchParams(formData),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert('Flow deleted successfully', 'success');
                loadFlows();
            } else {
                showAlert(result.message || 'Failed to delete flow', 'error');
            }
        } catch (error) {
            console.error('Error deleting flow:', error);
            showAlert('Failed to delete flow', 'error');
        }
    };

    // Open PR permission modal
    window.openPRPermissionModal = function(id = null) {
        const modal = document.getElementById('prPermissionModal');
        const form = document.getElementById('prPermissionForm');
        const title = document.getElementById('prPermissionModalTitle');
        
        form.reset();
        document.getElementById('prPermissionId').value = id || '';
        title.textContent = id ? 'Edit PR Permission' : 'Add PR Permission';
        
        if (id) {
            const perm = prPermissions.find(p => p.id == id);
            if (perm) {
                document.getElementById('prPermissionRole').value = perm.role;
                document.getElementById('prPermissionCanCreate').checked = perm.can_create == 1;
                document.getElementById('prPermissionCanEdit').checked = perm.can_edit == 1;
                document.getElementById('prPermissionEditStatus').value = perm.can_edit_status || '';
                document.getElementById('prPermissionActive').checked = perm.is_active == 1;
            }
        }
        
        modal.showModal();
    };

    // Save PR permission
    async function savePRPermission() {
        const form = document.getElementById('prPermissionForm');
        const formData = new FormData(form);
        const id = formData.get('id');
        
        // Add checkbox values
        formData.append('can_create', document.getElementById('prPermissionCanCreate').checked ? '1' : '0');
        formData.append('can_edit', document.getElementById('prPermissionCanEdit').checked ? '1' : '0');
        formData.append('is_active', document.getElementById('prPermissionActive').checked ? '1' : '0');
        
        // Handle empty edit status (NULL)
        const editStatus = document.getElementById('prPermissionEditStatus').value;
        if (!editStatus) {
            formData.append('can_edit_status', '');
        }
        
        try {
            const url = '../api/admin/status-permissions.php';
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            const options = {
                method: id ? 'PUT' : 'POST',
                body: new URLSearchParams(data),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            };
            
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert(id ? 'PR Permission updated successfully' : 'PR Permission created successfully', 'success');
                document.getElementById('prPermissionModal').close();
                loadPRPermissions();
            } else {
                showAlert(result.message || 'Failed to save PR permission', 'error');
            }
        } catch (error) {
            console.error('Error saving PR permission:', error);
            showAlert('Failed to save PR permission', 'error');
        }
    }

    // Edit PR permission
    window.editPRPermission = function(id) {
        openPRPermissionModal(id);
    };

    // Delete PR permission
    window.deletePRPermission = async function(id) {
        if (typeof Swal === 'undefined') {
            if (!confirm('Are you sure you want to delete this PR permission?')) return;
        } else {
            const confirmResult = await Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to delete this PR permission?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });
            
            if (!confirmResult.isConfirmed) return;
        }
        
        try {
            const formData = new FormData();
            formData.append('type', 'pr_permissions');
            formData.append('id', id);
            
            const response = await fetch('../api/admin/status-permissions.php', {
                method: 'DELETE',
                body: new URLSearchParams(formData),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert('PR Permission deleted successfully', 'success');
                loadPRPermissions();
            } else {
                showAlert(result.message || 'Failed to delete PR permission', 'error');
            }
        } catch (error) {
            console.error('Error deleting PR permission:', error);
            showAlert('Failed to delete PR permission', 'error');
        }
    };

    // Show alert
    function showAlert(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        }
    }
});

