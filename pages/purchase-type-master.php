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
$currentPage = 'purchase-type-master.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Purchase Type Master</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-tag"></i>
                <span id="formTitle">Add Purchase Type</span>
            </h2>
            <form id="purchaseTypeForm" class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
                <input type="hidden" name="id" id="purchaseTypeId">
                
                <div class="form-control w-full sm:flex-1">
                    <label class="label">
                        <span class="label-text">Purchase Type Name <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                        <input type="text" name="name" id="name" class="input input-bordered join-item flex-1" required placeholder="Enter purchase type name">
                    </div>
                </div>
                
                <div class="form-control w-full sm:w-auto">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm sm:btn-md flex-1 sm:flex-none">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Purchase Type</span>
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

    <!-- Purchase Types Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Purchase Types
                </h2>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <input type="text" id="searchInput" placeholder="Search purchase types..." class="input input-bordered w-full sm:w-64">
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
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Purchase Type Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseTypeTableBody">
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
                <th>Purchase Type Name</th>
            </tr>
        </thead>
        <tbody>        </tbody>
    </table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
$("#purchaseTypeForm").submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const action = $("#purchaseTypeId").val() ? "update" : "create";
    const typeName = $("#name").val();

    (async () => {
        const confirmResult = await showConfirm(
            action === "create" ? 'Add New Purchase Type?' : 'Update Purchase Type?',
            action === "create" 
                ? `Add "${typeName}" as a new purchase type?` 
                : `Update this purchase type to "${typeName}"?`,
            action === "create" ? 'Yes, add it!' : 'Yes, update it!',
            'Cancel'
        );
        
        if (confirmResult.isConfirmed) {
            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);
            $.ajax({
                url: "../api/admin/purchase-types.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success', 2000);
                        resetForm();
                        loadPurchaseTypes();
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
    deletePurchaseType();
});

async function deletePurchaseType() {
    const id = $("#purchaseTypeId").val();
    if (!id) {
        showToast('Please select a purchase type to delete first.', 'warning');
        return;
    }

    const typeName = $("#name").val();

    const confirmResult = await showConfirm(
        'Delete Purchase Type?',
        `This will permanently delete the purchase type: ${typeName}\n\nWarning: This action cannot be undone!`,
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        // Get CSRF token
        const csrfResponse = await fetch('../auth/get-csrf-token.php');
        const csrfData = await csrfResponse.json();
        const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('csrf_token', csrfToken);
        
        $.ajax({
            url: "../api/admin/purchase-types.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'success', 2000);
                    resetForm();
                    loadPurchaseTypes();
                } else {
                    showToast(response.message || 'Failed to delete purchase type', 'error');
                }
            }
        });
    }
}

function resetForm() {
    $("#purchaseTypeForm")[0].reset();
    $("#purchaseTypeId").val('');
    $("#formTitle").text('Add Purchase Type');
    $("#submitBtnText").text('Add Purchase Type');
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

function loadPurchaseTypes(page = 1, search = '') {
    const searchValue = search || $("#searchInput").val().trim();
    const url = `../api/admin/purchase-types.php?action=read_all&page=${page}&limit=${itemsPerPage}${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const purchaseTypes = responseData.data || [];
            const pagination = responseData.pagination || {};
            const tbody = $("#purchaseTypeTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            currentPage = pagination.current_page || page;
            
            if (purchaseTypes.length === 0) {
                tbody.html('<tr><td colspan="3" class="text-center">No purchase types found</td></tr>');
                renderPagination(pagination);
                return;
            }

            tbody.html(purchaseTypes.map(type => `
                <tr>
                    <td>${type.id}</td>
                    <td><strong>${type.name}</strong></td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${type.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${type.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join(''));
            
            renderPagination(pagination);
        }
    }).fail(function() {
        showToast('Failed to load purchase types', 'error');
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
    
    if (current > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadPurchaseTypes(${current - 1})">«</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">«</button>`;
    }
    
    // Page numbers - show first page only if not in range
    if (startPage > 1 && !isMobile) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadPurchaseTypes(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            paginationHTML += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="join-item btn btn-sm" onclick="loadPurchaseTypes(${i})">${i}</button>`;
        }
    }
    
    // Show last page only if not in range and not on mobile
    if (endPage < total && !isMobile) {
        if (endPage < total - 1) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadPurchaseTypes(${total})">${total}</button>`;
    }
    
    if (current < total) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadPurchaseTypes(${current + 1})">»</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    paginationHTML += '</div></div>';
    paginationHTML += `<div class="text-sm opacity-70 text-center sm:text-left sm:ml-4 mt-2 sm:mt-0">Showing ${((current - 1) * itemsPerPage) + 1}-${Math.min(current * itemsPerPage, totalItems)} of ${totalItems}</div>`;
    
    container.html(paginationHTML);
}

// Make functions global for event delegation
window.editPurchaseType = function(id) {
    $.getJSON("../api/admin/purchase-types.php?action=read_all", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const purchaseTypes = responseData.data || data.data || [];
            const type = purchaseTypes.find(t => t.id == id);
            if (!type) {
                showToast('Purchase type not found', 'error');
                return;
            }
            
            $("#purchaseTypeId").val(type.id);
            $("#name").val(type.name);
            $("#formTitle").text('Edit Purchase Type');
            $("#submitBtnText").text('Update Purchase Type');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            $("#purchaseTypeForm")[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deletePurchaseTypeById = function(id) {
    $.getJSON("../api/admin/purchase-types.php?action=read_all", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const purchaseTypes = responseData.data || data.data || [];
            const type = purchaseTypes.find(t => t.id == id);
            if (!type) {
                showToast('Purchase type not found', 'error');
                return;
            }
            
            $("#purchaseTypeId").val(type.id);
            $("#name").val(type.name);
            deletePurchaseType();
        }
    });
};

$("#searchInput").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#purchaseTypeTableBody tr").each(function() {
        const itemText = $(this).text().toLowerCase();
        if (itemText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.editPurchaseType(id);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.deletePurchaseTypeById(id);
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadPurchaseTypes(currentPage);
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

function exportToExcel() {
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/purchase-types.php?action=read_all&limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const purchaseTypes = responseData.data || data.data || [];
            
            if (purchaseTypes.length === 0) {
                showToast('No purchase types found to export', 'warning');
                return;
            }
            
            try {
                const headers = [['ID', 'Purchase Type Name']];
                const rows = purchaseTypes.map(type => [type.id, type.name]);
                const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "PurchaseTypes");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Purchase_Types_${dateStr}.xlsx`);
                
                showToast('Purchase types have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load purchase types for export', 'error');
    });
}

function exportToCSV() {
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/purchase-types.php?action=read_all&limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const purchaseTypes = responseData.data || data.data || [];
            
            if (purchaseTypes.length === 0) {
                showToast('No purchase types found to export', 'warning');
                return;
            }
            
            try {
                const headers = ['ID', 'Purchase Type Name'];
                const csvRows = purchaseTypes.map(type => {
                    const row = [type.id, type.name.replace(/"/g, '""')];
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
                saveAs(blob, `Purchase_Types_${dateStr}.csv`);
                
                showToast('Purchase types have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load purchase types for export', 'error');
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

$(document).ready(function() {
    loadPurchaseTypes();
});
</script>

<?php include '../common/layout-footer.php'; ?>

