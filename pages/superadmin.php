<?php
// Only super_admin and master can access
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'master'])) {
    header("Location: ../index.php");
    exit;
}
include '../common/layout.php'; 
?>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Status Flow Management</h1>
    </div>
    
    <!-- Tabs for Permissions and Flow -->
    <div class="overflow-x-auto no-scrollbar mb-6">
        <div class="tabs tabs-boxed whitespace-nowrap">
            <a class="tab tab-active" data-tab="permissions">Status Permissions</a>
            <a class="tab" data-tab="flow">Status Flow</a>
            <a class="tab" data-tab="role_pr_permissions">PR Permissions</a>
            <a class="tab" data-tab="status_modal_fields">Status Modal Fields</a>
        </div>
    </div>

        <!-- Status Permissions Tab -->
        <div id="permissions-tab">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="text-2xl font-semibold">Status Permissions</h2>
                <div class="flex gap-2 items-center">
                    <input type="text" id="permissionSearch" placeholder="Search permissions..." class="input input-bordered w-64">
                    <button id="addPermissionBtn" class="btn btn-primary">Add Permission</button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="permissionsTableBody">
                        <tr>
                            <td colspan="5" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Flow Tab -->
        <div id="flow-tab" class="hidden">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="text-2xl font-semibold">Status Flow</h2>
                <div class="flex gap-2 items-center">
                    <input type="text" id="flowSearch" placeholder="Search flows..." class="input input-bordered w-64">
                    <button id="addFlowBtn" class="btn btn-primary">Add Flow</button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From Status</th>
                            <th>To Status</th>
                            <th>Role</th>
                            <th>Requires Proforma</th>
                            <th>Priority</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="flowTableBody">
                        <tr>
                            <td colspan="8" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PR Permissions Tab -->
        <div id="role_pr_permissions-tab" class="hidden">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="text-2xl font-semibold">PR Permissions</h2>
                <div class="flex gap-2 items-center">
                    <input type="text" id="prPermissionSearch" placeholder="Search PR permissions..." class="input input-bordered w-64">
                    <button id="addPRPermissionBtn" class="btn btn-primary">Add PR Permission</button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Can Create</th>
                            <th>Can Edit</th>
                            <th>Edit Status</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="prPermissionsTableBody">
                        <tr>
                            <td colspan="7" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Modal Fields Tab -->
        <div id="status_modal_fields-tab" class="hidden">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="text-2xl font-semibold">Status Modal Fields</h2>
                <div class="flex gap-2 items-center">
                    <input type="text" id="modalFieldsSearch" placeholder="Search fields..." class="input input-bordered w-64">
                    <button id="addModalFieldBtn" class="btn btn-primary">Add Field</button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Field Name</th>
                            <th>Required</th>
                            <th>Order</th>
                            <th>DB Column</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="modalFieldsTableBody">
                        <tr>
                            <td colspan="7" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
        </div>
    </div>

    <!-- Permission Modal -->
    <dialog id="permissionModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4" id="permissionModalTitle">Add Permission</h3>
            <form id="permissionForm">
                <input type="hidden" id="permissionId" name="id">
                <input type="hidden" name="type" value="permission">
                <input type="hidden" name="csrf_token" id="permissionCsrfToken" value="">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <select id="permissionRole" name="role" class="select select-bordered w-full" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <select id="permissionStatus" name="status_id" class="select select-bordered w-full" required>
                        <option value="">Select Status</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active</span>
                        <input type="checkbox" id="permissionActive" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('permissionModal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Flow Modal -->
    <dialog id="flowModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4" id="flowModalTitle">Add Flow</h3>
            <form id="flowForm">
                <input type="hidden" id="flowId" name="id">
                <input type="hidden" name="type" value="flow">
                <input type="hidden" name="csrf_token" id="flowCsrfToken" value="">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">From Status</span>
                    </label>
                    <select id="flowFromStatus" name="from_status_id" class="select select-bordered w-full" required>
                        <option value="">Select From Status</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">To Status</span>
                    </label>
                    <select id="flowToStatus" name="to_status_id" class="select select-bordered w-full" required>
                        <option value="">Select To Status</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <select id="flowRole" name="role" class="select select-bordered w-full" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Requires Proforma</span>
                        <input type="checkbox" id="flowRequiresProforma" class="toggle toggle-primary">
                    </label>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Priority</span>
                    </label>
                    <input type="number" id="flowPriority" name="priority" class="input input-bordered w-full" value="0" min="0">
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active</span>
                        <input type="checkbox" id="flowActive" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('flowModal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- PR Permission Modal -->
    <dialog id="prPermissionModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4" id="prPermissionModalTitle">Add PR Permission</h3>
            <form id="prPermissionForm">
                <input type="hidden" id="prPermissionId" name="id">
                <input type="hidden" name="type" value="role_pr_permissions">
                <input type="hidden" name="csrf_token" id="prPermissionCsrfToken" value="">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <select id="prPermissionRole" name="role" class="select select-bordered w-full" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Can Create PR</span>
                        <input type="checkbox" id="prPermissionCanCreate" class="toggle toggle-primary">
                    </label>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Can Edit PR</span>
                        <input type="checkbox" id="prPermissionCanEdit" class="toggle toggle-primary">
                    </label>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Can Edit When Status (leave empty for any status)</span>
                    </label>
                    <select id="prPermissionEditStatus" name="can_edit_status" class="select select-bordered w-full">
                        <option value="">Any Status</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Active</span>
                        <input type="checkbox" id="prPermissionActive" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('prPermissionModal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Status Modal Fields Modal -->
    <dialog id="modalFieldModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4" id="modalFieldModalTitle">Add Status Modal Field</h3>
            <form id="modalFieldForm">
                <input type="hidden" id="modalFieldId" name="id">
                <input type="hidden" name="type" value="status_modal_fields">
                <input type="hidden" name="csrf_token" id="modalFieldCsrfToken" value="">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <select id="modalFieldStatus" name="status_id" class="select select-bordered w-full" required>
                        <option value="">Select Status</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Field Name</span>
                    </label>
                    <select id="modalFieldName" name="field_name" class="select select-bordered w-full" required>
                        <option value="">Select Field</option>
                        <option value="buyer">Buyer</option>
                        <option value="po_head">PO Head</option>
                        <option value="po_team">PO Team</option>
                        <option value="qty">Quantity</option>
                        <option value="file_upload">File Upload</option>
                        <option value="remark">Remark</option>
                    </select>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label cursor-pointer">
                        <span class="label-text">Required</span>
                        <input type="checkbox" id="modalFieldRequired" name="is_required" class="toggle toggle-primary">
                    </label>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Field Order</span>
                    </label>
                    <input type="number" id="modalFieldOrder" name="field_order" class="input input-bordered w-full" value="0" min="0" required>
                </div>
                
                <div class="form-control mb-4" id="dbColumnNameContainer" style="display: none;">
                    <label class="label">
                        <span class="label-text">Database Column Name</span>
                        <span class="label-text-alt text-gray-500">(Only for remark fields)</span>
                    </label>
                    <select id="modalFieldDbColumn" name="db_column_name" class="select select-bordered w-full">
                        <option value="">Select Column (Optional)</option>
                        <option value="b_remark">b_remark (Buyer Remark)</option>
                        <option value="to_bh_rm">to_bh_rm (To Buyer Head Remark)</option>
                        <option value="po_team_rm">po_team_rm (PO Team Remark)</option>
                        <option value="rrm">rrm (Rejection/Response Remark)</option>
                    </select>
                    <label class="label">
                        <span class="label-text-alt text-gray-500">Specify which database column should store this remark value</span>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('modalFieldModal').close()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script src="../common/js/superadmin.js"></script>
<?php include '../common/layout-footer.php'; ?>

