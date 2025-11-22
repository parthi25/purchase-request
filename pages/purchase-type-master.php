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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Form Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-tag"></i>
                        <span id="formTitle">Add Purchase Type</span>
                    </h2>
                    <form id="purchaseTypeForm">
                        <input type="hidden" name="id" id="purchaseTypeId">
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Purchase Type Name</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                                <input type="text" name="name" id="name" class="input input-bordered join-item flex-1" required placeholder="Enter purchase type name">
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
        
        <!-- Purchase Types List -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i> Purchase Types
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
                            <input type="text" id="searchInput" class="input input-bordered join-item flex-1" placeholder="Search purchase types...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="purchaseTypeList" class="space-y-2"></div>
                    
                    <div id="emptyState" class="text-center py-8 hidden">
                        <i class="fas fa-tag text-6xl text-base-content opacity-20 mb-4"></i>
                        <h5 class="text-xl font-semibold">No purchase types found</h5>
                        <p>Create a new purchase type to get started</p>
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
                <th>Purchase Type Name</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="../assets/js/xlsx.full.min.js"></script>
<script src="../assets/js/FileSaver.min.js"></script>
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
            formData.append('action', action);
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
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
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
    $(".purchase-type-item").removeClass('bg-primary bg-opacity-10');
}

$("#resetBtn").click(function() {
    resetForm();
});

function loadPurchaseTypes() {
    $.getJSON("../api/admin/purchase-types.php?action=read_all", function(data) {
        if (data.status === 'success') {
            const purchaseTypes = data.data || [];
            const container = $("#purchaseTypeList");
            const exportTable = $("#exportTable tbody");
            
            container.empty();
            exportTable.empty();
            
            if (purchaseTypes.length === 0) {
                $("#emptyState").removeClass('hidden');
                return;
            }
            
            $("#emptyState").addClass('hidden');

            purchaseTypes.forEach(type => {
                const item = $(`
                    <div class="card bg-base-200 purchase-type-item cursor-pointer hover:bg-base-300 transition" data-id="${type.id}">
                        <div class="card-body py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary text-primary-content rounded w-10">
                                            <span><i class="fas fa-tag"></i></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs opacity-60">ID: ${type.id}</div>
                                        <div class="font-semibold">${type.name}</div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="${type.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-error btn-delete" data-id="${type.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                item.find('.btn-edit').click(function(e) {
                    e.stopPropagation();
                    selectPurchaseType(type);
                });
                
                item.find('.btn-delete').click(function(e) {
                    e.stopPropagation();
                    $("#purchaseTypeId").val(type.id);
                    $("#name").val(type.name);
                    deletePurchaseType();
                });
                
                item.click(function(e) {
                    if (!$(e.target).closest('button').length) {
                        selectPurchaseType(type);
                    }
                });
                
                container.append(item);
                
                exportTable.append(`
                    <tr>
                        <td>${type.id}</td>
                        <td>${type.name}</td>
                    </tr>
                `);
            });
        }
    }).fail(function() {
        showToast('Failed to load purchase types', 'error');
    });
}

function selectPurchaseType(type) {
    $("#purchaseTypeId").val(type.id);
    $("#name").val(type.name);
    $("#formTitle").text('Edit Purchase Type');
    $(".purchase-type-item").removeClass('bg-primary bg-opacity-10');
    $(`.purchase-type-item[data-id="${type.id}"]`).addClass('bg-primary bg-opacity-10');
    
    $('html, body').animate({
        scrollTop: $("#purchaseTypeForm").offset().top - 100
    }, 500);
}

$("#searchInput").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#purchaseTypeList .purchase-type-item").each(function() {
        const itemText = $(this).text().toLowerCase();
        if (itemText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
    
    const visibleItems = $("#purchaseTypeList .purchase-type-item").not('.hidden').length;
    if (visibleItems === 0) {
        $("#emptyState").removeClass('hidden');
        $("#emptyState h5").text('No matching purchase types found');
    } else {
        $("#emptyState").addClass('hidden');
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadPurchaseTypes();
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
            showToast('No purchase types found to export', 'warning');
            return;
        }
        
        const date = new Date();
        const dateStr = date.toISOString().split('T')[0];
        const ws = XLSX.utils.table_to_sheet(exportTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "PurchaseTypes");
        XLSX.writeFile(wb, `Purchase_Types_${dateStr}.xlsx`);
        
        showToast('Purchase types have been exported to Excel', 'success', 1500);
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
            showToast('No purchase types found to export', 'warning');
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
        saveAs(blob, `Purchase_Types_${dateStr}.csv`);
        
        showToast('Purchase types have been exported to CSV', 'success', 1500);
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
    loadPurchaseTypes();
});
</script>

<?php include '../common/layout-footer.php'; ?>

