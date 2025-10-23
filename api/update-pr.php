<?php
require '../config/db.php';
require '../config/response.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

if (!isset($_POST['id'])) {
    sendResponse(400, "error", "Missing PO ID.");
}

$id = (int) $_POST['id'];
$supplier_id = trim($_POST['supplier_id'] ?? $_POST['supplierId'] ?? $_POST['supplierInput'] ?? '');
$buyer_id = isset($_POST['buyer']) ? (int) $_POST['buyer']
    : (isset($_POST['buyerId']) ? (int) $_POST['buyerId'] : null);
$quantity = isset($_POST['qty']) ? (int) $_POST['qty'] : (isset($_POST['qtyInput']) ? (int) $_POST['qtyInput'] : 0);
$uom = trim($_POST['uom'] ?? $_POST['uomInput'] ?? '');
$remark = trim($_POST['remark'] ?? $_POST['remarkInput'] ?? '');
$cat = trim($_POST['category'] ?? $_POST['categoryInput'] ?? '');
$purchtype = trim($_POST['purchtype'] ?? $_POST['purchInput'] ?? '');
$created_by = $_SESSION['user_id'];

// Basic validation
$errors = [];
if ($id <= 0)
    $errors[] = 'Invalid PO ID';
if ($supplier_id <= 0)
    $errors[] = 'Invalid supplier ID';
if ($quantity <= 0)
    $errors[] = 'Quantity must be greater than 0';
if (empty($uom))
    $errors[] = 'UOM is required';
if (empty($cat))
    $errors[] = 'Category is required';
if ($created_by <= 0)
    $errors[] = 'Invalid creator ID';

if (!empty($errors)) {
    sendResponse(400, "error", "Validation failed", ['errors' => $errors]);
}

// Validate buyer_id exists in users table if provided
if ($buyer_id !== null) {
    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $userStmt->bind_param("i", $buyer_id);
    $userStmt->execute();
    $userStmt->store_result();
    if ($userStmt->num_rows === 0) {
        $buyer_id = null; // invalidate buyer if not found
    }
    $userStmt->close();
}

// Get category_id from 'cat' table
$category_id = null;
$catStmt = $conn->prepare("SELECT id FROM cat WHERE maincat = ?");
$catStmt->bind_param("s", $cat);
$catStmt->execute();
$catStmt->bind_result($category_id);
$catStmt->fetch();
$catStmt->close();

if ($category_id === null) {
    sendResponse(400, "error", "Category '{$cat}' not found.");
}

// Prepare update query
if ($buyer_id !== null) {
    $updateQuery = "
        UPDATE po_tracking 
        SET supplier_id = ?, 
            b_head = ?, 
            qty = ?, 
            uom = ?, 
            remark = ?, 
            created_by = ?,  
            category_id = ?,
            purch_id = ?
        WHERE id = ?";
    $bindTypes = 'iisssiiii';
    $bindParams = [$supplier_id, $buyer_id, $quantity, $uom, $remark, $created_by, $category_id, $purchtype, $id];
} else {
    $updateQuery = "
        UPDATE po_tracking 
        SET supplier_id = ?, 
            qty = ?, 
            uom = ?, 
            remark = ?, 
            created_by = ?,  
            category_id = ?,
            purch_id = ?
        WHERE id = ?";
    $bindTypes = 'isssiiii';
    $bindParams = [$supplier_id, $quantity, $uom, $remark, $created_by, $category_id, $purchtype, $id];
}

$stmt = $conn->prepare($updateQuery);
if (!$stmt) {
    sendResponse(500, "error", "Failed to prepare update statement.");
}

$stmt->bind_param($bindTypes, ...$bindParams);

if ($stmt->execute()) {
    sendResponse(200, "success", "PO updated successfully", ['po_id' => $id]);
} else {
    sendResponse(500, "error", "Database error: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
