<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // unified sendResponse

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not authenticated");
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

if ($user_role !== 'PO_Team') {
    sendResponse(403, "error", "Access denied");
}

try {
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'poteam' ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    $poMembers = [];
    while ($row = $result->fetch_assoc()) {
        $poMembers[] = $row;
    }

    sendResponse(200, "success", "PO Members retrieved successfully", $poMembers);

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in fetch_po_members.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
