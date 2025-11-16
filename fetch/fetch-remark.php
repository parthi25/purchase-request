<?php
/**
 * Fetch single PR remarks by ID
 * Returns all remark fields for a given record
 */

session_start();
require_once '../config/db.php';
require_once '../config/response.php';

// --- Session validation ---
$userid = $_SESSION['user_id'] ?? 0;
if ($userid <= 0) {
    sendResponse(401, "error", "Invalid session");
}

// --- Input validation ---
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    sendResponse(400, "error", "Invalid or missing ID");
}

// --- Query ---
$sql = "
    SELECT 
        remark,
        b_remark,
        to_bh_rm,
        po_team_rm,
        rrm
    FROM purchase_requests
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendResponse(500, "error", "Database query preparation failed");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    sendResponse(200, "success", "Remark fetched successfully", $row);
} else {
    sendResponse(404, "error", "Record not found");
}

$stmt->close();
$conn->close();
?>
