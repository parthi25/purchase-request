<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, "error", "Only GET method allowed.");
}

session_start();
include '../config/response.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$type = isset($_GET["type"]) ? strtolower(trim($_GET["type"])) : '';

if ($id <= 0 || $type === '') {
    sendResponse(400, "error", "Missing file ID or type.");
}

// Allowed tables
$allowedTables = [
    'proforma' => 'proforma',
    'po' => 'po_documents',
    'product' => 'pr_attachments'
];

if (!isset($allowedTables[$type])) {
    sendResponse(400, "error", "Invalid type parameter. Use proforma, po, or product.");
}

$table = $allowedTables[$type];

// Step 1: Fetch file URL and PR ID
$stmt = $conn->prepare("SELECT url, ord_id FROM {$table} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    sendResponse(404, "error", "File not found.");
}

$stmt->bind_result($fileUrl, $order_id);
$stmt->fetch();
$stmt->close();

// Check file delete permissions from database
$userRole = $_SESSION['role'] ?? '';
$statusStmt = $conn->prepare("SELECT po_status FROM purchase_requests WHERE id = ?");
if (!$statusStmt) {
    sendResponse(500, "error", "Database query preparation failed");
}
$statusStmt->bind_param("i", $order_id);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
if ($statusResult->num_rows === 0) {
    $statusStmt->close();
    sendResponse(404, "error", "PR not found.");
}
$prData = $statusResult->fetch_assoc();
$prStatus = (int)$prData['po_status'];
$statusStmt->close();

// Check if permissions table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'file_upload_permissions'");
$hasPermission = false;

if ($tableCheck && $tableCheck->num_rows > 0) {
    // Table exists, check permission from database
    $permStmt = $conn->prepare("SELECT can_delete FROM file_upload_permissions 
                                 WHERE role = ? AND file_type = ? AND status_id = ? AND is_active = 1");
    if ($permStmt) {
        $permStmt->bind_param("ssi", $userRole, $type, $prStatus);
        if ($permStmt->execute()) {
            $permResult = $permStmt->get_result();
            if ($permResult->num_rows > 0) {
                $permData = $permResult->fetch_assoc();
                $hasPermission = (bool)$permData['can_delete'];
            }
        }
        $permStmt->close();
    }
} else {
    // Table doesn't exist, use fallback hardcoded permissions
    if ($type === 'proforma') {
        $hasPermission = in_array($prStatus, [1, 5]) && in_array($userRole, ['B_Head', 'bhead']);
    } else if ($type === 'po') {
        $hasPermission = $prStatus === 7 && in_array($userRole, ['PO_Team', 'PO_Team_Member', 'pohead', 'poteammember']);
    } else if ($type === 'product') {
        $hasPermission = in_array($prStatus, [1, 2, 3, 4, 5]) && in_array($userRole, ['B_Head', 'buyer', 'admin', 'bhead']);
    }
}

if (!$hasPermission) {
    sendResponse(403, "error", "You do not have permission to delete files for this status.");
}

// Step 2: Delete physical file
$filePath = realpath(__DIR__ . '/../' . $fileUrl);
if ($filePath && file_exists($filePath)) {
    if (!unlink($filePath)) {
        sendResponse(500, "error", "Failed to delete file from server.");
    }
}

// Step 3: Delete database record
$deleteStmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
$deleteStmt->bind_param("i", $id);

if (!$deleteStmt->execute()) {
    $deleteStmt->close();
    $conn->close();
    sendResponse(500, "error", "Failed to delete database record.");
}

$deleteStmt->close();
$conn->close();

sendResponse(200, "success", "File deleted successfully.");
?>
