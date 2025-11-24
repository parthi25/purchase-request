<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

try {
    // Get roles from roles table
    $query = "SELECT role_code, role_name, description FROM roles WHERE is_active = 1 ORDER BY display_order ASC, role_name ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        sendResponse(500, "error", "Database query failed: " . $conn->error);
    }
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = [
            'code' => $row['role_code'],
            'name' => $row['role_name'],
            'description' => $row['description']
        ];
    }
    
    sendResponse(200, "success", "Roles retrieved successfully", $roles);
} catch (Exception $e) {
    error_log("Error in get-roles.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}

