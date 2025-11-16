<?php
session_start();
require '../config/db.php';
require '../config/response.php'; // contains sendResponse()

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['B_Head', 'PO_Team', 'PO_Team_Member', 'buyer'])) {
    sendResponse(403, "error", "Unauthorized access");
}

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log POST data
error_log("POST DATA: " . json_encode($_POST), 3, "../debug_log.txt");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Invalid request method");
}

if (!isset($_POST['ids'], $_POST['status'], $_POST['status_date'])) {
    sendResponse(400, "error", "Missing required fields: ids, status, or status_date");
}

// Prepare IDs for SQL
$ids = is_array($_POST['ids']) ? implode(',', array_map('intval', $_POST['ids'])) : intval($_POST['ids']);
$status = intval($_POST['status']);
$statusDate = mysqli_real_escape_string($conn, $_POST['status_date']);
$buyer = isset($_POST['selectedBuyer']) ? intval($_POST['selectedBuyer']) : null;
$po_team = isset($_POST['selectedPoTeam']) ? intval($_POST['selectedPoTeam']) : null;
$pocmd = $_POST['pocmd'] ?? null;
$bycmd = $_POST['bycmd'] ?? null;
$tobh = $_POST['tobh'] ?? null;
$ponum = isset($_POST['PoNum']) ? intval($_POST['PoNum']) : null;
$rrm = $_POST['RRM'] ?? null;

$statusMapping = [
    2 => 'status_1',
    3 => 'status_2',
    4 => 'status_3',
    5 => 'status_4',
    6 => 'status_5',
    7 => 'status_6',
    8 => 'status_7'
];

// Validate specific status fields
if (($status == 2 && empty($buyer)) || ($status == 7 && empty($po_team))) {
    sendResponse(400, "error", "Missing required fields for this status");
}

// Build dynamic update
$updateFields = ["updated_at = CURRENT_TIMESTAMP", "po_status = '$status'"];

if (!empty($pocmd))
    $updateFields[] = "po_team_rm = '" . mysqli_real_escape_string($conn, $pocmd) . "'";
if (!empty($bycmd))
    $updateFields[] = "b_remark = '" . mysqli_real_escape_string($conn, $bycmd) . "'";
if (!empty($tobh))
    $updateFields[] = "to_bh_rm = '" . mysqli_real_escape_string($conn, $tobh) . "'";
if (!empty($rrm))
    $updateFields[] = "rrm = '" . mysqli_real_escape_string($conn, $rrm) . "'";
if (isset($statusMapping[$status]))
    $updateFields[] = "{$statusMapping[$status]} = '$statusDate'";
if ($status == 2 && $buyer !== null)
    $updateFields[] = "buyer = '$buyer'";
if ($status == 6 && $po_team !== null)
    $updateFields[] = "po_team = '$po_team'";

// Insert PO number if provided
if ($ponum !== null) {
    $stmt = $conn->prepare("INSERT INTO po_documents (po_num, ord_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $ponum, $ids);
    $stmt->execute();
}

// Execute update
if (!empty($updateFields)) {
    $query = "UPDATE purchase_requests SET " . implode(", ", $updateFields) . " WHERE id IN ($ids)";
    error_log("QUERY: " . $query, 3, "../debug_log.txt");

    if (mysqli_query($conn, $query)) {
        sendResponse(200, "success", "Status updated successfully");
    } else {
        error_log("ERROR: " . mysqli_error($conn), 3, "../debug_log.txt");
        sendResponse(500, "error", "Database update failed: " . mysqli_error($conn));
    }
} else {
    sendResponse(400, "error", "No fields to update");
}

$conn->close();
?>
