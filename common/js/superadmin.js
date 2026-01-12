document.addEventListener('DOMContentLoaded', function () {
    let roles = [];
    let statuses = [];
    let permissions = [];
    let flows = [];
    let prPermissions = [];
    let modalFields = [];

    // Tab switching
    document.querySelectorAll('[data-tab]').forEach(tab => {
        tab.addEventListener('click', function () {
            const tabName = this.dataset.tab;

            // Update tab UI
            document.querySelectorAll('[data-tab]').forEach(t => t.classList.remove('tab-active'));
            this.classList.add('tab-active');

            // Show/hide content
            document.getElementById('permissions-tab').classList.toggle('hidden', tabName !== 'permissions');
            document.getElementById('flow-tab').classList.toggle('hidden', tabName !== 'flow');
            document.getElementById('role_pr_permissions-tab').classList.toggle('hidden', tabName !== 'role_pr_permissions');
            document.getElementById('status_modal_fields-tab').classList.toggle('hidden', tabName !== 'status_modal_fields');

            // Clear search and load data for active tab
            if (tabName === 'permissions') {
                if (permissionSearch) permissionSearch.value = '';
                loadPermissions();
            } else if (tabName === 'flow') {
                if (flowSearch) flowSearch.value = '';
                loadFlows();
            } else if (tabName === 'role_pr_permissions') {
                if (prPermissionSearch) prPermissionSearch.value = '';
                loadPRPermissions();
            } else if (tabName === 'status_modal_fields') {
                const modalFieldsSearch = document.getElementById('modalFieldsSearch');
                if (modalFieldsSearch) modalFieldsSearch.value = '';
                loadModalFields();
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
    const modalFieldsSearch = document.getElementById('modalFieldsSearch');

    if (permissionSearch) {
        permissionSearch.addEventListener('input', function () {
            filterPermissions(this.value);
        });
    }

    if (flowSearch) {
        flowSearch.addEventListener('input', function () {
            filterFlows(this.value);
        });
    }

    if (prPermissionSearch) {
        prPermissionSearch.addEventListener('input', function () {
            filterPRPermissions(this.value);
        });
    }

    if (modalFieldsSearch) {
        modalFieldsSearch.addEventListener('input', function () {
            filterModalFields(this.value);
        });
    }

    // Add Permission button
    document.getElementById('addPermissionBtn').addEventListener('click', function () {
        openPermissionModal();
    });

    // Add Flow button
    document.getElementById('addFlowBtn').addEventListener('click', function () {
        openFlowModal();
    });

    // Add PR Permission button
    const addPRPermissionBtn = document.getElementById('addPRPermissionBtn');
    if (addPRPermissionBtn) {
        addPRPermissionBtn.addEventListener('click', function () {
            openPRPermissionModal();
        });
    }

    // Add Modal Field button
    const addModalFieldBtn = document.getElementById('addModalFieldBtn');
    if (addModalFieldBtn) {
        addModalFieldBtn.addEventListener('click', function () {
            openModalFieldModal();
        });
    }

    // Permission form submit
    document.getElementById('permissionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        savePermission();
    });

    // Flow form submit
    document.getElementById('flowForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveFlow();
    });

    // PR Permission form submit
    const prPermissionForm = document.getElementById('prPermissionForm');
    if (prPermissionForm) {
        prPermissionForm.addEventListener('submit', function (e) {
            e.preventDefault();
            savePRPermission();
        });
    }

    // Modal Field form submit
    const modalFieldForm = document.getElementById('modalFieldForm');
    if (modalFieldForm) {
        modalFieldForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveModalField();
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
                    // Handle both object format (code/name) and string format for backward compatibility
                    const roleCode = role.code || role;
                    const roleName = role.name || roleCode;
                    option.value = roleCode;
                    option.textContent = roleName;
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
                    <span class="badge ${perm.is_active == 1 ? 'badge-success' : 'badge-error'}">
                        ${perm.is_active == 1 ? 'Active' : 'Inactive'}
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
                    <span class="badge ${flow.requires_proforma == 1 ? 'badge-warning' : 'badge-info'}">
                        ${flow.requires_proforma == 1 ? 'Yes' : 'No'}
                    </span>
                </td>
                <td>${flow.priority}</td>
                <td>
                    <span class="badge ${flow.is_active == 1 ? 'badge-success' : 'badge-error'}">
                        ${flow.is_active == 1 ? 'Active' : 'Inactive'}
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
            const response = await fetch('../api/admin/status-permissions.php?type=role_pr_permissions');
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
    window.openPermissionModal = async function (id = null) {
        const modal = document.getElementById('permissionModal');
        const form = document.getElementById('permissionForm');
        const title = document.getElementById('permissionModalTitle');

        // Get CSRF token
        try {
            const response = await fetch('../auth/get-csrf-token.php');
            const data = await response.json();
            if (data.status === 'success') {
                document.getElementById('permissionCsrfToken').value = data.data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }

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
    window.openFlowModal = async function (id = null) {
        const modal = document.getElementById('flowModal');
        const form = document.getElementById('flowForm');
        const title = document.getElementById('flowModalTitle');

        // Get CSRF token
        try {
            const response = await fetch('../auth/get-csrf-token.php');
            const data = await response.json();
            if (data.status === 'success') {
                document.getElementById('flowCsrfToken').value = data.data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }

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

            // Ensure CSRF token is included
            const csrfToken = document.getElementById('permissionCsrfToken').value;
            if (csrfToken) {
                data['csrf_token'] = csrfToken;
            }

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

            // Ensure CSRF token is included
            const csrfToken = document.getElementById('flowCsrfToken').value;
            if (csrfToken) {
                data['csrf_token'] = csrfToken;
            }

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
    window.editPermission = function (id) {
        openPermissionModal(id);
    };

    // Edit flow
    window.editFlow = function (id) {
        openFlowModal(id);
    };

    // Delete permission
    window.deletePermission = async function (id) {
        const confirmResult = await showConfirm(
            'Are you sure?',
            'Are you sure you want to delete this permission?',
            'Yes, delete it!',
            'Cancel'
        );

        if (!confirmResult.isConfirmed) return;

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
    window.deleteFlow = async function (id) {
        const confirmResult = await showConfirm(
            'Are you sure?',
            'Are you sure you want to delete this flow?',
            'Yes, delete it!',
            'Cancel'
        );

        if (!confirmResult.isConfirmed) return;

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
    window.openPRPermissionModal = async function (id = null) {
        const modal = document.getElementById('prPermissionModal');
        const form = document.getElementById('prPermissionForm');
        const title = document.getElementById('prPermissionModalTitle');

        // Get CSRF token
        try {
            const response = await fetch('../auth/get-csrf-token.php');
            const data = await response.json();
            if (data.status === 'success') {
                document.getElementById('prPermissionCsrfToken').value = data.data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }

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

            // Ensure CSRF token is included
            const csrfToken = document.getElementById('prPermissionCsrfToken').value;
            if (csrfToken) {
                data['csrf_token'] = csrfToken;
            }

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

    // Load Modal Fields
    async function loadModalFields() {
        try {
            const response = await fetch('../api/admin/status-permissions.php?type=status_modal_fields');
            const result = await response.json();
            if (result.status === 'success') {
                modalFields = result.data;
                renderModalFieldsTable();
            }
        } catch (error) {
            console.error('Error loading modal fields:', error);
        }
    }

    // Render Modal Fields table
    function renderModalFieldsTable(filteredData = null) {
        const tbody = document.getElementById('modalFieldsTableBody');
        const dataToRender = filteredData !== null ? filteredData : modalFields;

        if (dataToRender.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No modal fields found</td></tr>';
            return;
        }

        tbody.innerHTML = dataToRender.map(field => {
            const statusName = statuses.find(s => s.id == field.status_id)?.status || `Status ${field.status_id}`;
            const fieldNameMap = {
                'buyer': 'Buyer',
                'po_head': 'PO Head',
                'po_team': 'PO Team',
                'qty': 'Quantity',
                'file_upload': 'File Upload',
                'remark': 'Remark'
            };
            const fieldDisplayName = fieldNameMap[field.field_name] || field.field_name;
            const dbColumnDisplay = field.db_column_name ? `<span class="text-xs text-gray-500">→ ${field.db_column_name}</span>` : '';

            return `
                <tr>
                    <td>${field.id}</td>
                    <td>${statusName}</td>
                    <td>${fieldDisplayName}</td>
                    <td>
                        <span class="badge ${field.is_required == 1 ? 'badge-error' : 'badge-ghost'}">
                            ${field.is_required == 1 ? 'Required' : 'Optional'}
                        </span>
                    </td>
                    <td>${field.field_order}</td>
                    <td>${field.db_column_name || '<span class="text-gray-400">-</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editModalField(${field.id})">Edit</button>
                        <button class="btn btn-sm btn-error" onclick="deleteModalField(${field.id})">Delete</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Filter Modal Fields
    function filterModalFields(searchTerm) {
        if (!searchTerm || searchTerm.trim() === '') {
            renderModalFieldsTable();
            return;
        }

        const term = searchTerm.toLowerCase().trim();
        const filtered = modalFields.filter(field => {
            const statusName = statuses.find(s => s.id == field.status_id)?.status || '';
            const fieldNameMap = {
                'buyer': 'Buyer',
                'po_head': 'PO Head',
                'po_team': 'PO Team',
                'qty': 'Quantity',
                'file_upload': 'File Upload',
                'remark': 'Remark'
            };
            const fieldDisplayName = fieldNameMap[field.field_name] || field.field_name;

            return (
                field.id.toString().includes(term) ||
                statusName.toLowerCase().includes(term) ||
                fieldDisplayName.toLowerCase().includes(term) ||
                field.field_name.toLowerCase().includes(term) ||
                (field.is_required == 1 ? 'required' : 'optional').includes(term) ||
                field.field_order.toString().includes(term)
            );
        });

        renderModalFieldsTable(filtered);
    }

    // Open Modal Field modal
    window.openModalFieldModal = async function (id = null) {
        const modal = document.getElementById('modalFieldModal');
        const form = document.getElementById('modalFieldForm');
        const title = document.getElementById('modalFieldModalTitle');

        // Get CSRF token
        try {
            const response = await fetch('../auth/get-csrf-token.php');
            const data = await response.json();
            if (data.status === 'success') {
                document.getElementById('modalFieldCsrfToken').value = data.data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }

        form.reset();
        document.getElementById('modalFieldId').value = id || '';
        title.textContent = id ? 'Edit Status Modal Field' : 'Add Status Modal Field';

        // Populate status dropdown
        const statusSelect = document.getElementById('modalFieldStatus');
        statusSelect.innerHTML = '<option value="">Select Status</option>';
        statuses.forEach(status => {
            statusSelect.innerHTML += `<option value="${status.id}">${status.status}</option>`;
        });

        // Show/hide db_column_name field based on field_name selection
        const fieldNameSelect = document.getElementById('modalFieldName');
        const dbColumnContainer = document.getElementById('dbColumnNameContainer');

        function toggleDbColumnField() {
            const isRemark = fieldNameSelect.value === 'remark';
            dbColumnContainer.style.display = isRemark ? 'block' : 'none';
            if (!isRemark) {
                document.getElementById('modalFieldDbColumn').value = '';
            }
        }

        fieldNameSelect.addEventListener('change', toggleDbColumnField);

        if (id) {
            const field = modalFields.find(f => f.id == id);
            if (field) {
                document.getElementById('modalFieldStatus').value = field.status_id;
                document.getElementById('modalFieldName').value = field.field_name;
                document.getElementById('modalFieldRequired').checked = field.is_required == 1;
                document.getElementById('modalFieldOrder').value = field.field_order;
                if (field.db_column_name) {
                    document.getElementById('modalFieldDbColumn').value = field.db_column_name;
                }
                toggleDbColumnField();
            }
        } else {
            toggleDbColumnField();
        }

        modal.showModal();
    };

    // Save Modal Field
    async function saveModalField() {
        const form = document.getElementById('modalFieldForm');
        const formData = new FormData(form);
        const id = formData.get('id');

        formData.append('status_id', document.getElementById('modalFieldStatus').value);
        formData.append('field_name', document.getElementById('modalFieldName').value);
        formData.append('is_required', document.getElementById('modalFieldRequired').checked ? '1' : '0');
        formData.append('field_order', document.getElementById('modalFieldOrder').value);
        const dbColumnName = document.getElementById('modalFieldDbColumn').value;
        if (dbColumnName) {
            formData.append('db_column_name', dbColumnName);
        }

        try {
            const url = '../api/admin/status-permissions.php';
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            // Ensure CSRF token is included
            const csrfToken = document.getElementById('modalFieldCsrfToken').value;
            if (csrfToken) {
                data['csrf_token'] = csrfToken;
            }

            const options = {
                method: id ? 'PUT' : 'POST',
                body: new URLSearchParams(data),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            };

            const response = await fetch(url, options);
            const result = await response.json();

            if (result.status === 'success') {
                showAlert('Status modal field saved successfully', 'success');
                document.getElementById('modalFieldModal').close();
                loadModalFields();
            } else {
                showAlert(result.message || 'Failed to save status modal field', 'error');
            }
        } catch (error) {
            console.error('Error saving modal field:', error);
            showAlert('Failed to save status modal field', 'error');
        }
    }

    // Edit Modal Field
    window.editModalField = function (id) {
        openModalFieldModal(id);
    };

    // Delete Modal Field
    window.deleteModalField = async function (id) {
        const confirmResult = await showConfirm(
            'Are you sure?',
            'Are you sure you want to delete this modal field?',
            'Yes, delete it!',
            'Cancel'
        );

        if (!confirmResult.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append('type', 'status_modal_fields');
            formData.append('id', id);

            const response = await fetch('../api/admin/status-permissions.php', {
                method: 'DELETE',
                body: new URLSearchParams(formData),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const result = await response.json();

            if (result.status === 'success') {
                showAlert('Modal field deleted successfully', 'success');
                loadModalFields();
            } else {
                showAlert(result.message || 'Failed to delete modal field', 'error');
            }
        } catch (error) {
            console.error('Error deleting modal field:', error);
            showAlert('Failed to delete modal field', 'error');
        }
    };

    // Edit PR permission
    window.editPRPermission = function (id) {
        openPRPermissionModal(id);
    };

    // Delete PR permission
    window.deletePRPermission = async function (id) {
        const confirmResult = await showConfirm(
            'Are you sure?',
            'Are you sure you want to delete this PR permission?',
            'Yes, delete it!',
            'Cancel'
        );

        if (!confirmResult.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append('type', 'role_pr_permissions');
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
        // Use DaisyUI toast notification
        showToast(message, type, 3000);
    }
});

