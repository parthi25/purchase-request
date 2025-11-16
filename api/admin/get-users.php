<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

$role = $_GET['role'] ?? '';

if (empty($role)) {
    sendResponse(400, "error", "Role parameter is required");
}

try {
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE role = ? ORDER BY fullname ASC");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendResponse(200, "success", "Users retrieved successfully", $users);
} catch (Exception $e) {
    sendResponse(500, "error", $e->getMessage());
}
?>

