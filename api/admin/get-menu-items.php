<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "Unauthorized access");
}

$role = $_SESSION['role'] ?? '';

if (empty($role)) {
    sendResponse(400, "error", "User role not found");
}

try {
    // Get menu items for the user's role, ordered by menu_order and grouped by menu_group
    $query = "SELECT * FROM role_menu_settings 
              WHERE role = ? AND is_active = 1 AND is_visible = 1 
              ORDER BY menu_group ASC, menu_order ASC, menu_item_label ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $menus = [];
    $currentGroup = null;
    
    while ($row = $result->fetch_assoc()) {
        $menuGroup = $row['menu_group'] ?? 'main';
        
        // Group menus by menu_group
        if (!isset($menus[$menuGroup])) {
            $menus[$menuGroup] = [];
        }
        
        $menus[$menuGroup][] = $row;
    }
    
    sendResponse(200, "success", "Menu items retrieved successfully", $menus);
} catch (Exception $e) {
    error_log("Error in get-menu-items.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>

