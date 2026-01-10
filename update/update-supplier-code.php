<?php
session_start();
require '../config/db.php';
require '../config/response.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'User not authenticated');
}

// Check if user has permission
// $allowed_roles = ['admin', 'PO_Team', 'PO_Team_Member', 'super_admin', 'master'];
// if (!in_array($_SESSION['role'], $allowed_roles)) {
//     sendResponse(403, 'error', 'Permission denied');
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'error', 'Invalid request method');
}

$supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
$supplier_code = isset($_POST['supplier_code']) ? trim($_POST['supplier_code']) : '';

if ($supplier_id <= 0) {
    sendResponse(400, 'error', 'Invalid supplier ID');
}

if (empty($supplier_code)) {
    sendResponse(400, 'error', 'Supplier code is required');
}

// Check if supplier_code column exists, if not, add it
$checkColumn = $conn->query("SHOW COLUMNS FROM supplier_requests LIKE 'supplier_code'");
$hasSupplierCode = $checkColumn && $checkColumn->num_rows > 0;

if (!$hasSupplierCode) {
    // Add supplier_code column
    $alterSql = "ALTER TABLE supplier_requests ADD COLUMN supplier_code VARCHAR(50) NULL AFTER email";
    if (!$conn->query($alterSql)) {
        sendResponse(500, 'error', 'Failed to add supplier_code column: ' . $conn->error);
    }
}

// Check if supplier code already exists (excluding current supplier)
$check_stmt = $conn->prepare("SELECT id FROM supplier_requests WHERE supplier_code = ? AND id != ?");
$check_stmt->bind_param("si", $supplier_code, $supplier_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $check_stmt->close();
    sendResponse(400, 'error', 'Supplier code already exists');
}
$check_stmt->close();

// Update supplier code
$update_stmt = $conn->prepare("UPDATE supplier_requests SET supplier_code = ? WHERE id = ?");
$update_stmt->bind_param("si", $supplier_code, $supplier_id);

if ($update_stmt->execute()) {
    sendResponse(200, 'success', 'Supplier code updated successfully');
} else {
    sendResponse(500, 'error', 'Failed to update supplier code: ' . $update_stmt->error);
}

$update_stmt->close();
$conn->close();
?>

