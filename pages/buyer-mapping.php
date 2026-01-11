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
$currentPage = 'buyer-mapping.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Buyer Mapping</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-users"></i>
                <span id="formTitle">Map Buyer</span>
            </h2>
            <form id="buyerMappingForm" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="id" id="mappingId">
                
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text">Buyer Head <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user-tie"></i></span>
                        <select name="b_head" id="b_head" class="select select-bordered join-item flex-1" required>
                            <option value="">Select Buyer Head</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text">Buyer <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user"></i></span>
                        <select name="buyer" id="buyer" class="select select-bordered join-item flex-1" required>
                            <option value="">Select Buyer</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-control">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Mapping</span>
                        </button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="button" id="deleteBtn" class="btn btn-error" style="display: none;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mappings Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Buyer Mappings
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
                    <input type="text" id="searchInput" placeholder="Search mappings..." class="input input-bordered w-full sm:w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Buyer Head</th>
                            <th>Buyer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mappingTableBody">
                        <tr>
                            <td colspan="4" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="flex justify-center items-center gap-2 mt-4" id="paginationContainer">
            </div>
        </div>
    </div>
    
    <!-- Hidden table for exports -->
    <table id="exportTable" class="hidden">
        <thead>
            <tr>
                <th>ID</th>
                <th>Buyer Head</th>
                <th>Buyer</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
$("#buyerMappingForm").submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const action = $("#mappingId").val() ? "update" : "create";
    
    (async () => {
        const confirmResult = await showConfirm(
            action === "create" ? 'Confirm Mapping' : 'Update Mapping',
            action === "create" ? 'Create this buyer mapping?' : 'Update this buyer mapping?',
            'Yes, save it!',
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
                url: "../api/admin/buyer-mapping.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success', 2000);
                        resetForm();
                        loadMappings();
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
    deleteMapping();
});

async function deleteMapping() {
    const id = $("#mappingId").val();
    if (!id) {
        showToast('Please select a mapping to delete first.', 'warning');
        return;
    }

    const confirmResult = await showConfirm(
        'Delete Mapping?',
        'This will permanently delete this buyer mapping.',
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        // Get CSRF token
        const csrfResponse = await fetch('../auth/get-csrf-token.php');
        const csrfData = await csrfResponse.json();
        const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
        
        $.post("../api/admin/buyer-mapping.php", { 
            delete_id: id,
            csrf_token: csrfToken
        }, function(response) {
            if (response.status === 'success') {
                showToast(response.message, 'success', 2000);
                resetForm();
                loadMappings();
            } else {
                showToast(response.message || 'Failed to delete mapping', 'error');
            }
        }, 'json');
    }
}

function resetForm() {
    $("#buyerMappingForm")[0].reset();
    $("#mappingId").val('');
    $("#formTitle").text('Map Buyer');
    $("#submitBtnText").text('Add Mapping');
    $("#cancelBtn").hide();
    $("#deleteBtn").hide();
}

$("#cancelBtn").click(function() {
    resetForm();
});

$("#resetBtn").click(function() {
    resetForm();
});

function loadBuyerHeads() {
    $.getJSON("../api/admin/get-users.php?role=B_Head", function(data) {
        if (data.status === 'success') {
            const select = $("#b_head");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Buyer Head"}));
            
            data.data.forEach(user => {
                select.append($("<option>", {
                    value: user.id,
                    text: user.fullname
                }));
            });
        }
    });
}

function loadBuyers() {
    $.getJSON("../api/admin/get-users.php?role=buyer", function(data) {
        if (data.status === 'success') {
            const select = $("#buyer");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Buyer"}));
            
            data.data.forEach(user => {
                select.append($("<option>", {
                    value: user.id,
                    text: user.fullname
                }));
            });
        }
    });
}

let currentPage = 1;
let searchTimeout = null;
const itemsPerPage = 10;

function loadMappings(page = 1, search = '') {
    const searchValue = search || $("#searchInput").val().trim();
    const url = `../api/admin/buyer-mapping.php?page=${page}&limit=${itemsPerPage}${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const mappings = responseData.data || [];
            const pagination = responseData.pagination || {};
            const tbody = $("#mappingTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            currentPage = pagination.current_page || page;
            
            if (mappings.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center">No mappings found</td></tr>');
                renderPagination(pagination);
                return;
            }

            tbody.html(mappings.map(row => `
                <tr>
                    <td>${row.id}</td>
                    <td><strong>${row.b_head_name}</strong></td>
                    <td>${row.buyer_name}</td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join(''));
            
            renderPagination(pagination);
        }
    }, 'json').fail(function() {
        showToast('Failed to load mappings', 'error');
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
    
    let paginationHTML = '<div class="join">';
    
    if (current > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadMappings(${current - 1})">«</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">«</button>`;
    }
    
    const maxPages = 5;
    let startPage = Math.max(1, current - Math.floor(maxPages / 2));
    let endPage = Math.min(total, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadMappings(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            paginationHTML += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="join-item btn btn-sm" onclick="loadMappings(${i})">${i}</button>`;
        }
    }
    
    if (endPage < total) {
        if (endPage < total - 1) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadMappings(${total})">${total}</button>`;
    }
    
    if (current < total) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadMappings(${current + 1})">»</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    paginationHTML += '</div>';
    paginationHTML += `<div class="ml-4 text-sm opacity-70">Showing ${((current - 1) * itemsPerPage) + 1}-${Math.min(current * itemsPerPage, totalItems)} of ${totalItems}</div>`;
    
    container.html(paginationHTML);
}

// Make functions global for event delegation
window.editMapping = function(id) {
    $.getJSON("../api/admin/buyer-mapping.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const mappings = responseData.data || data.data || [];
            const row = mappings.find(r => r.id == id);
            if (!row) {
                showToast('Mapping not found', 'error');
                return;
            }
            
            $("#mappingId").val(row.id);
            $("#formTitle").text('Edit Mapping');
            $("#submitBtnText").text('Update Mapping');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            ensureOptionExists("#b_head", row.b_head, row.b_head_name);
            $("#b_head").val(row.b_head);
            
            ensureOptionExists("#buyer", row.buyer, row.buyer_name);
            $("#buyer").val(row.buyer);
            
            $("#buyerMappingForm").scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deleteMappingById = function(id) {
    $("#mappingId").val(id);
    deleteMapping();
};

function ensureOptionExists(selectId, value, text) {
    const select = $(selectId);
    if (select.find(`option[value="${value}"]`).length === 0) {
        select.append($("<option>", {
            value: value,
            text: text
        }));
    }
}

$("#searchInput").on("keyup", function() {
    clearTimeout(searchTimeout);
    const value = $(this).val().trim();
    searchTimeout = setTimeout(function() {
        loadMappings(1, value);
    }, 500);
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.editMapping(id);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.deleteMappingById(id);
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadMappings(currentPage);
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
    const url = `../api/admin/buyer-mapping.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const mappings = responseData.data || data.data || [];
            
            if (mappings.length === 0) {
                showToast('No mappings found to export', 'warning');
                return;
            }
            
            try {
                const headers = [['ID', 'Buyer Head', 'Buyer']];
                const rows = mappings.map(row => [row.id, row.b_head_name, row.buyer_name]);
                const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Mappings");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Buyer_Mappings_${dateStr}.xlsx`);
                
                showToast('Mappings have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load mappings for export', 'error');
    });
}

function exportToCSV() {
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/buyer-mapping.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const mappings = responseData.data || data.data || [];
            
            if (mappings.length === 0) {
                showToast('No mappings found to export', 'warning');
                return;
            }
            
            try {
                const headers = ['ID', 'Buyer Head', 'Buyer'];
                const csvRows = mappings.map(row => {
                    const rowData = [row.id, row.b_head_name.replace(/"/g, '""'), row.buyer_name.replace(/"/g, '""')];
                    return rowData.map(cell => {
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
                saveAs(blob, `Buyer_Mappings_${dateStr}.csv`);
                
                showToast('Mappings have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load mappings for export', 'error');
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
    loadBuyerHeads();
    loadBuyers();
    loadMappings();
});
</script>

<?php include '../common/layout-footer.php'; ?>

