<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';
include '../../config/security.php';

// Check if user is super_admin/master
$allowedRoles = ['super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            $query = "SELECT * FROM roles ORDER BY display_order ASC, role_name ASC";
            $result = $conn->query($query);
            
            if (!$result) {
                sendResponse(500, "error", "Database query failed: " . $conn->error);
            }
            
            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row;
            }
            
            $result->free();
            sendResponse(200, "success", "Roles retrieved successfully", $roles);
        } catch (Exception $e) {
            error_log("Error in roles.php list: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'create':
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            sendResponse(403, "error", "Invalid CSRF token");
        }
        try {
            $role_code = trim($_POST['role_code'] ?? '');
            $role_name = trim($_POST['role_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $display_order = intval($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
            
            if (empty($role_code) || empty($role_name)) {
                sendResponse(400, "error", "Role code and role name are required");
            }
            
            // Validate role_code format (alphanumeric and underscore only)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $role_code)) {
                sendResponse(400, "error", "Role code can only contain letters, numbers, and underscores");
            }
            
            $stmt = $conn->prepare("INSERT INTO roles (role_code, role_name, description, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $role_code, $role_name, $description, $display_order, $is_active);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Role created successfully", ["id" => $stmt->insert_id]);
            } else {
                if ($conn->errno === 1062) {
                    sendResponse(400, "error", "Role code already exists");
                } else {
                    sendResponse(500, "error", "Failed to create role: " . $stmt->error);
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in roles.php create: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'update':
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            sendResponse(403, "error", "Invalid CSRF token");
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $role_name = trim($_POST['role_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $display_order = intval($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
            
            if ($id <= 0 || empty($role_name)) {
                sendResponse(400, "error", "ID and role name are required");
            }
            
            // Check if role exists
            $checkStmt = $conn->prepare("SELECT role_code FROM roles WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $result->free();
                $checkStmt->close();
                sendResponse(404, "error", "Role not found");
            }
            $result->free();
            $checkStmt->close();
            
            $stmt = $conn->prepare("UPDATE roles SET role_name = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $role_name, $description, $display_order, $is_active, $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Role updated successfully");
            } else {
                sendResponse(500, "error", "Failed to update role: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in roles.php update: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'delete':
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            sendResponse(403, "error", "Invalid CSRF token");
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendResponse(400, "error", "Invalid role ID");
            }
            
            // Check if role exists
            $checkStmt = $conn->prepare("SELECT role_code FROM roles WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $result->free();
                $checkStmt->close();
                sendResponse(404, "error", "Role not found");
            }
            
            $row = $result->fetch_assoc();
            $role_code = $row['role_code'];
            $result->free();
            $checkStmt->close();
            
            // Check if any users have this role
            $userCheck = $conn->prepare("SELECT COUNT(*) as count FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = ?");
            $userCheck->bind_param("s", $role_code);
            $userCheck->execute();
            $userResult = $userCheck->get_result();
            $userCount = $userResult->fetch_assoc()['count'];
            $userResult->free();
            $userCheck->close();
            
            if ($userCount > 0) {
                sendResponse(400, "error", "Cannot delete role. There are {$userCount} user(s) assigned to this role.");
            }
            
            // Delete role
            $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Role deleted successfully");
            } else {
                sendResponse(500, "error", "Failed to delete role: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in roles.php delete: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    default:
        sendResponse(400, "error", "Invalid action");
        break;
}

$conn->close();
?>

