<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';
include '../config/response.php'; // has sendResponse()

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, "error", "Only GET method allowed.");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';

if ($id <= 0) {
    sendResponse(400, "error", "Invalid or missing ID.");
}

$allowedTables = [
    'proforma' => 'proforma',
    'po' => 'po_documents',
    'product' => 'pr_attachments'
];

if (!isset($allowedTables[$type])) {
    sendResponse(400, "error", "Invalid type parameter. Use proforma, po, or product.");
}

$table = $allowedTables[$type];

// Check if proforma table has the new columns
$hasItemColumns = false;
if ($type === 'proforma') {
    $checkStmt = $conn->query("SHOW COLUMNS FROM {$table} LIKE 'item_details_url'");
    $hasItemColumns = ($checkStmt && $checkStmt->num_rows > 0);
    if ($checkStmt) $checkStmt->close();
}

// Build SELECT query based on table type
if ($type === 'proforma' && $hasItemColumns) {
    $sql = "SELECT id, url, filename, item_details_url, item_info FROM {$table} WHERE ord_id = ?";
} else {
    $sql = "SELECT id, url, filename FROM {$table} WHERE ord_id = ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendResponse(500, "error", "Database prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $fileData = [
        'id' => (int) $row['id'],
        'url' => $row['url'],
        'filename' => $row['filename']
    ];
    
    // Add new columns for proforma if they exist
    if ($type === 'proforma' && $hasItemColumns) {
        $fileData['item_details_url'] = $row['item_details_url'] ?? null;
        $fileData['item_info'] = $row['item_info'] ?? null;
    }
    
    $files[] = $fileData;
}
$stmt->close();
$conn->close();

if (empty($files)) {
    sendResponse(200, "success", "No files found.", ['data' => []]);
}

sendResponse(200, "success", "Files fetched successfully.", $files);
?>
