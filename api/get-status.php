<?php
require '../config/db.php';
include '../config/response.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}
// Get input values
$current_status = $_GET['current_status'] ?? '';
$pr_id = $_GET['pr_id'] ?? '';
$role = $_SESSION["role"] ?? '';

if (empty($current_status) || empty($role)) {
    sendResponse(400, "error", "Current status and role parameters are required");
}

// Role-based allowed status transitions
$statusAccess = [
    "admin" => [1],
    "buyer" => [3, 4, 5],
    "B_Head" => [2, 6, 8],
    "PO_Team" => [9],
    "PO_Team_Member" => [7]
];

if (!isset($statusAccess[$role])) {
    sendResponse(400, "error", "Invalid role provided");
}

try {
    // Determine current_status ID
    if (is_numeric($current_status)) {
        $current_status_id = (int) $current_status;
    } else {
        $stmt = $conn->prepare("SELECT id FROM status WHERE status = ?");
        if (!$stmt)
            sendResponse(500, "error", "Database query preparation failed");

        $stmt->bind_param("s", $current_status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row)
            sendResponse(404, "error", "Invalid current status");
        $current_status_id = (int) $row['id'];
    }

    // Special handling for status 1 (Forwarded to Buyer)
    if ($current_status_id == 1) {
        $statuses = [];

        // Always include status 8 (Rejected)
        $stmt = $conn->prepare("SELECT id, status FROM status WHERE id in (2)");
        if (!$stmt)
            sendResponse(500, "error", "Database query preparation failed");
        $stmt->execute();
        $result = $stmt->get_result();
        $statuses = array_merge($statuses, $result->fetch_all(MYSQLI_ASSOC));
        $stmt->close();

        // Check if proforma is uploaded for this PR
        if (!empty($pr_id)) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proforma WHERE ord_id = ? AND filename IS NOT NULL");
            if (!$stmt)
                sendResponse(500, "error", "Database query preparation failed");
            $stmt->bind_param("i", $pr_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row['count'] > 0) {

                $stmt = $conn->prepare("SELECT id, status FROM status WHERE id in (6)");
                if (!$stmt)
                    sendResponse(500, "error", "Database query preparation failed");
                $stmt->execute();
                $result = $stmt->get_result();
                $statuses = array_merge($statuses, $result->fetch_all(MYSQLI_ASSOC));
                $stmt->close();
            }
        }

        if (empty($statuses)) {
            sendResponse(200, "success", "No next status found", []);
        }

        sendResponse(200, "success", "Next status retrieved successfully", $statuses);
    } else {
        // Original logic for other statuses
        $next_status_id = $role == 'PO_Team' ? $current_status_id + 3 : $current_status_id + 1;

        if (!in_array($next_status_id, $statusAccess[$role])) {
            sendResponse(200, "success", "No next status available for this role", []);
        }

        $stmt = $conn->prepare("SELECT id, status FROM status WHERE id = ?");
        if (!$stmt)
            sendResponse(500, "error", "Database query preparation failed");

        $stmt->bind_param("i", $next_status_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $statuses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($statuses)) {
            sendResponse(200, "success", "No next status found", []);
        }

        sendResponse(200, "success", "Next status retrieved successfully", $statuses);
    }

} catch (Exception $e) {
    error_log("Error in get_status.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
