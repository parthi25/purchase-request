<?php
require '../config/db.php';
require '../config/response.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

// Check if user has permission to delete PR
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['super_admin', 'master', 'admin'];
if (!in_array($userRole, $allowedRoles)) {
    sendResponse(403, "error", "You do not have permission to delete PR");
}

if (!isset($_POST['id'])) {
    sendResponse(400, "error", "Missing PR ID.");
}

$id = (int) $_POST['id'];
if ($id <= 0) {
    sendResponse(400, "error", "Invalid PR ID.");
}

try {
    // Check if PR exists
    $checkStmt = $conn->prepare("SELECT id FROM purchase_requests WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        sendResponse(404, "error", "PR not found");
    }
    $checkStmt->close();
    
    // Start transaction
    $conn->autocommit(false);
    
    // Delete related records first (due to foreign key constraints)
    // Delete pr_assignments
    $deleteAssign = $conn->prepare("DELETE FROM pr_assignments WHERE ord_id = ?");
    $deleteAssign->bind_param("i", $id);
    $deleteAssign->execute();
    $deleteAssign->close();
    
    // Delete pr_attachments (product images)
    $deleteAttach = $conn->prepare("DELETE FROM pr_attachments WHERE ord_id = ?");
    $deleteAttach->bind_param("i", $id);
    $deleteAttach->execute();
    $deleteAttach->close();
    
    // Delete po_documents
    $deletePO = $conn->prepare("DELETE FROM po_documents WHERE ord_id = ?");
    $deletePO->bind_param("i", $id);
    $deletePO->execute();
    $deletePO->close();
    
    // Delete proforma (if exists)
    $deleteProforma = $conn->prepare("DELETE FROM proforma WHERE ord_id = ?");
    if ($deleteProforma) {
        $deleteProforma->bind_param("i", $id);
        $deleteProforma->execute();
        $deleteProforma->close();
    }
    
    // Finally delete the PR
    $deleteStmt = $conn->prepare("DELETE FROM purchase_requests WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete PR: " . $deleteStmt->error);
    }
    
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("PR not found or already deleted");
    }
    
    $deleteStmt->close();
    
    // Commit transaction
    $conn->commit();
    sendResponse(200, "success", "PR deleted successfully", ['pr_id' => $id]);
    
} catch (Exception $e) {
    $conn->rollback();
    sendResponse(500, "error", "Database error: " . $e->getMessage());
} finally {
    $conn->autocommit(true);
    $conn->close();
}
?>

