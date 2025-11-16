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
    // Get all statuses
    $query = "SELECT id, status FROM pr_statuses ORDER BY id";
    $result = $conn->query($query);
    
    if (!$result) {
        sendResponse(500, "error", "Database query failed: " . $conn->error);
    }
    
    $statuses = [];
    while ($row = $result->fetch_assoc()) {
        $statuses[] = $row;
    }
    
    sendResponse(200, "success", "Statuses retrieved successfully", $statuses);
} catch (Exception $e) {
    error_log("Error in get-statuses.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}

