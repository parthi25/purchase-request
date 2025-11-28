<?php
session_start();
require '../config/db.php';
include '../config/response.php';

// Public endpoint - no authentication required for status badges
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
    
    $result->free();
    sendResponse(200, "success", "Statuses retrieved successfully", $statuses);
} catch (Exception $e) {
    error_log("Error in get-status-badges.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>

