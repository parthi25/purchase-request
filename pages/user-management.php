<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Only superadmin and master can access
if (!in_array($_SESSION['role'], ['super_admin', 'master'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;
$currentPage = 'user-management.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">User Management</h1>
        <input type="hidden" id="currentRole" value="<?= htmlspecialchars($role) ?>">
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4">
                <i class="fas fa-user-plus"></i>
                <span id="formTitle">Add New User</span>
            </h2>
            <form id="userForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Full Name</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user"></i></span>
                        <input type="text" name="fullname" id="fullname" class="input input-bordered join-item flex-1" placeholder="Enter full name" required>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" id="email" class="input input-bordered join-item flex-1" placeholder="Enter email address">
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Phone</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" id="phone" class="input input-bordered join-item flex-1" placeholder="Enter phone number">
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Username</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-at"></i></span>
                        <input type="text" name="username" id="username" class="input input-bordered join-item flex-1" placeholder="Enter username" required>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Password</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="input input-bordered join-item flex-1" placeholder="Required for new users">
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user-shield"></i></span>
                        <select name="role" id="roleSelect" class="select select-bordered join-item flex-1" required>
                            <option value="">Select Role</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active Status</span>
                        <input type="checkbox" name="is_active" id="is_active" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="form-control md:col-span-2 lg:col-span-3 flex flex-row justify-between items-center mt-4">
                    <button type="button" id="resetBtn" class="btn btn-outline">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-center mb-4">
                <h2 class="card-title">
                    <i class="fas fa-users"></i> User List
                </h2>
                <div class="flex gap-2">
                    <div class="form-control">
                        <div class="join">
                            <input type="text" id="searchInput" class="input input-bordered join-item" placeholder="Search users...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a id="exportExcel"><i class="fas fa-file-excel text-success"></i> Export as Excel</a></li>
                            <li><a id="exportCSV"><i class="fas fa-file-csv text-primary"></i> Export as CSV</a></li>
                        </ul>
                    </div>
                    <button id="refreshBtn" class="btn btn-outline">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="emptyState" class="text-center py-8 hidden">
                <i class="fas fa-users text-6xl text-base-content opacity-20 mb-4"></i>
                <h5 class="text-xl font-semibold">No users found</h5>
                <p>Add a new user to get started</p>
            </div>
            <div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4 hidden">
                <button id="prevPage" class="btn btn-sm btn-outline">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <div class="flex gap-1">
                    <span id="pageInfo" class="btn btn-sm btn-disabled"></span>
                </div>
                <button id="nextPage" class="btn btn-sm btn-outline">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/xlsx.full.min.js"></script>
<script src="../assets/js/FileSaver.min.js"></script>
<script>
function loadRoles() {
    $.get('../api/admin/get-roles.php', function(response) {
        if (response.status === 'success' && response.data) {
            let options = '<option value="">Select Role</option>';
            const currentRole = $('#currentRole').val();
            response.data.forEach(role => {
                // Only super_admin can create super_admin
                if (role === 'super_admin' && currentRole !== 'super_admin') {
                    return;
                }
                options += `<option value="${role}">${role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ')}</option>`;
            });
            $('#roleSelect').html(options);
        }
    }, 'json');
}

let currentPage = 1;
let totalPages = 1;
let searchQuery = '';
const itemsPerPage = 10;

function loadUsers(page = 1, search = '') {
    currentPage = page;
    searchQuery = search;
    
    const url = `../api/admin/users.php?action=list&page=${page}&limit=${itemsPerPage}${search ? '&search=' + encodeURIComponent(search) : ''}`;
    
    $.get(url, function(response) {
        const responseData = (typeof response === 'object' && response.data) ? response.data : response;
        const users = responseData?.data || responseData || [];
        const pagination = responseData?.pagination || {};
        
        totalPages = pagination.total_pages || 1;
        currentPage = pagination.current_page || 1;
        
        if (!users || users.length === 0) {
            $('#userTable').addClass('hidden');
            $('#emptyState').removeClass('hidden');
            $('#paginationContainer').addClass('hidden');
            return;
        }
        
        $('#userTable').removeClass('hidden');
        $('#emptyState').addClass('hidden');
        $('#paginationContainer').removeClass('hidden');
        
        let rows = '';
        users.forEach(user => {
            const isActive = user.is_active == 1 || user.is_active === true;
            const statusBadge = isActive ? 
                '<span class="badge badge-success">Active</span>' : 
                '<span class="badge badge-error">Inactive</span>';
            
            rows += `
                <tr data-id="${user.id}">
                    <td>${user.id}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-8">
                                    <span class="text-xs">${(user.fullname || 'U').charAt(0).toUpperCase()}</span>
                                </div>
                            </div>
                            <div>${user.fullname || 'N/A'}</div>
                        </div>
                    </td>
                    <td>${user.email || 'N/A'}</td>
                    <td>${user.phone || 'N/A'}</td>
                    <td>${user.username}</td>
                    <td>${user.role.replace('_', ' ')}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-warning" onclick='editUser(${JSON.stringify(user)})'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm ${isActive ? 'btn-error' : 'btn-success'}" onclick="toggleUserStatus(${user.id}, ${isActive ? 0 : 1})">
                                <i class="fas fa-${isActive ? 'ban' : 'check'}"></i> ${isActive ? 'Deactivate' : 'Activate'}
                            </button>
                            <button class="btn btn-sm btn-error" onclick="deleteUser(${user.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
        $('#userTable tbody').html(rows);
        
        // Update pagination info
        updatePaginationInfo();
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to load users', 'error');
    });
}

function updatePaginationInfo() {
    $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
    $('#prevPage').prop('disabled', currentPage <= 1);
    $('#nextPage').prop('disabled', currentPage >= totalPages);
}

function editUser(user) {
    $('html, body').animate({
        scrollTop: $("#userForm").offset().top - 100
    }, 500);
    
    $('#userId').val(user.id);
    $('#fullname').val(user.fullname);
    $('#email').val(user.email);
    $('#phone').val(user.phone);
    $('#username').val(user.username);
    $('#roleSelect').val(user.role);
    $('#is_active').prop('checked', user.is_active == 1 || user.is_active === true);
    $('#submitBtn').html('<i class="fas fa-save"></i> Update User');
    $('#formTitle').text('Edit User');
    
    $('tr').removeClass('bg-primary bg-opacity-10');
    $(`tr[data-id="${user.id}"]`).addClass('bg-primary bg-opacity-10');
}

function toggleUserStatus(userId, newStatus) {
    const action = newStatus == 1 ? 'activate' : 'deactivate';
    const actionText = newStatus == 1 ? 'activate' : 'deactivate';
    
    Swal.fire({
        title: `Are you sure?`,
        text: `Do you want to ${actionText} this user?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, ${actionText} it!`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: newStatus == 1 ? '#10b981' : '#ef4444',
    }).then(result => {
        if (result.isConfirmed) {
            $.post('../api/admin/users.php', { 
                action: 'toggle_status', 
                id: userId,
                is_active: newStatus
            }, function(response) {
                if (typeof response === 'object' && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || `User ${actionText}d successfully.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadUsers(currentPage, searchQuery);
                } else {
                    const errorMsg = typeof response === 'object' ? response.message : 'Failed to update user status.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMsg,
                    });
                }
            }, 'json').fail(function(xhr) {
                let errorMsg = 'Failed to update user status.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch(e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMsg,
                });
            });
        }
    });
}

function resetForm() {
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#is_active').prop('checked', true);
    $('#submitBtn').html('<i class="fas fa-user-plus"></i> Add User');
    $('#formTitle').text('Add New User');
    $('tr').removeClass('bg-primary bg-opacity-10');
}

function deleteUser(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will not be able to recover this user!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
    }).then(result => {
        if (result.isConfirmed) {
            $.post('../api/admin/users.php', { action: 'delete', id: id }, function(response) {
                if (typeof response === 'object' && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message || 'User has been deleted successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        loadUsers(currentPage, searchQuery);
                    }, 500);
                } else {
                    const errorMsg = typeof response === 'object' ? response.message : 'Failed to delete user.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMsg,
                    });
                }
            }, 'json').fail(function(xhr) {
                let errorMsg = 'Failed to delete user.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch(e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMsg,
                });
            });
        }
    });
}

$('#userForm').submit(function(e) {
    e.preventDefault();
    const form = $(this).serializeArray();
    const action = $('#userId').val() ? 'update' : 'add';
    
    // Add is_active checkbox value
    const isActive = $('#is_active').is(':checked') ? '1' : '0';
    form.push({ name: 'is_active', value: isActive });
    form.push({ name: 'action', value: action });

    Swal.fire({
        title: action === 'add' ? 'Add User?' : 'Update User?',
        text: action === 'add' ? 'New user will be added to the system.' : 'User information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
    }).then(result => {
        if (result.isConfirmed) {
            $.post('../api/admin/users.php', form, function(response) {
                if (typeof response === 'object' && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || `User ${action === 'add' ? 'added' : 'updated'} successfully.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    resetForm();
                    loadUsers(currentPage, searchQuery);
                } else {
                    const errorMsg = typeof response === 'object' ? response.message : 'Failed to process your request.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMsg,
                    });
                }
            }, 'json').fail(function(xhr) {
                let errorMsg = 'Failed to process your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch(e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMsg,
                });
            });
        }
    });
});

$('#resetBtn').click(function() {
    resetForm();
});

$('#refreshBtn').click(function() {
    $(this).find('i').addClass('fa-spin');
    loadUsers(currentPage, searchQuery);
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

let searchTimeout;
$('#searchInput').on('keyup', function() {
    const value = $(this).val().trim();
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        loadUsers(1, value);
    }, 500);
});

$('#prevPage').click(function() {
    if (currentPage > 1) {
        loadUsers(currentPage - 1, searchQuery);
    }
});

$('#nextPage').click(function() {
    if (currentPage < totalPages) {
        loadUsers(currentPage + 1, searchQuery);
    }
});

function exportToExcel() {
    // Fetch all data for export
    const url = `../api/admin/users.php?action=list&page=1&limit=10000${searchQuery ? '&search=' + encodeURIComponent(searchQuery) : ''}`;
    $.get(url, function(response) {
        const responseData = (typeof response === 'object' && response.data) ? response.data : response;
        const users = responseData?.data || responseData || [];
        
        // Create table data
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Username', 'Role', 'Status'];
        const rows = users.map(user => [
            user.id,
            user.fullname || 'N/A',
            user.email || 'N/A',
            user.phone || 'N/A',
            user.username,
            user.role.replace('_', ' '),
            (user.is_active == 1 || user.is_active === true) ? 'Active' : 'Inactive'
        ]);
        
        const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Users");
        const date = new Date();
        const dateStr = date.toISOString().split('T')[0];
        XLSX.writeFile(wb, `User_List_${dateStr}.xlsx`);
        Swal.fire({
            icon: 'success',
            title: 'Export Successful',
            text: 'User list has been exported to Excel',
            timer: 1500,
            showConfirmButton: false
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to export users', 'error');
    });
}

function exportToCSV() {
    // Fetch all data for export
    const url = `../api/admin/users.php?action=list&page=1&limit=10000${searchQuery ? '&search=' + encodeURIComponent(searchQuery) : ''}`;
    $.get(url, function(response) {
        const responseData = (typeof response === 'object' && response.data) ? response.data : response;
        const users = responseData?.data || responseData || [];
        
        // Create CSV data
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Username', 'Role', 'Status'];
        const csvRows = users.map(user => {
            const row = [
                user.id,
                user.fullname || 'N/A',
                user.email || 'N/A',
                user.phone || 'N/A',
                user.username,
                user.role.replace('_', ' '),
                (user.is_active == 1 || user.is_active === true) ? 'Active' : 'Inactive'
            ];
            return row.map(cell => {
                let text = String(cell);
                if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                    text = `"${text.replace(/"/g, '""')}"`;
                }
                return text;
            }).join(',');
        });
        
        csvRows.unshift(headers.join(','));
        const csvContent = csvRows.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const date = new Date();
        const dateStr = date.toISOString().split('T')[0];
        saveAs(blob, `User_List_${dateStr}.csv`);
        Swal.fire({
            icon: 'success',
            title: 'Export Successful',
            text: 'User list has been exported to CSV',
            timer: 1500,
            showConfirmButton: false
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to export users', 'error');
    });
}

$(document).ready(function() {
    loadUsers(1, '');
    loadRoles();
    
    $('#exportExcel').click(function(e) {
        e.preventDefault();
        exportToExcel();
    });
    
    $('#exportCSV').click(function(e) {
        e.preventDefault();
        exportToCSV();
    });
});
</script>

<?php include '../common/layout-footer.php'; ?>

