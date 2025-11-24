<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is super_admin/master
$allowedRoles = ['super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            $role = $_GET['role'] ?? '';
            
            if (empty($role)) {
                sendResponse(400, "error", "Role parameter is required");
            }
            
            $query = "SELECT * FROM role_menu_settings WHERE role = ? AND is_active = 1 ORDER BY menu_order ASC, menu_item_label ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $menus = [];
            while ($row = $result->fetch_assoc()) {
                $menus[] = $row;
            }
            
            sendResponse(200, "success", "Menu settings retrieved successfully", $menus);
        } catch (Exception $e) {
            error_log("Error in role-menu-settings.php list: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'list_all':
        try {
            $query = "SELECT rms.*, r.role_name 
                      FROM role_menu_settings rms 
                      LEFT JOIN roles r ON rms.role = r.role_code 
                      ORDER BY r.display_order ASC, rms.menu_order ASC, rms.menu_item_label ASC";
            $result = $conn->query($query);
            
            if (!$result) {
                sendResponse(500, "error", "Database query failed: " . $conn->error);
            }
            
            $menus = [];
            while ($row = $result->fetch_assoc()) {
                $menus[] = $row;
            }
            
            sendResponse(200, "success", "All menu settings retrieved successfully", $menus);
        } catch (Exception $e) {
            error_log("Error in role-menu-settings.php list_all: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'create':
        try {
            $role = trim($_POST['role'] ?? '');
            $menu_item_label = trim($_POST['menu_item_label'] ?? '');
            $menu_item_url = trim($_POST['menu_item_url'] ?? '');
            $menu_item_icon = trim($_POST['menu_item_icon'] ?? '');
            $menu_order = intval($_POST['menu_order'] ?? 0);
            $is_visible = isset($_POST['is_visible']) && $_POST['is_visible'] ? 1 : 0;
            $menu_group = trim($_POST['menu_group'] ?? 'main');
            
            if (empty($role) || empty($menu_item_label) || empty($menu_item_url)) {
                sendResponse(400, "error", "Role, menu label, and menu URL are required");
            }
            
            $stmt = $conn->prepare("INSERT INTO role_menu_settings (role, menu_item_label, menu_item_url, menu_item_icon, menu_order, is_visible, menu_group, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssiiss", $role, $menu_item_label, $menu_item_url, $menu_item_icon, $menu_order, $is_visible, $menu_group);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Menu item created successfully", ["id" => $stmt->insert_id]);
            } else {
                if ($conn->errno === 1062) {
                    sendResponse(400, "error", "Menu item with this URL already exists for this role");
                } else {
                    sendResponse(500, "error", "Failed to create menu item: " . $stmt->error);
                }
            }
        } catch (Exception $e) {
            error_log("Error in role-menu-settings.php create: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'update':
        try {
            $id = intval($_POST['id'] ?? 0);
            $menu_item_label = trim($_POST['menu_item_label'] ?? '');
            $menu_item_url = trim($_POST['menu_item_url'] ?? '');
            $menu_item_icon = trim($_POST['menu_item_icon'] ?? '');
            $menu_order = intval($_POST['menu_order'] ?? 0);
            $is_visible = isset($_POST['is_visible']) && $_POST['is_visible'] ? 1 : 0;
            $menu_group = trim($_POST['menu_group'] ?? 'main');
            $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
            
            if ($id <= 0 || empty($menu_item_label) || empty($menu_item_url)) {
                sendResponse(400, "error", "ID, menu label, and menu URL are required");
            }
            
            // Check if menu item exists
            $checkStmt = $conn->prepare("SELECT role FROM role_menu_settings WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                sendResponse(404, "error", "Menu item not found");
            }
            
            $row = $result->fetch_assoc();
            $role = $row['role'];
            
            // Check for duplicate URL for same role (excluding current record)
            $dupCheck = $conn->prepare("SELECT id FROM role_menu_settings WHERE role = ? AND menu_item_url = ? AND id != ?");
            $dupCheck->bind_param("ssi", $role, $menu_item_url, $id);
            $dupCheck->execute();
            $dupResult = $dupCheck->get_result();
            
            if ($dupResult->num_rows > 0) {
                sendResponse(400, "error", "Menu item with this URL already exists for this role");
            }
            
            $stmt = $conn->prepare("UPDATE role_menu_settings SET menu_item_label = ?, menu_item_url = ?, menu_item_icon = ?, menu_order = ?, is_visible = ?, menu_group = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssiissi", $menu_item_label, $menu_item_url, $menu_item_icon, $menu_order, $is_visible, $menu_group, $is_active, $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Menu item updated successfully");
            } else {
                sendResponse(500, "error", "Failed to update menu item: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Error in role-menu-settings.php update: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    case 'delete':
        try {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendResponse(400, "error", "Invalid menu item ID");
            }
            
            // Check if menu item exists
            $checkStmt = $conn->prepare("SELECT id FROM role_menu_settings WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                sendResponse(404, "error", "Menu item not found");
            }
            
            // Soft delete by setting is_active to 0, or hard delete
            $stmt = $conn->prepare("DELETE FROM role_menu_settings WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendResponse(200, "success", "Menu item deleted successfully");
            } else {
                sendResponse(500, "error", "Failed to delete menu item: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Error in role-menu-settings.php delete: " . $e->getMessage());
            sendResponse(500, "error", "Internal server error");
        }
        break;

    default:
        sendResponse(400, "error", "Invalid action");
        break;
}

$conn->close();
?>

