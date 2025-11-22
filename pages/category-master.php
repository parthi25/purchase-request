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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Form Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-tag"></i>
                        <span id="formTitle">Add Category</span>
                    </h2>
                    <form id="categoryForm">
                        <input type="hidden" name="id" id="categoryId">
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Category Name</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                                <input type="text" name="maincat" id="maincat" class="input input-bordered join-item flex-1" required placeholder="Enter category name">
                            </div>
                        </div>
                        
                        <div class="flex justify-between gap-2 mt-4">
                            <button type="button" id="resetBtn" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <div class="flex gap-2">
                                <button type="button" id="deleteBtn" class="btn btn-error">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Categories List -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i> Categories
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
                        </div>
                    </div>
                    
                    <div class="form-control mb-4">
                        <div class="join w-full">
                            <input type="text" id="searchInput" class="input input-bordered join-item flex-1" placeholder="Search categories...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="categoryList" class="space-y-2"></div>
                    
                    <div id="emptyState" class="text-center py-8 hidden">
                        <i class="fas fa-tag text-6xl text-base-content opacity-20 mb-4"></i>
                        <h5 class="text-xl font-semibold">No categories found</h5>
                        <p>Create a new category to get started</p>
                    </div>
                </div>
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

<script src="../assets/js/xlsx.full.min.js"></script>
<script src="../assets/js/FileSaver.min.js"></script>
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
        $.post("../api/admin/categories.php", { delete_id: id }, function(response) {
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
    $(".category-item").removeClass('bg-primary bg-opacity-10');
}

$("#resetBtn").click(function() {
    resetForm();
});

function loadCategories() {
    $.getJSON("../api/admin/categories.php", function(data) {
        if (data.status === 'success') {
            const categories = data.data || [];
            const container = $("#categoryList");
            const exportTable = $("#exportTable tbody");
            
            container.empty();
            exportTable.empty();
            
            if (categories.length === 0) {
                $("#emptyState").removeClass('hidden');
                return;
            }
            
            $("#emptyState").addClass('hidden');

            categories.forEach(category => {
                const item = $(`
                    <div class="card bg-base-200 category-item cursor-pointer hover:bg-base-300 transition" data-id="${category.id}">
                        <div class="card-body py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary text-primary-content rounded w-10">
                                            <span><i class="fas fa-tag"></i></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs opacity-60">ID: ${category.id}</div>
                                        <div class="font-semibold">${category.maincat}</div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="${category.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-error btn-delete" data-id="${category.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                item.find('.btn-edit').click(function(e) {
                    e.stopPropagation();
                    selectCategory(category);
                });
                
                item.find('.btn-delete').click(function(e) {
                    e.stopPropagation();
                    $("#categoryId").val(category.id);
                    $("#maincat").val(category.maincat);
                    deleteCategory();
                });
                
                item.click(function(e) {
                    if (!$(e.target).closest('button').length) {
                        selectCategory(category);
                    }
                });
                
                container.append(item);
                
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

function selectCategory(category) {
    $("#categoryId").val(category.id);
    $("#maincat").val(category.maincat);
    $("#formTitle").text('Edit Category');
    $(".category-item").removeClass('bg-primary bg-opacity-10');
    $(`.category-item[data-id="${category.id}"]`).addClass('bg-primary bg-opacity-10');
    
    $('html, body').animate({
        scrollTop: $("#categoryForm").offset().top - 100
    }, 500);
}

$("#searchInput").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#categoryList .category-item").each(function() {
        const itemText = $(this).text().toLowerCase();
        if (itemText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
    
    const visibleItems = $("#categoryList .category-item").not('.hidden').length;
    if (visibleItems === 0) {
        $("#emptyState").removeClass('hidden');
        $("#emptyState h5").text('No matching categories found');
    } else {
        $("#emptyState").addClass('hidden');
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadCategories();
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

function exportToExcel() {
    // Check if XLSX is available
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    try {
        const exportTable = document.getElementById('exportTable');
        if (!exportTable || exportTable.querySelectorAll('tbody tr').length === 0) {
            showToast('No categories found to export', 'warning');
            return;
        }
        
        const date = new Date();
        const dateStr = date.toISOString().split('T')[0];
        const ws = XLSX.utils.table_to_sheet(exportTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Categories");
        XLSX.writeFile(wb, `Categories_${dateStr}.xlsx`);
        
        showToast('Categories have been exported to Excel', 'success', 1500);
    } catch (error) {
        console.error('Export error:', error);
        showToast('An error occurred while exporting: ' + error.message, 'error');
    }
}

function exportToCSV() {
    // Check if FileSaver is available
    if (typeof saveAs === 'undefined') {
        showToast('FileSaver library not loaded. Please refresh the page.', 'error');
        return;
    }
    
    try {
        const table = document.getElementById('exportTable');
        if (!table || table.querySelectorAll('tbody tr').length === 0) {
            showToast('No categories found to export', 'warning');
            return;
        }
        
        const rows = Array.from(table.querySelectorAll('tr'));
        const headers = Array.from(rows.shift().querySelectorAll('th'))
            .map(header => header.textContent.trim());
        const csvData = rows.map(row => {
            return Array.from(row.querySelectorAll('td'))
                .map(cell => {
                    let text = cell.textContent.trim();
                    if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                        text = `"${text.replace(/"/g, '""')}"`;
                    }
                    return text;
                })
                .join(',');
        });
        csvData.unshift(headers.join(','));
        const csvContent = csvData.join('\n');
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

$('#exportExcel').click(function(e) {
    e.preventDefault();
    exportToExcel();
});

$('#exportCSV').click(function(e) {
    e.preventDefault();
    exportToCSV();
});

$(document).ready(function() {
    loadCategories();
});
</script>

<?php include '../common/layout-footer.php'; ?>

