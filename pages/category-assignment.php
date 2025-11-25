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
$currentPage = 'category-assignment.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Category Assignment</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4">
                <i class="fas fa-link"></i>
                <span id="formTitle">Assign Category</span>
            </h2>
            <form id="assignForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="assignmentId">
                
                <div class="form-control">
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
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Category <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                        <select name="cat_id" id="cat_id" class="select select-bordered join-item flex-1" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-control sm:col-span-2 lg:col-span-3">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Assignment</span>
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

    <!-- Assignments Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    Assignments
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
                    <input type="text" id="searchInput" placeholder="Search assignments..." class="input input-bordered w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Buyer Head</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assignTableBody">
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
                <th>Category</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
$("#assignForm").submit(function(e) {
    e.preventDefault();

    const bHeadSelect = $("#b_head");
    const catIdSelect = $("#cat_id");

    $('input[name="b_head_name"], input[name="cat_name"]').remove();

    const bHeadText = bHeadSelect.find("option:selected").text();
    const catIdText = catIdSelect.find("option:selected").text();

    $("<input>").attr({
        type: "hidden",
        name: "b_head_name", 
        value: bHeadText
    }).appendTo(this);
    
    $("<input>").attr({
        type: "hidden",
        name: "cat_name",
        value: catIdText
    }).appendTo(this);

    const formData = new FormData(this);
    const action = $("#assignmentId").val() ? "update" : "create";

    (async () => {
        const confirmResult = await showConfirm(
            action === "create" ? 'Confirm Assignment' : 'Update Assignment',
            action === "create" ? `Assign "${catIdText}" to "${bHeadText}"?` : `Update this assignment?`,
            'Yes, save it!',
            'Cancel'
        );
        
        if (confirmResult.isConfirmed) {
            $.ajax({
                url: "../api/admin/category-assignment.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success', 2000);
                        resetForm();
                        loadAssignments();
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
    deleteEntry();
});

async function deleteEntry() {
    const id = $("#assignmentId").val();
    if (!id) {
        showToast('Please select an assignment to delete first.', 'warning');
        return;
    }

    const buyerName = $("#b_head option:selected").text();
    const categoryName = $("#cat_id option:selected").text();

    const confirmResult = await showConfirm(
        'Delete Assignment?',
        `This will remove the assignment: ${categoryName} from ${buyerName}`,
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        $.post("../api/admin/category-assignment.php", { delete_id: id }, function(response) {
            if (response.status === 'success') {
                showToast(response.message, 'success', 2000);
                resetForm();
                loadAssignments();
            } else {
                showToast(response.message || 'Failed to delete assignment', 'error');
            }
        }, 'json');
    }
}

function resetForm() {
    $("#assignForm")[0].reset();
    $("#assignmentId").val('');
    $("#formTitle").text('Assign Category');
    $("#submitBtnText").text('Add Assignment');
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
            
            // Ensure data.data is an array before forEach
            const users = (data.data && Array.isArray(data.data)) ? data.data : [];
            users.forEach(user => {
                select.append($("<option>", {
                    value: user.id,
                    text: user.fullname
                }));
            });
        }
    });
}

function loadCategories() {
    $.getJSON("../api/admin/categories.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const select = $("#cat_id");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Category"}));
            
            // Handle both response structures: direct array or nested with pagination
            const categories = (data.data && Array.isArray(data.data)) 
                ? data.data 
                : (data.data && data.data.data) 
                    ? data.data.data 
                    : [];
            
            categories.forEach(cat => {
                select.append($("<option>", {
                    value: cat.id,
                    text: cat.maincat
                }));
            });
        }
    });
}

let currentPage = 1;
let searchTimeout = null;
const itemsPerPage = 10;

function loadAssignments(page = 1, search = '') {
    const searchValue = search || $("#searchInput").val().trim();
    const url = `../api/admin/category-assignment.php?page=${page}&limit=${itemsPerPage}${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const assignments = responseData.data || [];
            const pagination = responseData.pagination || {};
            const tbody = $("#assignTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            currentPage = pagination.current_page || page;
            
            if (assignments.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center">No assignments found</td></tr>');
                renderPagination(pagination);
                return;
            }

            tbody.html(assignments.map(row => `
                <tr>
                    <td>${row.id}</td>
                    <td><strong>${row.buyer_name}</strong></td>
                    <td>${row.cat_name}</td>
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
        showToast('Failed to load assignments', 'error');
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
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadAssignments(${current - 1})">«</button>`;
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
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadAssignments(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            paginationHTML += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="join-item btn btn-sm" onclick="loadAssignments(${i})">${i}</button>`;
        }
    }
    
    if (endPage < total) {
        if (endPage < total - 1) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadAssignments(${total})">${total}</button>`;
    }
    
    if (current < total) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadAssignments(${current + 1})">»</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    paginationHTML += '</div>';
    paginationHTML += `<div class="ml-4 text-sm opacity-70">Showing ${((current - 1) * itemsPerPage) + 1}-${Math.min(current * itemsPerPage, totalItems)} of ${totalItems}</div>`;
    
    container.html(paginationHTML);
}

function ensureOptionExists(selectId, value, text) {
    const select = $(selectId);
    if (select.find(`option[value="${value}"]`).length === 0) {
        select.append($("<option>", {
            value: value,
            text: text
        }));
    }
}

// Make functions global for event delegation
window.editAssignment = function(id) {
    $.getJSON("../api/admin/category-assignment.php?limit=1000", function(data) {
        if (data.status === 'success') {
            // Handle nested response structure: data.data.data
            const responseData = data.data || {};
            const assignments = (responseData.data && Array.isArray(responseData.data)) 
                ? responseData.data 
                : (Array.isArray(data.data)) 
                    ? data.data 
                    : [];
            
            const row = assignments.find(r => r.id == id);
            if (!row) {
                showToast('Assignment not found', 'error');
                return;
            }
            
            $("#assignmentId").val(row.id);
            $("#formTitle").text('Edit Assignment');
            $("#submitBtnText").text('Update Assignment');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            ensureOptionExists("#b_head", row.user_id, row.buyer_name);
            $("#b_head").val(row.user_id);
            
            ensureOptionExists("#cat_id", row.cat_id, row.cat_name);
            $("#cat_id").val(row.cat_id);
            
            $("#assignForm")[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deleteAssignmentById = function(id) {
    $.getJSON("../api/admin/category-assignment.php?limit=1000", function(data) {
        if (data.status === 'success') {
            // Handle nested response structure: data.data.data
            const responseData = data.data || {};
            const assignments = (responseData.data && Array.isArray(responseData.data)) 
                ? responseData.data 
                : (Array.isArray(data.data)) 
                    ? data.data 
                    : [];
            
            const row = assignments.find(r => r.id == id);
            if (!row) {
                showToast('Assignment not found', 'error');
                return;
            }
            
            $("#assignmentId").val(row.id);
            ensureOptionExists("#b_head", row.user_id, row.buyer_name);
            $("#b_head").val(row.user_id);
            ensureOptionExists("#cat_id", row.cat_id, row.cat_name);
            $("#cat_id").val(row.cat_id);
            deleteEntry();
        }
    });
};

$("#searchInput").on("keyup", function() {
    clearTimeout(searchTimeout);
    const value = $(this).val().trim();
    searchTimeout = setTimeout(function() {
        loadAssignments(1, value);
    }, 500);
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.editAssignment(id);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.deleteAssignmentById(id);
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadAssignments(currentPage);
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
    const url = `../api/admin/category-assignment.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            // Handle nested response structure: data.data.data
            const responseData = data.data || {};
            const assignments = (responseData.data && Array.isArray(responseData.data)) 
                ? responseData.data 
                : (Array.isArray(data.data)) 
                    ? data.data 
                    : [];
            
            if (assignments.length === 0) {
                showToast('No assignments found to export', 'warning');
                return;
            }
            
            try {
                const headers = [['ID', 'Buyer Head', 'Category']];
                const rows = assignments.map(row => [row.id, row.buyer_name, row.cat_name]);
                const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Assignments");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Category_Assignments_${dateStr}.xlsx`);
                
                showToast('Assignments have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load assignments for export', 'error');
    });
}

function exportToCSV() {
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/category-assignment.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            // Handle nested response structure: data.data.data
            const responseData = data.data || {};
            const assignments = (responseData.data && Array.isArray(responseData.data)) 
                ? responseData.data 
                : (Array.isArray(data.data)) 
                    ? data.data 
                    : [];
            
            if (assignments.length === 0) {
                showToast('No assignments found to export', 'warning');
                return;
            }
            
            try {
                const headers = ['ID', 'Buyer Head', 'Category'];
                const csvRows = assignments.map(row => {
                    const rowData = [row.id, row.buyer_name.replace(/"/g, '""'), row.cat_name.replace(/"/g, '""')];
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
                saveAs(blob, `Category_Assignments_${dateStr}.csv`);
                
                showToast('Assignments have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load assignments for export', 'error');
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
    loadCategories();
    loadAssignments();
});
</script>

<?php include '../common/layout-footer.php'; ?>

