<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // Unified API response helper

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    $query = "SELECT u.id, u.username, u.fullname 
              FROM users u
              INNER JOIN roles r ON u.role_id = r.id
              WHERE r.role_code = 'PO_Team_Member' AND u.is_active = 1
              ORDER BY u.username ASC";
    $result = $conn->query($query);

    if (!$result) {
        sendResponse(500, "error", "Database query failed: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Use fullname if available, otherwise fallback to username
        $row['fullname'] = !empty($row['fullname']) ? $row['fullname'] : $row['username'];
        $users[] = $row;
    }

    $result->free();

    if (!empty($users)) {
        sendResponse(200, "success", "PO team members retrieved successfully", $users);
    } else {
        sendResponse(200, "success", "No PO team members found", []);
    }

} catch (Exception $e) {
    error_log("Error in fetch_po_team.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
