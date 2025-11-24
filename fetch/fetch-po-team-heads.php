<?php
session_start();
include '../config/db.php';
include '../config/response.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    $query = "SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team' AND u.is_active = 1 ORDER BY u.username";
    $result = $conn->query($query);

    if (!$result) {
        sendResponse(500, "error", "Database query failed");
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    sendResponse(200, "success", "PO Team heads retrieved successfully", $users);

} catch (Exception $e) {
    error_log("Error in fetch-po-team-heads.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>

