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
    'po' => 'po_',
    'product' => 'po_order'
];

if (!isset($allowedTables[$type])) {
    sendResponse(400, "error", "Invalid type parameter. Use proforma, po, or product.");
}

$table = $allowedTables[$type];

// fetch files from the matching table
$stmt = $conn->prepare("SELECT id, url, filename FROM {$table} WHERE ord_id = ?");
if (!$stmt) {
    sendResponse(500, "error", "Database prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = [
        'id' => (int) $row['id'],
        'url' => $row['url'],
        'filename' => $row['filename']
    ];
}
$stmt->close();
$conn->close();

if (empty($files)) {
    sendResponse(200, "success", "No files found.", ['data' => []]);
}

sendResponse(200, "success", "Files fetched successfully.", $files);
?>
