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
$currentPage = 'status-master.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Status Master</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-flag"></i>
                <span id="formTitle">Add Status</span>
            </h2>
            <form id="statusForm" class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
                <input type="hidden" name="id" id="statusId">
                
                <div class="form-control w-full sm:flex-1">
                    <label class="label">
                        <span class="label-text">Status Name <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-flag"></i></span>
                        <input type="text" name="status" id="status" class="input input-bordered join-item flex-1" required placeholder="Enter status name">
                    </div>
                </div>
                
                <div class="form-control w-full sm:w-auto">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm sm:btn-md flex-1 sm:flex-none">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Status</span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm sm:btn-md" id="cancelBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline btn-sm sm:btn-md flex-1 sm:flex-none">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="button" id="deleteBtn" class="btn btn-error btn-sm sm:btn-md" style="display: none;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statuses Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Statuses
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
                    <input type="text" id="searchInput" placeholder="Search statuses..." class="input input-bordered w-full sm:w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="statusTableBody">
                        <tr>
                            <td colspan="3" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-2 mt-4" id="paginationContainer">
            </div>
        </div>
    </div>
    
    <!-- Hidden table for exports -->
    <table id="exportTable" class="hidden">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status Name</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
$("#statusForm").submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const action = $("#statusId").val() ? "update" : "create";
    const statusName = $("#status").val();

    (async () => {
        const confirmResult = await showConfirm(
            action === "create" ? 'Add New Status?' : 'Update Status?',
            action === "create" 
                ? `Add "${statusName}" as a new status?` 
                : `Update this status to "${statusName}"?`,
            action === "create" ? 'Yes, add it!' : 'Yes, update it!',
            'Cancel'
        );
        
        if (confirmResult.isConfirmed) {
            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            formData.append('csrf_token', csrfToken);
            
            $.ajax({
                url: "../api/admin/statuses.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success', 2000);
                        resetForm();
                        loadStatuses();
                        // Clear status badge cache to force refresh
                        if (window.StatusBadges) {
                            window.StatusBadges.fetchStatuses().then(() => {
                                window.StatusBadges.init();
                            });
                        }
                    } else {
                        showToast(response.message || 'An error occurred', 'error');
                    }
                },
                error: function(err) {
                    showToast('An error occurred while processing your request.', 'error');
                }
            });
        }
    })();
});

$("#deleteBtn").click(function() {
    deleteStatus();
});

async function deleteStatus() {
    const id = $("#statusId").val();
    if (!id) {
        showToast('Please select a status to delete first.', 'warning');
        return;
    }

    const statusName = $("#status").val();

    const confirmResult = await showConfirm(
        'Delete Status?',
        `This will permanently delete the status: ${statusName}\n\nWarning: This action cannot be undone!`,
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        // Get CSRF token
        const csrfResponse = await fetch('../auth/get-csrf-token.php');
        const csrfData = await csrfResponse.json();
        const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
        
        $.post("../api/admin/statuses.php", { 
            delete_id: id,
            csrf_token: csrfToken
        }, function(response) {
            if (response.status === 'success') {
                showToast(response.message, 'success', 2000);
                resetForm();
                loadStatuses();
                // Clear status badge cache to force refresh
                if (window.StatusBadges) {
                    window.StatusBadges.fetchStatuses().then(() => {
                        window.StatusBadges.init();
                    });
                }
            } else {
                showToast(response.message || 'Failed to delete status', 'error');
            }
        }, 'json');
    }
}

function resetForm() {
    $("#statusForm")[0].reset();
    $("#statusId").val('');
    $("#formTitle").text('Add Status');
    $("#submitBtnText").text('Add Status');
    $("#cancelBtn").hide();
    $("#deleteBtn").hide();
}

$("#cancelBtn").click(function() {
    resetForm();
});

$("#resetBtn").click(function() {
    resetForm();
});

let currentPage = 1;
let searchTimeout = null;
const itemsPerPage = 10;

function loadStatuses(page = 1, search = '') {
    const searchValue = search || $("#searchInput").val().trim();
    const url = `../api/admin/statuses.php?page=${page}&limit=${itemsPerPage}${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const statuses = responseData.data || [];
            const pagination = responseData.pagination || {};
            const tbody = $("#statusTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            currentPage = pagination.current_page || page;
            
            if (statuses.length === 0) {
                tbody.html('<tr><td colspan="3" class="text-center">No statuses found</td></tr>');
                renderPagination(pagination);
                return;
            }

            tbody.html(statuses.map(status => `
                <tr>
                    <td>${status.id}</td>
                    <td><strong>${status.status}</strong></td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${status.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${status.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join(''));
            
            // For export, we need all data - fetch without pagination
            if (searchValue) {
                $.getJSON(`../api/admin/statuses.php?search=${encodeURIComponent(searchValue)}`, function(exportData) {
                    if (exportData.status === 'success') {
                        const allStatuses = exportData.data?.data || [];
                        allStatuses.forEach(status => {
                            exportTable.append(`
                                <tr>
                                    <td>${status.id}</td>
                                    <td>${status.status}</td>
                                </tr>
                            `);
                        });
                    }
                });
            } else {
                statuses.forEach(status => {
                    exportTable.append(`
                        <tr>
                            <td>${status.id}</td>
                            <td>${status.status}</td>
                        </tr>
                    `);
                });
            }
            
            renderPagination(pagination);
        }
    }).fail(function() {
        showToast('Failed to load statuses', 'error');
    });
}

function renderPagination(pagination) {
    const container = $("#paginationContainer");
    if (!pagination || pagination.total_pages <= 1) {
        container.html('');
        return;
    }
    
    const current = pagination.current_page || 1;
    const total = pagination.total_pages || 1;
    const totalItems = pagination.total_items || 0;
    
    // Use fewer pages on mobile
    const isMobile = window.innerWidth < 640;
    const maxPages = isMobile ? 3 : 5;
    let startPage = Math.max(1, current - Math.floor(maxPages / 2));
    let endPage = Math.min(total, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    let paginationHTML = '<div class="overflow-x-auto w-full flex justify-center"><div class="join">';
    
    // Previous button
    if (current > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadStatuses(${current - 1})">«</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">«</button>`;
    }
    
    // Page numbers - show first page only if not in range
    if (startPage > 1 && !isMobile) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadStatuses(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            paginationHTML += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="join-item btn btn-sm" onclick="loadStatuses(${i})">${i}</button>`;
        }
    }
    
    // Show last page only if not in range and not on mobile
    if (endPage < total && !isMobile) {
        if (endPage < total - 1) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadStatuses(${total})">${total}</button>`;
    }
    
    // Next button
    if (current < total) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadStatuses(${current + 1})">»</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    paginationHTML += '</div></div>';
    paginationHTML += `<div class="text-sm opacity-70 text-center sm:text-left sm:ml-4 mt-2 sm:mt-0">Showing ${((current - 1) * itemsPerPage) + 1}-${Math.min(current * itemsPerPage, totalItems)} of ${totalItems}</div>`;
    
    container.html(paginationHTML);
}

// Make functions global for event delegation
window.editStatus = function(id) {
    // Fetch all statuses to find the one to edit
    $.getJSON("../api/admin/statuses.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const statuses = responseData.data || data.data || [];
            const status = statuses.find(s => s.id == id);
            if (!status) {
                showToast('Status not found', 'error');
                return;
            }
            
            $("#statusId").val(status.id);
            $("#status").val(status.status);
            $("#formTitle").text('Edit Status');
            $("#submitBtnText").text('Update Status');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            $("#statusForm")[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deleteStatusById = function(id) {
    // Fetch all statuses to find the one to delete
    $.getJSON("../api/admin/statuses.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const statuses = responseData.data || data.data || [];
            const status = statuses.find(s => s.id == id);
            if (!status) {
                showToast('Status not found', 'error');
                return;
            }
            
            $("#statusId").val(status.id);
            $("#status").val(status.status);
            deleteStatus();
        }
    });
};

function exportToExcel() {
    // Check if XLSX is available
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/statuses.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const statuses = responseData.data || data.data || [];
            
            if (statuses.length === 0) {
                showToast('No statuses found to export', 'warning');
                return;
            }
            
            try {
                const headers = [['ID', 'Status Name']];
                const rows = statuses.map(status => [status.id, status.status]);
                const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Statuses");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Statuses_${dateStr}.xlsx`);
                
                showToast('Statuses have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load statuses for export', 'error');
    });
}

function exportToCSV() {
    // Check if FileSaver is available
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/statuses.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const statuses = responseData.data || data.data || [];
            
            if (statuses.length === 0) {
                showToast('No statuses found to export', 'warning');
                return;
            }
            
            try {
                const headers = ['ID', 'Status Name'];
                const csvRows = statuses.map(status => {
                    const row = [status.id, status.status.replace(/"/g, '""')];
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
                saveAs(blob, `Statuses_${dateStr}.csv`);
                
                showToast('Statuses have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load statuses for export', 'error');
    });
}

$('#exportExcel').click(function(e) {
    e.preventDefault();
    exportToExcel();
});

$('#exportCSV').click(function(e) {
    e.preventDefault();
    exportToCSV();
});

$("#searchInput").on("keyup", function() {
    clearTimeout(searchTimeout);
    const value = $(this).val().trim();
    searchTimeout = setTimeout(function() {
        loadStatuses(1, value);
    }, 500);
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.editStatus(id);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.deleteStatusById(id);
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadStatuses(currentPage);
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

$(document).ready(function() {
    loadStatuses();
});
</script>

<?php include '../common/layout-footer.php'; ?>

