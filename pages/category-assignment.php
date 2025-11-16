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

<div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Category Assignment</h1>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-link"></i>
                        <span id="formTitle">Assign Category</span>
                    </h2>
                    <form id="assignForm">
                        <input type="hidden" name="id" id="assignmentId">
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Buyer Head</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user-tie"></i></span>
                                <select name="b_head" id="b_head" class="select select-bordered join-item flex-1" required>
                                    <option value="">Select Buyer Head</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Category</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-tag"></i></span>
                                <select name="cat_id" id="cat_id" class="select select-bordered join-item flex-1" required>
                                    <option value="">Select Category</option>
                                </select>
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
        
        <!-- Assignments List -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">
                            <i class="fas fa-list-check"></i> Assignments
                        </h2>
                        <div class="flex gap-2">
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
                    
                    <div class="form-control mb-4">
                        <div class="join w-full">
                            <input type="text" id="searchInput" class="input input-bordered join-item flex-1" placeholder="Search assignments...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="assignList" class="space-y-2"></div>
                    
                    <div id="emptyState" class="text-center py-8 hidden">
                        <i class="fas fa-link text-6xl text-base-content opacity-20 mb-4"></i>
                        <h5 class="text-xl font-semibold">No assignments found</h5>
                        <p>Create a new assignment to get started</p>
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

    Swal.fire({
        title: 'Confirm Assignment',
        text: `Assign "${catIdText}" to "${bHeadText}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, assign it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "../api/admin/category-assignment.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        resetForm();
                        loadAssignments();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred',
                        });
                    }
                },
                error: function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request.',
                    });
                }
            });
        }
    });
});

$("#deleteBtn").click(function() {
    deleteEntry();
});

function deleteEntry() {
    const id = $("#assignmentId").val();
    if (!id) {
        return Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select an assignment to delete first.',
        });
    }

    const buyerName = $("#b_head option:selected").text();
    const categoryName = $("#cat_id option:selected").text();

    Swal.fire({
        title: 'Delete Assignment?',
        html: `This will remove the assignment:<br>
              <strong>${categoryName}</strong> from <strong>${buyerName}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("../api/admin/category-assignment.php", { delete_id: id }, function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    resetForm();
                    loadAssignments();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to delete assignment',
                    });
                }
            }, 'json');
        }
    });
}

function resetForm() {
    $("#assignForm")[0].reset();
    $("#assignmentId").val('');
    $("#formTitle").text('Assign Category');
    $(".assignment-item").removeClass('bg-primary bg-opacity-10');
}

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

function loadCategories() {
    $.getJSON("../api/admin/categories.php", function(data) {
        if (data.status === 'success') {
            const select = $("#cat_id");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Category"}));
            
            data.data.forEach(cat => {
                select.append($("<option>", {
                    value: cat.id,
                    text: cat.maincat
                }));
            });
        }
    });
}

function loadAssignments() {
    $.get("../api/admin/category-assignment.php", function(response) {
        let data;
        try {
            data = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error("Invalid JSON:", e, response);
            return Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server returned invalid data.',
            });
        }

        const container = $("#assignList");
        const exportTable = $("#exportTable tbody");
        
        container.empty();
        exportTable.empty();
        
        if (data.status === 'success' && data.data && data.data.length === 0) {
            $("#emptyState").removeClass('hidden');
            return;
        }
        
        if (data.status !== 'success' || !data.data) {
            $("#emptyState").removeClass('hidden');
            return;
        }
        
        $("#emptyState").addClass('hidden');

        data.data.forEach(row => {
            const item = $(`
                <div class="card bg-base-200 assignment-item cursor-pointer hover:bg-base-300 transition" data-id="${row.id}">
                    <div class="card-body py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded w-10">
                                        <span><i class="fas fa-link"></i></span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs opacity-60">ID: ${row.id}</div>
                                    <div class="font-semibold">${row.buyer_name}</div>
                                    <div class="text-sm opacity-70"><i class="fas fa-tag"></i> ${row.cat_name}</div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-error btn-delete" data-id="${row.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            item.find('.btn-edit').click(function(e) {
                e.stopPropagation();
                selectAssignment(row);
            });
            
            item.find('.btn-delete').click(function(e) {
                e.stopPropagation();
                $("#assignmentId").val(row.id);
                ensureOptionExists("#b_head", row.user_id, row.buyer_name);
                $("#b_head").val(row.user_id);
                ensureOptionExists("#cat_id", row.cat_id, row.cat_name);
                $("#cat_id").val(row.cat_id);
                deleteEntry();
            });
            
            item.click(function(e) {
                if (!$(e.target).closest('button').length) {
                    selectAssignment(row);
                }
            });
            
            container.append(item);
            
            exportTable.append(`
                <tr>
                    <td>${row.id}</td>
                    <td>${row.buyer_name}</td>
                    <td>${row.cat_name}</td>
                </tr>
            `);
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to load assignments', 'error');
    });
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

function selectAssignment(row) {
    $("#assignmentId").val(row.id);
    $("#formTitle").text('Edit Assignment');
    
    ensureOptionExists("#b_head", row.user_id, row.buyer_name);
    $("#b_head").val(row.user_id);
    
    ensureOptionExists("#cat_id", row.cat_id, row.cat_name);
    $("#cat_id").val(row.cat_id);
    
    $(".assignment-item").removeClass('bg-primary bg-opacity-10');
    $(`.assignment-item[data-id="${row.id}"]`).addClass('bg-primary bg-opacity-10');
    
    $('html, body').animate({
        scrollTop: $("#assignForm").offset().top - 100
    }, 500);
}

$("#searchInput").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#assignList .assignment-item").each(function() {
        const itemText = $(this).text().toLowerCase();
        if (itemText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
    
    const visibleItems = $("#assignList .assignment-item").not('.hidden').length;
    if (visibleItems === 0) {
        $("#emptyState").removeClass('hidden');
        $("#emptyState h5").text('No matching assignments found');
    } else {
        $("#emptyState").addClass('hidden');
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadAssignments();
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

function exportToExcel() {
    const date = new Date();
    const dateStr = date.toISOString().split('T')[0];
    const ws = XLSX.utils.table_to_sheet(document.getElementById('exportTable'));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Assignments");
    XLSX.writeFile(wb, `Category_Assignments_${dateStr}.xlsx`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Assignments have been exported to Excel',
        timer: 1500,
        showConfirmButton: false
    });
}

function exportToCSV() {
    const table = document.getElementById('exportTable');
    const rows = Array.from(table.querySelectorAll('tr'));
    const headers = Array.from(rows.shift().querySelectorAll('th'))
        .map(header => header.textContent.trim());
    const csvData = rows.map(row => {
        return Array.from(row.querySelectorAll('td'))
            .map(cell => {
                let text = cell.textContent.trim();
                if (text.includes(',')) {
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
    saveAs(blob, `Category_Assignments_${dateStr}.csv`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Assignments have been exported to CSV',
        timer: 1500,
        showConfirmButton: false
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

