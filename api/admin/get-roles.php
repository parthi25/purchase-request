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
    // Get distinct roles from users table
    $query = "SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role != '' ORDER BY role";
    $result = $conn->query($query);
    
    if (!$result) {
        sendResponse(500, "error", "Database query failed: " . $conn->error);
    }
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['role'];
    }
    
    sendResponse(200, "success", "Roles retrieved successfully", $roles);
} catch (Exception $e) {
    error_log("Error in get-roles.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}

