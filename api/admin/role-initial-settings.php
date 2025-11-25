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
            $query = "SELECT ris.*, r.role_name 
                      FROM role_initial_settings ris
                      LEFT JOIN roles r ON ris.role = r.role_code
                      ORDER BY r.display_order ASC, ris.role ASC, ris.id ASC";
            $result = $conn->query($query);
            
            if (!$result) {
                sendResponse(500, "error", "Database query failed: " . $conn->error);
            }
            
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[] = $row;
            }
            $result->free();
            
            sendResponse(200, "success", "Initial settings retrieved successfully", $settings);
        } catch (Exception $e) {
            error_log("Error in role-initial-settings.php list: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'get_by_role':
        try {
            $role = trim($_GET['role'] ?? '');
            
            if (empty($role)) {
                sendResponse(400, "error", "Role parameter is required");
            }
            
            $query = "SELECT * FROM role_initial_settings WHERE role = ? AND is_active = 1 LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                sendResponse(404, "error", "No initial settings found for this role");
            }
            
            $setting = $result->fetch_assoc();
            $stmt->close();
            sendResponse(200, "success", "Initial setting retrieved successfully", $setting);
        } catch (Exception $e) {
            error_log("Error in role-initial-settings.php get_by_role: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'create':
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            sendResponse(403, "error", "Invalid CSRF token");
        }
        try {
            $role = trim($_POST['role'] ?? '');
            $initial_page_url = trim($_POST['initial_page_url'] ?? '');
            $initial_status_filter = !empty($_POST['initial_status_filter']) ? intval($_POST['initial_status_filter']) : null;
            
            if (empty($role) || empty($initial_page_url)) {
                sendResponse(400, "error", "Role and initial page URL are required");
            }
            
            // Handle NULL value properly - prepare different statements for NULL vs non-NULL
            if ($initial_status_filter === null) {
                $stmt = $conn->prepare("INSERT INTO role_initial_settings (role, initial_page_url, initial_status_filter, is_active) VALUES (?, ?, NULL, 1)");
                $stmt->bind_param("ss", $role, $initial_page_url);
            } else {
                $stmt = $conn->prepare("INSERT INTO role_initial_settings (role, initial_page_url, initial_status_filter, is_active) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("ssi", $role, $initial_page_url, $initial_status_filter);
            }
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Initial setting created successfully", ["id" => $stmt->insert_id]);
            } else {
                if ($conn->errno === 1062) {
                    sendResponse(400, "error", "Initial setting already exists for this role. Use update instead.");
                } else {
                    sendResponse(500, "error", "Failed to create initial setting: " . $stmt->error);
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in role-initial-settings.php create: " . $e->getMessage());
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
            $initial_page_url = trim($_POST['initial_page_url'] ?? '');
            $initial_status_filter = !empty($_POST['initial_status_filter']) ? intval($_POST['initial_status_filter']) : null;
            // Checkbox: if not set, it's 0; if set, it can be 'on', '1', or 'true'
            $is_active = (isset($_POST['is_active']) && ($_POST['is_active'] === 'on' || $_POST['is_active'] === '1' || $_POST['is_active'] === 'true' || $_POST['is_active'] === true)) ? 1 : 0;
            
            if ($id <= 0 || empty($initial_page_url)) {
                sendResponse(400, "error", "ID and initial page URL are required");
            }
            
            // Check if setting exists
            $checkStmt = $conn->prepare("SELECT id FROM role_initial_settings WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $result->free();
                $checkStmt->close();
                sendResponse(404, "error", "Initial setting not found");
            }
            $result->free();
            $checkStmt->close();
            
            // Handle NULL value properly
            if ($initial_status_filter === null) {
                $stmt = $conn->prepare("UPDATE role_initial_settings SET initial_page_url = ?, initial_status_filter = NULL, is_active = ? WHERE id = ?");
                $stmt->bind_param("sii", $initial_page_url, $is_active, $id);
            } else {
                $stmt = $conn->prepare("UPDATE role_initial_settings SET initial_page_url = ?, initial_status_filter = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("siii", $initial_page_url, $initial_status_filter, $is_active, $id);
            }
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Initial setting updated successfully");
            } else {
                sendResponse(500, "error", "Failed to update initial setting: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in role-initial-settings.php update: " . $e->getMessage());
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
                sendResponse(400, "error", "Invalid setting ID");
            }
            
            // Soft delete by setting is_active to 0
            $stmt = $conn->prepare("UPDATE role_initial_settings SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Initial setting deleted successfully");
            } else {
                sendResponse(500, "error", "Failed to delete initial setting: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error in role-initial-settings.php delete: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    default:
        sendResponse(400, "error", "Invalid action");
        break;
}

$conn->close();
?>

