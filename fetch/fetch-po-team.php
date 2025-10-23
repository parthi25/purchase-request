<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // Unified API response helper

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    $query = "SELECT id, username, username AS fullname 
              FROM users 
              WHERE role='PO_Team_Member' AND is_active=1";
    $result = $conn->query($query);

    if (!$result) {
        sendResponse(500, "error", "Database query failed");
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    if (!empty($users)) {
        sendResponse(200, "success", "PO team members retrieved successfully", $users);
    } else {
        sendResponse(200, "success", "No PO team members found", []);
    }

} catch (Exception $e) {
    error_log("Error in fetch_po_team.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
