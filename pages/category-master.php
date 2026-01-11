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
$currentPage = 'category-master.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Category Master</h1>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-tag"></i>
                <span id="formTitle">Add Category</span>
            </h2>
            <form id="categoryForm" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text">Category Name <span class="text-error">*</span></span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                        <input type="text" name="maincat" id="maincat" class="input input-bordered join-item flex-1" required placeholder="Enter category name">
                    </div>
                </div>
                
                <div class="form-control">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Category</span>
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

    <!-- Categories Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Categories
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
                    <input type="text" id="searchInput" placeholder="Search categories..." class="input input-bordered w-full sm:w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <tr>
                            <td colspan="3" class="text-center">Loading...</td>
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
                <th>Category Name</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
$("#categoryForm").submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const action = $("#categoryId").val() ? "update" : "create";
    const categoryName = $("#maincat").val();

    (async () => {
        const confirmResult = await showConfirm(
            action === "create" ? 'Add New Category?' : 'Update Category?',
            action === "create" 
                ? `Add "${categoryName}" as a new category?` 
                : `Update this category to "${categoryName}"?`,
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
                url: "../api/admin/categories.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success', 2000);
                        resetForm();
                        loadCategories();
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
    deleteCategory();
});

async function deleteCategory() {
    const id = $("#categoryId").val();
    if (!id) {
        showToast('Please select a category to delete first.', 'warning');
        return;
    }

    const categoryName = $("#maincat").val();

    const confirmResult = await showConfirm(
        'Delete Category?',
        `This will permanently delete the category: ${categoryName}\n\nWarning: This action cannot be undone!`,
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        // Get CSRF token
        const csrfResponse = await fetch('../auth/get-csrf-token.php');
        const csrfData = await csrfResponse.json();
        const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
        
        $.post("../api/admin/categories.php", { 
            delete_id: id,
            csrf_token: csrfToken
        }, function(response) {
            if (response.status === 'success') {
                showToast(response.message, 'success', 2000);
                resetForm();
                loadCategories();
            } else {
                showToast(response.message || 'Failed to delete category', 'error');
            }
        }, 'json');
    }
}

function resetForm() {
    $("#categoryForm")[0].reset();
    $("#categoryId").val('');
    $("#formTitle").text('Add Category');
    $("#submitBtnText").text('Add Category');
    $("#cancelBtn").hide();
    $("#deleteBtn").hide();
}

$("#cancelBtn").click(function() {
    resetForm();
});

$("#resetBtn").click(function() {
    resetForm();
});

function loadCategories() {
    $.getJSON("../api/admin/categories.php", function(data) {
        if (data.status === 'success') {
            const categories = data.data || [];
            const tbody = $("#categoryTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            if (categories.length === 0) {
                tbody.html('<tr><td colspan="3" class="text-center">No categories found</td></tr>');
                return;
            }

            tbody.html(categories.map(category => `
                <tr>
                    <td>${category.id}</td>
                    <td><strong>${category.maincat}</strong></td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${category.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${category.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join(''));
            
            categories.forEach(category => {
                exportTable.append(`
                    <tr>
                        <td>${category.id}</td>
                        <td>${category.maincat}</td>
                    </tr>
                `);
            });
        }
    }).fail(function() {
        showToast('Failed to load categories', 'error');
    });
}

// Make functions global for event delegation
window.editCategory = function(id) {
    $.getJSON("../api/admin/categories.php", function(data) {
        if (data.status === 'success') {
            const category = data.data.find(c => c.id == id);
            if (!category) {
                showToast('Category not found', 'error');
                return;
            }
            
            $("#categoryId").val(category.id);
            $("#maincat").val(category.maincat);
            $("#formTitle").text('Edit Category');
            $("#submitBtnText").text('Update Category');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            $("#categoryForm")[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deleteCategoryById = function(id) {
    $.getJSON("../api/admin/categories.php", function(data) {
        if (data.status === 'success') {
            const category = data.data.find(c => c.id == id);
            if (!category) {
                showToast('Category not found', 'error');
                return;
            }
            
            $("#categoryId").val(category.id);
            $("#maincat").val(category.maincat);
            deleteCategory();
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
    const url = `../api/admin/categories.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const categories = responseData.data || data.data || [];
            
            if (categories.length === 0) {
                showToast('No categories found to export', 'warning');
                return;
            }
            
            try {
                const headers = [['ID', 'Category Name']];
                const rows = categories.map(cat => [cat.id, cat.maincat]);
                const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Categories");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Categories_${dateStr}.xlsx`);
                
                showToast('Categories have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load categories for export', 'error');
    });
}

function exportToCSV() {
    // Check if FileSaver is available
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    const searchValue = $("#searchInput").val().trim();
    const url = `../api/admin/categories.php?limit=10000${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const categories = responseData.data || data.data || [];
            
            if (categories.length === 0) {
                showToast('No categories found to export', 'warning');
                return;
            }
            
            try {
                const headers = ['ID', 'Category Name'];
                const csvRows = categories.map(cat => {
                    const row = [cat.id, cat.maincat.replace(/"/g, '""')];
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
                saveAs(blob, `Categories_${dateStr}.csv`);
                
                showToast('Categories have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }
    }).fail(function() {
        showToast('Failed to load categories for export', 'error');
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

let currentPage = 1;
let searchTimeout = null;
const itemsPerPage = 10;

function loadCategories(page = 1, search = '') {
    const searchValue = search || $("#searchInput").val().trim();
    const url = `../api/admin/categories.php?page=${page}&limit=${itemsPerPage}${searchValue ? '&search=' + encodeURIComponent(searchValue) : ''}`;
    
    $.getJSON(url, function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const categories = responseData.data || [];
            const pagination = responseData.pagination || {};
            const tbody = $("#categoryTableBody");
            const exportTable = $("#exportTable tbody");
            
            tbody.empty();
            exportTable.empty();
            
            currentPage = pagination.current_page || page;
            
            if (categories.length === 0) {
                tbody.html('<tr><td colspan="3" class="text-center">No categories found</td></tr>');
                renderPagination(pagination);
                return;
            }

            tbody.html(categories.map(category => `
                <tr>
                    <td>${category.id}</td>
                    <td><strong>${category.maincat}</strong></td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${category.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${category.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join(''));
            
            // For export, we need all data - fetch without pagination
            if (searchValue) {
                $.getJSON(`../api/admin/categories.php?search=${encodeURIComponent(searchValue)}`, function(exportData) {
                    if (exportData.status === 'success') {
                        const allCategories = exportData.data?.data || [];
                        allCategories.forEach(category => {
                            exportTable.append(`
                                <tr>
                                    <td>${category.id}</td>
                                    <td>${category.maincat}</td>
                                </tr>
                            `);
                        });
                    }
                });
            } else {
                categories.forEach(category => {
                    exportTable.append(`
                        <tr>
                            <td>${category.id}</td>
                            <td>${category.maincat}</td>
                        </tr>
                    `);
                });
            }
            
            renderPagination(pagination);
        }
    }).fail(function() {
        showToast('Failed to load categories', 'error');
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
    
    // Previous button
    if (current > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadCategories(${current - 1})">«</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">«</button>`;
    }
    
    // Page numbers
    const maxPages = 5;
    let startPage = Math.max(1, current - Math.floor(maxPages / 2));
    let endPage = Math.min(total, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadCategories(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            paginationHTML += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="join-item btn btn-sm" onclick="loadCategories(${i})">${i}</button>`;
        }
    }
    
    if (endPage < total) {
        if (endPage < total - 1) {
            paginationHTML += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadCategories(${total})">${total}</button>`;
    }
    
    // Next button
    if (current < total) {
        paginationHTML += `<button class="join-item btn btn-sm" onclick="loadCategories(${current + 1})">»</button>`;
    } else {
        paginationHTML += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    paginationHTML += '</div>';
    paginationHTML += `<div class="ml-4 text-sm opacity-70">Showing ${((current - 1) * itemsPerPage) + 1}-${Math.min(current * itemsPerPage, totalItems)} of ${totalItems}</div>`;
    
    container.html(paginationHTML);
}

// Make functions global for event delegation
window.editCategory = function(id) {
    // Fetch all categories to find the one to edit
    $.getJSON("../api/admin/categories.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const categories = responseData.data || data.data || [];
            const category = categories.find(c => c.id == id);
            if (!category) {
                showToast('Category not found', 'error');
                return;
            }
            
            $("#categoryId").val(category.id);
            $("#maincat").val(category.maincat);
            $("#formTitle").text('Edit Category');
            $("#submitBtnText").text('Update Category');
            $("#cancelBtn").show();
            $("#deleteBtn").show();
            
            $("#categoryForm")[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

window.deleteCategoryById = function(id) {
    // Fetch all categories to find the one to delete
    $.getJSON("../api/admin/categories.php?limit=1000", function(data) {
        if (data.status === 'success') {
            const responseData = data.data || {};
            const categories = responseData.data || data.data || [];
            const category = categories.find(c => c.id == id);
            if (!category) {
                showToast('Category not found', 'error');
                return;
            }
            
            $("#categoryId").val(category.id);
            $("#maincat").val(category.maincat);
            deleteCategory();
        }
    });
};

$("#searchInput").on("keyup", function() {
    clearTimeout(searchTimeout);
    const value = $(this).val().trim();
    searchTimeout = setTimeout(function() {
        loadCategories(1, value);
    }, 500);
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.editCategory(id);
    }
});

$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (id) {
        window.deleteCategoryById(id);
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadCategories(currentPage);
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

$(document).ready(function() {
    loadCategories();
});
</script>

<?php include '../common/layout-footer.php'; ?>

