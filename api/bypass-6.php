<?php
session_start();
include '../config/db.php';
include '../config/response.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['B_Head', 'PO_Head', 'PO_Team_Member','admin'])) {
    sendResponse(403, "error", "Unauthorized access");
}

if (is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} else {
    $ids = [intval($_POST['ids'])];
}

$status = intval($_POST['status']);
$remark = isset($_POST['remarkInput']) ? $_POST['remarkInput'] : null;

// Get first PO Head ID as default fallback
$defaultPoHeadId = null;
$poHeadQuery = "SELECT u.id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Head' AND u.is_active = 1 ORDER BY u.id ASC LIMIT 1";
$poHeadResult = $conn->query($poHeadQuery);
if ($poHeadResult && $poHeadResult->num_rows > 0) {
    $poHeadRow = $poHeadResult->fetch_assoc();
    $defaultPoHeadId = intval($poHeadRow['id']);
    $poHeadResult->free();
}

// Check for poHeadInput first (for status 6), then fallback to poTeamInput, then default PO Head ID
$po_team = isset($_POST['poHeadInput']) && !empty($_POST['poHeadInput']) 
    ? intval($_POST['poHeadInput']) 
    : (isset($_POST['poTeamInput']) && !empty($_POST['poTeamInput'])
        ? intval($_POST['poTeamInput']) 
        : ($defaultPoHeadId ? $defaultPoHeadId : null));

// If still no PO team assigned, return error
if ($po_team === null) {
    sendResponse(400, "error", "No PO Head found in system. Please contact administrator.");
}

$statusDate = (new DateTime())->format('Y-m-d H:i:s');
$buyer_id = isset($_POST['buyerInput']) ? intval($_POST['buyerInput']) : null;


// Prepare statements
$selectQuery = "SELECT b_head,created_by FROM purchase_requests WHERE id = ?";
$selectStmt = $conn->prepare($selectQuery);

$checkBuyerQuery = "SELECT r.role_code FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
$checkBuyerStmt = $conn->prepare($checkBuyerQuery);

// Prepare base SQL
$sql = "UPDATE purchase_requests SET 
            po_team = ?, 
            status_1 = ?, 
            status_2 = ?, 
            status_3 = ?, 
            status_4 = ?, 
            status_5 = ?, 
            po_status = ?, 
            po_team_rm = ?,
            buyer = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    sendResponse(500, "error", $conn->error);
}

// Run update for each ID
foreach ($ids as $id) {
    // Get created_by from purchase_requests
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $selectResult = $selectStmt->get_result();
    $row = $selectResult->fetch_assoc();
    $selectResult->free();

    if (!$row) {
        sendResponse(404, "error", "ID $id not found.");
    }

    $created_by = $row['created_by'];
    $head = $row['b_head'];

    // Check if created_by user exists and get their role
    $checkBuyerStmt->bind_param("i", $created_by);
    $checkBuyerStmt->execute();
    $buyerResult = $checkBuyerStmt->get_result();
    $buyerRow = $buyerResult->fetch_assoc();
    $buyerResult->free();


    $stmt->bind_param(
        "isssssisii",
        $po_team,
        $statusDate,
        $statusDate,
        $statusDate,
        $statusDate,
        $statusDate,
        $status,
        $remark,
        $buyer_id,
        $id
    );

    if (!$stmt->execute()) {
        sendResponse(500, "error", $stmt->error);
    }
}

$selectStmt->close();
$checkBuyerStmt->close();
$stmt->close();
$conn->close();

sendResponse(200, "success", "Status updated successfully", ["buyer_id" => $buyer_id, "created_by" => $created_by]);
?>