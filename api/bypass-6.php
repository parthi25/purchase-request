<?php
session_start();
include '../config/db.php';
include '../config/response.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['B_Head', 'PO_Team', 'PO_Team_Member'])) {
    sendResponse(403, "error", "Unauthorized access");
}

if (is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} else {
    $ids = [intval($_POST['ids'])];
}

$status = intval($_POST['status']);
$remark = isset($_POST['remarkInput']) ? $_POST['remarkInput'] : null;
$po_team = $_POST['poTeamInput'] ?? 37;
$statusDate = (new DateTime())->format('Y-m-d H:i:s');
$buyer_id = isset($_POST['buyerInput']) ? intval($_POST['buyerInput']) : null;


// Prepare statements
$selectQuery = "SELECT b_head,created_by FROM po_tracking WHERE id = ?";
$selectStmt = $conn->prepare($selectQuery);

$checkBuyerQuery = "SELECT role FROM users WHERE id = ?";
$checkBuyerStmt = $conn->prepare($checkBuyerQuery);

// Prepare base SQL
$sql = "UPDATE po_tracking SET 
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
    // Get created_by from po_tracking
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $selectResult = $selectStmt->get_result();
    $row = $selectResult->fetch_assoc();

    if (!$row) {
        sendResponse(404, "error", "ID $id not found.");
    }

    $created_by = $row['created_by'];
    $head = $row['b_head'];

    // Check if created_by user exists and is a buyer
    $checkBuyerStmt->bind_param("i", $created_by);
    $checkBuyerStmt->execute();
    $buyerResult = $checkBuyerStmt->get_result();


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