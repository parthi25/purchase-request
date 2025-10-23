<?php
session_start();
include '../config/db.php';
require '../config/response.php';

// Get POST data safely
$id = isset($_POST['ids']) ? intval($_POST['ids']) : 0;
$status = intval($_POST['status']) ?? 9;
$po_team_member = $_POST['poTeamInput'] ?? null;
$statusDate = date('Y-m-d H:i:s');
$created_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$remark = isset($_POST['remarkInput']) ? $_POST['remarkInput'] : null;

// Validation
$errors = [];
if ($id <= 0) $errors[] = 'Invalid ID';
if ($po_team_member <= 0) $errors[] = 'Invalid PO team member';
if ($created_by <= 0) $errors[] = 'User not authenticated';

if (!empty($errors)) {
    sendResponse(400, "error", 'Validation failed: ' . implode(', ', $errors), ['errors' => $errors]);
}

try {
    $conn->autocommit(false);

    // Update po_tracking
    $updateQuery = "UPDATE po_tracking 
                    SET po_status = ?, 
                        po_team_rm = ?, 
                        status_6 = ?
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt)
        throw new Exception('Failed to prepare po_tracking update query');

    $updateStmt->bind_param("sssi", $status, $remark, $statusDate, $id);
    if (!$updateStmt->execute())
        throw new Exception('Failed to update po_tracking: ' . $updateStmt->error);

    // Check if record exists in po_team_member
    $checkStmt = $conn->prepare("SELECT id FROM po_team_member WHERE ord_id = ?");
    if (!$checkStmt)
        throw new Exception('Failed to prepare check query');
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update existing record
        $updateMemberStmt = $conn->prepare("UPDATE po_team_member 
                                            SET created_by = ?, po_team_member = ?, updated_at = ? 
                                            WHERE ord_id = ?");
        if (!$updateMemberStmt)
            throw new Exception('Failed to prepare po_team_member update query');
        $updateMemberStmt->bind_param("iisi", $created_by, $po_team_member, $updated_at, $id);
        if (!$updateMemberStmt->execute())
            throw new Exception('Failed to update po_team_member: ' . $updateMemberStmt->error);
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("INSERT INTO po_team_member 
                                      (created_by, po_team_member, created_at, updated_at, ord_id) 
                                      VALUES (?, ?, ?, ?, ?)");
        if (!$insertStmt)
            throw new Exception('Failed to prepare po_team_member insert query');
        $insertStmt->bind_param("iissi", $created_by, $po_team_member, $created_at, $updated_at, $id);
        if (!$insertStmt->execute())
            throw new Exception('Failed to insert po_team_member: ' . $insertStmt->error);
    }

    $conn->commit();

    sendResponse(200, "success", 'PO assignment completed successfully', [
        'id' => $id,
        'status' => $status,
        'po_team_member' => $po_team_member
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
