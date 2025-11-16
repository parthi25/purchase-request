<?php
/**
 * Complete PO Order - Functional Endpoint
 * Handles completion of purchase orders with unified response format
 */

session_start();
require_once '../config/db.php';
require_once '../config/response.php';

// Enable MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validate user session
if (!isset($_SESSION['user_id'])) {
    sendResponse(403, "error", "User not logged in");
}

// Helper functions for basic validation
function validateInteger($value, $min = 1)
{
    return (isset($value) && filter_var($value, FILTER_VALIDATE_INT) !== false && intval($value) >= $min) ? intval($value) : null;
}
function sanitizeString($value)
{
    return isset($value) ? trim($value) : '';
}

// Collect input data
$po_id = validateInteger($_POST['ids'] ?? null);
$status = validateInteger($_POST['status'] ?? null);
$po_num = validateInteger($_POST['PoNum'] ?? null);
$po_qty = validateInteger($_POST['poQty'] ?? null);
$po_lines = validateInteger($_POST['poLines'] ?? null);

$status_date = sanitizeString($_POST['status_date'] ?? '');
$po_date = sanitizeString($_POST['poDate'] ?? '');
$buyer = sanitizeString($_POST['buyer'] ?? '');
$supplier = sanitizeString($_POST['supplier'] ?? '');
$supplierCode = validateInteger($_POST['supplier_code'] ?? 0, 0);
$sapsupplier_code = validateInteger($_POST['sapsupplier_code'] ?? 0, 0);
$inv_am = isset($_POST['inv_am']) ? floatval($_POST['inv_am']) : 0;

$current_date = date('Y-m-d H:i:s');
$user_id = $_SESSION['user_id'];

// Validate required fields
$errors = [];
if (!$po_id)
    $errors['ids'] = 'Valid PO ID is required';
if (!$status)
    $errors['status'] = 'Valid status is required';
if (!$po_num)
    $errors['PoNum'] = 'Valid PO number is required';
if (!$po_qty)
    $errors['poQty'] = 'Valid PO quantity is required';
if (!$po_lines)
    $errors['poLines'] = 'Valid PO lines is required';
if (empty($status_date))
    $errors['status_date'] = 'Status date is required';
if (empty($po_date))
    $errors['poDate'] = 'PO date is required';

if (!empty($errors)) {
    sendResponse(400, "error", "Validation failed", $errors);
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert or update supplier if supplier_code not provided
    if ($supplierCode == 0 && $sapsupplier_code) {
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $sapsupplier_code);
        $stmt->execute();
        $stmt->bind_result($supplierId);
        $stmt->fetch();
        $stmt->close();

        if (empty($supplierId)) {
            $stmt = $conn->prepare("INSERT INTO suppliers (supplier_id, supplier) VALUES (?, ?)");
            $stmt->bind_param("is", $sapsupplier_code, $supplier);
            $stmt->execute();
            $supplierId = $stmt->insert_id;
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE purchase_requests SET supplier_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $supplierId, $po_id);
        $stmt->execute();
        $stmt->close();
    }

    // Update purchase_requests
    $stmt = $conn->prepare("UPDATE purchase_requests SET po_status = ?, status_7 = ?, po_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $status_date, $po_date, $po_id);
    $stmt->execute();
    $stmt->close();

    // Update pr_assignments
    $stmt = $conn->prepare("UPDATE pr_assignments 
        SET po_qty = ?, po_number = ?, po_lines = ?, updated_by = ?, amount = ?, buyername = ?, supplier = ?, updated_at = ?
        WHERE ord_id = ?");
    $stmt->bind_param("iiiiisssi", $po_qty, $po_num, $po_lines, $user_id, $inv_am, $buyer, $supplier, $current_date, $po_id);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    sendResponse(200, "success", "PO order completed successfully", [
        'po_id' => $po_id,
        'status' => $status,
        'po_number' => $po_num,
        'updated_at' => $current_date
    ]);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Database error in complete.php: " . $e->getMessage());
    sendResponse(500, "error", "Database operation failed");
} catch (Exception $e) {
    $conn->rollback();
    error_log("General error in complete.php: " . $e->getMessage());
    sendResponse(500, "error", $e->getMessage());
}
?>
