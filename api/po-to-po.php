<?php
session_start();
include '../config/db.php';
require '../config/response.php';

// Get POST data safely
$id = isset($_POST['ids']) ? intval($_POST['ids']) : 0;
$status = intval($_POST['status']) ?? 9;
$pr_assignments = $_POST['poTeamInput'] ?? null;
$statusDate = date('Y-m-d H:i:s');
$created_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$remark = isset($_POST['remarkInput']) ? $_POST['remarkInput'] : null;

// Validation
$errors = [];
if ($id <= 0) $errors[] = 'Invalid ID';
if ($pr_assignments <= 0) $errors[] = 'Invalid PO team member';
if ($created_by <= 0) $errors[] = 'User not authenticated';

if (!empty($errors)) {
    sendResponse(400, "error", 'Validation failed: ' . implode(', ', $errors), ['errors' => $errors]);
}

try {
    $conn->autocommit(false);

    // Update purchase_requests
    $updateQuery = "UPDATE purchase_requests 
                    SET po_status = ?, 
                        rrm = ?, 
                        status_6 = ?
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt)
        throw new Exception('Failed to prepare purchase_requests update query');

    $updateStmt->bind_param("sssi", $status, $remark, $statusDate, $id);
    if (!$updateStmt->execute())
        throw new Exception('Failed to update purchase_requests: ' . $updateStmt->error);

    // Fetch buyer from purchase_requests
    $buyerStmt = $conn->prepare("SELECT buyer FROM purchase_requests WHERE id = ?");
    if (!$buyerStmt)
        throw new Exception('Failed to prepare buyer fetch query');
    $buyerStmt->bind_param("i", $id);
    $buyerStmt->execute();
    $buyerStmt->bind_result($buyer);
    $buyerStmt->fetch();
    $buyerStmt->close();

    // Check if record exists in pr_assignments
    $checkStmt = $conn->prepare("SELECT id FROM pr_assignments WHERE ord_id = ?");
    if (!$checkStmt)
        throw new Exception('Failed to prepare check query');
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update existing record
        $updateMemberStmt = $conn->prepare("UPDATE pr_assignments 
                                            SET created_by = ?, po_team_member = ?, buyer = ?, updated_at = ? 
                                            WHERE ord_id = ?");
        if (!$updateMemberStmt)
            throw new Exception('Failed to prepare pr_assignments update query');
        $updateMemberStmt->bind_param("iissi", $created_by, $pr_assignments, $buyer, $updated_at, $id);
        if (!$updateMemberStmt->execute())
            throw new Exception('Failed to update pr_assignments: ' . $updateMemberStmt->error);
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("INSERT INTO pr_assignments 
                                      (created_by, po_team_member, created_at, updated_at, ord_id) 
                                      VALUES (?, ?, ?, ?, ?)");
        if (!$insertStmt)
            throw new Exception('Failed to prepare pr_assignments insert query');
        $insertStmt->bind_param("iissi", $created_by, $pr_assignments, $created_at, $updated_at, $id);
        if (!$insertStmt->execute())
            throw new Exception('Failed to insert pr_assignments: ' . $insertStmt->error);
    }

    $conn->commit();

    sendResponse(200, "success", 'PO assignment completed successfully', [
        'id' => $id,
        'status' => $status,
        'pr_assignments' => $pr_assignments,
        'buyer' => $buyer
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in po_to_po.php: " . $e->getMessage());
    sendResponse(500, "error", 'Failed to process PO assignment: ' . $e->getMessage());
} finally {
    $conn->autocommit(true);
    $conn->close();
}
?>
