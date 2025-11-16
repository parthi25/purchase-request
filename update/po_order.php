<?php
declare(strict_types=1);

require '../config/db.php';
require '../config/response.php'; // contains sendResponse()
require '../config/env.php';

header('Content-Type: application/json; charset=utf-8');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Only POST method allowed");
}

// Validate uploaded files
if (!isset($_FILES["files"])) {
    sendResponse(400, "error", "No files uploaded");
}

// Validate order ID
$order_id = isset($_POST["order_id"]) ? (int) $_POST["order_id"] : 0;
if ($order_id <= 0) {
    sendResponse(400, "error", "Valid order ID is required");
}

$uploadConfig = getUploadConfig();
$uploadDir = realpath(__DIR__ . '/../' . $uploadConfig['dir']);
if ($uploadDir === false) {
    sendResponse(500, "error", "Upload directory does not exist");
}

// Configuration
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx', 'csv'];
$maxFileSize = $uploadConfig['max_size'];

$files = $_FILES['files'];
$uploadedFilesCount = 0;
$errors = [];
$uploadResults = [];

for ($i = 0; $i < count($files['name']); $i++) {
    $fileName = basename($files['name'][$i]);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileSize = $files['size'][$i];
    $tmpName = $files['tmp_name'][$i];

    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "$fileName: Invalid file type.";
        continue;
    }

    if ($fileSize > $maxFileSize) {
        $errors[] = "$fileName: File too large.";
        continue;
    }

    if (!is_uploaded_file($tmpName)) {
        $errors[] = "$fileName: File upload error.";
        continue;
    }

    // Generate a safe, unique filename
    $newFileName = time() . "_" . bin2hex(random_bytes(8)) . "." . $fileExtension;
    $filePath = $uploadDir . '/' . $newFileName;
    $fileUrl = $uploadConfig['dir'] . '/' . $newFileName;

    if (move_uploaded_file($tmpName, $filePath)) {
        $stmt = $conn->prepare("INSERT INTO pr_attachments (ord_id, url, filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $order_id, $fileUrl, $newFileName);

        if ($stmt->execute()) {
            $uploadedFilesCount++;
            $uploadResults[] = [
                'id' => $stmt->insert_id,
                'file' => $newFileName,
                'full_path' => $filePath,
                'table' => 'pr_attachments'
            ];
        } else {
            $errors[] = "$fileName: Database error (" . $stmt->error . ")";
            if (file_exists($filePath))
                unlink($filePath);
        }

        $stmt->close();
    } else {
        $errors[] = "$fileName: Failed to move uploaded file.";
    }
}

$conn->close();

if ($uploadedFilesCount > 0) {
    sendResponse(200, "success", "Files uploaded successfully", [
        'uploaded_count' => $uploadedFilesCount,
        'files' => $uploadResults,
        'errors' => $errors
    ]);
} else {
    sendResponse(400, "error", "No files uploaded", ['errors' => $errors]);
}
