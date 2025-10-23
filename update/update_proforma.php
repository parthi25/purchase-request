<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';
include '../config/response.php'; // contains sendResponse()
include '../config/env.php';

$uploadConfig = getUploadConfig();
$uploadDir = realpath(__DIR__ . '/../' . $uploadConfig['dir']);
$allowedMimeTypes = $uploadConfig['allowed_types'];
$maxFileSize = $uploadConfig['max_size'];

// Ensure upload directory exists
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    sendResponse(500, "error", "Failed to create upload directory.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Only POST method allowed.");
}

if (!isset($_FILES["files"])) {
    sendResponse(400, "error", "No files uploaded.");
}

$order_id = isset($_POST["order_id"]) ? intval($_POST["order_id"]) : 0;
$type = isset($_POST["type"]) ? $_POST["type"] : '';

if ($order_id <= 0) {
    sendResponse(400, "error", "Invalid order ID.");
}

$uploadedFiles = [];
$errors = [];

// Process each file
foreach ($_FILES["files"]["tmp_name"] as $key => $tmpName) {
    if (empty($tmpName))
        continue;

    $fileMimeType = mime_content_type($tmpName);
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        $errors[] = "Invalid file type for " . $_FILES["files"]["name"][$key];
        continue;
    }

    if ($_FILES["files"]["size"][$key] > $maxFileSize) {
        $errors[] = "File too large for " . $_FILES["files"]["name"][$key] . ". Max size: 10MB.";
        continue;
    }

    $originalFileName = basename($_FILES["files"]["name"][$key]);
    $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    $safeFileName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $newFileName = time() . "_" . $key . "_" . $safeFileName;
    $filePath = $uploadDir . '/' . $newFileName;
    $fileUrl = $uploadConfig['dir'] . '/' . $newFileName;

    if (!move_uploaded_file($tmpName, $filePath)) {
        $errors[] = "File upload failed for " . $_FILES["files"]["name"][$key];
        continue;
    }

    $uploadedFiles[] = [
        'url' => $fileUrl,
        'filename' => $newFileName
    ];
}

if (empty($uploadedFiles)) {
    sendResponse(400, "error", "No valid files were uploaded.", ['errors' => $errors]);
}

// Handle "change" type
if ($type === 'change') {
    $stmt = $conn->prepare("SELECT url FROM proforma WHERE ord_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($oldFile);
        while ($stmt->fetch()) {
            $oldFilePath = '../' . $oldFile;
            if (file_exists($oldFilePath))
                unlink($oldFilePath);
        }

        $deleteStmt = $conn->prepare("DELETE FROM proforma WHERE ord_id = ?");
        $deleteStmt->bind_param("i", $order_id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
    $stmt->close();
}

// Insert new records
$successCount = 0;
$stmt = $conn->prepare("INSERT INTO proforma (ord_id, url, filename) VALUES (?, ?, ?)");

foreach ($uploadedFiles as $file) {
    $stmt->bind_param("iss", $order_id, $file['url'], $file['filename']);
    if ($stmt->execute())
        $successCount++;
}
$stmt->close();
$conn->close();

if ($successCount > 0) {
    sendResponse(200, "success", "File(s) uploaded successfully.", ['uploaded_count' => $successCount, 'files' => $uploadedFiles, 'errors' => $errors]);
} else {
    sendResponse(500, "error", "Database insert failed.", ['errors' => $errors]);
}
?>
