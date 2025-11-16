<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';
include '../config/response.php'; // contains sendResponse()

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, "error", "Only GET method allowed.");
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

// Step 1: Fetch file URL
$stmt = $conn->prepare("SELECT url FROM {$table} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    sendResponse(404, "error", "File not found.");
}

$stmt->bind_result($fileUrl);
$stmt->fetch();
$stmt->close();

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
