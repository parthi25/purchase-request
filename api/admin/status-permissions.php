<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';
include '../../config/security.php';

// Check if user is super_admin/master only
$allowedRoles = ['super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all status permissions
            $type = $_GET['type'] ?? 'permissions'; // 'permissions', 'flow', 'role_pr_permissions', or 'status_modal_fields'
            
            if ($type === 'flow') {
                // Get status flow
                $query = "SELECT sf.*, 
                         s1.status as from_status_name, 
                         s2.status as to_status_name
                         FROM status_transitions sf
                         LEFT JOIN pr_statuses s1 ON sf.from_status_id = s1.id
                         LEFT JOIN pr_statuses s2 ON sf.to_status_id = s2.id
                         ORDER BY sf.from_status_id, sf.priority DESC, sf.id";
            } elseif ($type === 'role_pr_permissions') {
                // Get PR permissions
                $query = "SELECT * FROM role_pr_permissions ORDER BY role";
            } elseif ($type === 'status_modal_fields') {
                // Get status modal fields
                $query = "SELECT smf.*, s.status as status_name
                         FROM status_modal_fields smf
                         LEFT JOIN pr_statuses s ON smf.status_id = s.id
                         ORDER BY smf.status_id, smf.field_order";
            } else {
                // Get status permissions
                $query = "SELECT sp.*, s.status as status_name
                         FROM role_status_permissions sp
                         LEFT JOIN pr_statuses s ON sp.status_id = s.id
                         ORDER BY sp.role, sp.status_id";
            }
            
            $result = $conn->query($query);
            if (!$result) {
                sendResponse(500, "error", "Database query failed: " . $conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            sendResponse(200, "success", "Data retrieved successfully", $data);
            break;
            
        case 'POST':
            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
                sendResponse(403, "error", "Invalid CSRF token");
            }
            // Create new permission or flow
            $type = $_POST['type'] ?? 'permission';
            
            if ($type === 'status_modal_fields') {
                if (!isset($_POST['status_id']) || !isset($_POST['field_name'])) {
                    sendResponse(400, "error", "Missing required fields: status_id and field_name are required");
                }
                $status_id = intval($_POST['status_id']);
                $field_name = Security::sanitizeInput($_POST['field_name']);
                $is_required = isset($_POST['is_required']) ? 1 : 0;
                $field_order = intval($_POST['field_order'] ?? 0);
                $db_column_name = !empty($_POST['db_column_name']) ? Security::sanitizeInput($_POST['db_column_name']) : null;
                
                $stmt = $conn->prepare("INSERT INTO status_modal_fields (status_id, field_name, is_required, field_order, db_column_name) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isiss", $status_id, $field_name, $is_required, $field_order, $db_column_name);
            } elseif ($type === 'role_pr_permissions') {
                if (!isset($_POST['role'])) {
                    sendResponse(400, "error", "Missing required field: role is required");
                }
                $role = Security::sanitizeInput($_POST['role']);
                $can_create = isset($_POST['can_create']) ? 1 : 0;
                $can_edit = isset($_POST['can_edit']) ? 1 : 0;
                $can_edit_status = !empty($_POST['can_edit_status']) ? intval($_POST['can_edit_status']) : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if ($can_edit_status === null) {
                    $stmt = $conn->prepare("INSERT INTO role_pr_permissions (role, can_create, can_edit, can_edit_status, is_active) 
                                           VALUES (?, ?, ?, NULL, ?)");
                    $stmt->bind_param("siii", $role, $can_create, $can_edit, $is_active);
                } else {
                    $stmt = $conn->prepare("INSERT INTO role_pr_permissions (role, can_create, can_edit, can_edit_status, is_active) 
                                           VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("siiii", $role, $can_create, $can_edit, $can_edit_status, $is_active);
                }
            } elseif ($type === 'flow') {
                if (!isset($_POST['from_status_id']) || !isset($_POST['to_status_id']) || !isset($_POST['role'])) {
                    sendResponse(400, "error", "Missing required fields: from_status_id, to_status_id, and role are required");
                }
                $from_status_id = intval($_POST['from_status_id']);
                $to_status_id = intval($_POST['to_status_id']);
                $role = Security::sanitizeInput($_POST['role']);
                $requires_proforma = isset($_POST['requires_proforma']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $priority = intval($_POST['priority'] ?? 0);
                
                $stmt = $conn->prepare("INSERT INTO status_transitions (from_status_id, to_status_id, role, requires_proforma, is_active, priority) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisiii", $from_status_id, $to_status_id, $role, $requires_proforma, $is_active, $priority);
            } else {
                // Default permission type - validate required fields
                if (!isset($_POST['role']) || !isset($_POST['status_id'])) {
                    sendResponse(400, "error", "Missing required fields: role and status_id are required");
                }
                
                $role = Security::sanitizeInput($_POST['role']);
                $status_id = intval($_POST['status_id']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO role_status_permissions (role, status_id, is_active) 
                                       VALUES (?, ?, ?)");
                $stmt->bind_param("sii", $role, $status_id, $is_active);
            }
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Record created successfully", ['id' => $conn->insert_id]);
            } else {
                sendResponse(500, "error", "Failed to create record: " . $stmt->error);
            }
            $stmt->close();
            break;
            
        case 'PUT':
            // Update permission or flow
            $putData = [];
            parse_str(file_get_contents("php://input"), $putData);
            // Also check POST data in case it's sent as POST with _method=PUT
            if (empty($putData) && !empty($_POST)) {
                $putData = $_POST;
            }
            // Validate CSRF token
            $csrfToken = $putData['csrf_token'] ?? $_POST['csrf_token'] ?? '';
            if (empty($csrfToken) || !Security::validateCSRFToken($csrfToken)) {
                sendResponse(403, "error", "Invalid CSRF token");
            }
            $type = $putData['type'] ?? 'permission';
            $id = intval($putData['id']);
            
            if ($type === 'status_modal_fields') {
                if (!isset($putData['status_id']) || !isset($putData['field_name'])) {
                    sendResponse(400, "error", "Missing required fields: status_id and field_name are required");
                }
                $status_id = intval($putData['status_id']);
                $field_name = Security::sanitizeInput($putData['field_name']);
                $is_required = isset($putData['is_required']) ? 1 : 0;
                $field_order = intval($putData['field_order'] ?? 0);
                $db_column_name = !empty($putData['db_column_name']) ? Security::sanitizeInput($putData['db_column_name']) : null;
                
                $stmt = $conn->prepare("UPDATE status_modal_fields 
                                       SET status_id = ?, field_name = ?, is_required = ?, field_order = ?, db_column_name = ?
                                       WHERE id = ?");
                $stmt->bind_param("isiisi", $status_id, $field_name, $is_required, $field_order, $db_column_name, $id);
            } elseif ($type === 'role_pr_permissions') {
                if (!isset($putData['role'])) {
                    sendResponse(400, "error", "Missing required field: role is required");
                }
                $role = Security::sanitizeInput($putData['role']);
                $can_create = isset($putData['can_create']) ? 1 : 0;
                $can_edit = isset($putData['can_edit']) ? 1 : 0;
                $can_edit_status = !empty($putData['can_edit_status']) ? intval($putData['can_edit_status']) : null;
                $is_active = isset($putData['is_active']) ? 1 : 0;
                
                if ($can_edit_status === null) {
                    $stmt = $conn->prepare("UPDATE role_pr_permissions 
                                           SET role = ?, can_create = ?, can_edit = ?, can_edit_status = NULL, is_active = ?
                                           WHERE id = ?");
                    $stmt->bind_param("siiii", $role, $can_create, $can_edit, $is_active, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE role_pr_permissions 
                                           SET role = ?, can_create = ?, can_edit = ?, can_edit_status = ?, is_active = ?
                                           WHERE id = ?");
                    $stmt->bind_param("siiiii", $role, $can_create, $can_edit, $can_edit_status, $is_active, $id);
                }
            } elseif ($type === 'flow') {
                if (!isset($putData['from_status_id']) || !isset($putData['to_status_id']) || !isset($putData['role'])) {
                    sendResponse(400, "error", "Missing required fields: from_status_id, to_status_id, and role are required");
                }
                $from_status_id = intval($putData['from_status_id']);
                $to_status_id = intval($putData['to_status_id']);
                $role = Security::sanitizeInput($putData['role']);
                $requires_proforma = isset($putData['requires_proforma']) ? 1 : 0;
                $is_active = isset($putData['is_active']) ? 1 : 0;
                $priority = intval($putData['priority'] ?? 0);
                
                $stmt = $conn->prepare("UPDATE status_transitions 
                                       SET from_status_id = ?, to_status_id = ?, role = ?, 
                                           requires_proforma = ?, is_active = ?, priority = ?
                                       WHERE id = ?");
                $stmt->bind_param("iisiiii", $from_status_id, $to_status_id, $role, $requires_proforma, $is_active, $priority, $id);
            } else {
                // Default permission type - validate required fields
                if (!isset($putData['role']) || !isset($putData['status_id'])) {
                    sendResponse(400, "error", "Missing required fields: role and status_id are required");
                }
                
                $role = Security::sanitizeInput($putData['role']);
                $status_id = intval($putData['status_id']);
                $is_active = isset($putData['is_active']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE role_status_permissions 
                                       SET role = ?, status_id = ?, is_active = ?
                                       WHERE id = ?");
                $stmt->bind_param("siii", $role, $status_id, $is_active, $id);
            }
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Record updated successfully");
            } else {
                sendResponse(500, "error", "Failed to update record: " . $stmt->error);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            // Delete permission or flow
            $deleteData = [];
            parse_str(file_get_contents("php://input"), $deleteData);
            // Also check POST data in case it's sent as POST with _method=DELETE
            if (empty($deleteData) && !empty($_POST)) {
                $deleteData = $_POST;
            }
            // Validate CSRF token
            $csrfToken = $deleteData['csrf_token'] ?? $_POST['csrf_token'] ?? '';
            if (empty($csrfToken) || !Security::validateCSRFToken($csrfToken)) {
                sendResponse(403, "error", "Invalid CSRF token");
            }
            $type = $deleteData['type'] ?? 'permission';
            $id = intval($deleteData['id']);
            
            if ($type === 'status_modal_fields') {
                $table = 'status_modal_fields';
            } elseif ($type === 'role_pr_permissions') {
                $table = 'role_pr_permissions';
            } elseif ($type === 'flow') {
                $table = 'status_transitions';
            } else {
                $table = 'role_status_permissions';
            }
            $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Record deleted successfully");
            } else {
                sendResponse(500, "error", "Failed to delete record: " . $stmt->error);
            }
            $stmt->close();
            break;
            
        default:
            sendResponse(405, "error", "Method not allowed");
    }
} catch (Exception $e) {
    error_log("Error in status-permissions.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}

