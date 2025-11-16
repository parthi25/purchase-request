<?php
ini_set('display_errors', 0);
error_reporting(0);

// Enable error logging for debugging
error_log("=== File Upload Debug ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
error_log("Session data: " . print_r($_SESSION, true));

include '../config/db.php';
include '../config/response.php';
include '../config/security.php';
include '../config/validator.php';
include '../config/env.php';

// Start session for CSRF validation
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!Security::checkRateLimit($clientIP, 'file_upload', 20, 3600)) { // 20 uploads per hour
    sendResponse(429, "error", "Too many file uploads. Please try again later.");
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
    error_log("CSRF validation failed - Token: " . ($_POST['csrf_token'] ?? 'not set') . ", Session token: " . ($_SESSION['csrf_token'] ?? 'not set'));
    sendResponse(403, "error", "Invalid CSRF token");
}

$uploadConfig = getUploadConfig();
$uploadDir = realpath(__DIR__ . '/../' . $uploadConfig['dir']);
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    sendResponse(500, "error", "Failed to create upload directory.");
}

// Enhanced file validation
$allowedMimeTypes = $uploadConfig['allowed_types'];
$maxFileSize = $uploadConfig['max_size'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Only POST method allowed.");
}

if (!isset($_FILES["file"])) {
    error_log("No file uploaded - FILES array: " . print_r($_FILES, true));
    sendResponse(400, "error", "No file uploaded.");
}

$order_id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$type = isset($_POST["type"]) ? strtolower(trim($_POST["type"])) : '';

// Input validation
$validator = new Validator();
if (!$validator->validateFileUpload(['id' => $order_id, 'type' => $type])) {
    sendResponse(400, "error", $validator->getFirstError());
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

// Process uploaded file with enhanced security
$file = $_FILES["file"];

// Validate file using Security class
$fileErrors = Security::validateFile($file, $allowedMimeTypes, $maxFileSize);
if (!empty($fileErrors)) {
    sendResponse(400, "error", implode(', ', $fileErrors));
}

// Generate secure filename
$originalFileName = basename($file["name"]);
$secureFileName = Security::generateSecureFilename($originalFileName);
$filePath = $uploadDir . '/' . $secureFileName;
$fileUrl = $uploadConfig['dir'] . '/' . $secureFileName;

// Additional security: Check file content
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detectedMimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

if (!in_array($detectedMimeType, $allowedMimeTypes)) {
    sendResponse(400, "error", "File type mismatch detected.");
}

// Move file with error handling
if (!move_uploaded_file($file["tmp_name"], $filePath)) {
    error_log("Failed to move uploaded file from {$file['tmp_name']} to {$filePath}");
    error_log("Upload directory permissions: " . (is_writable($uploadDir) ? 'writable' : 'not writable'));
    error_log("Upload directory exists: " . (is_dir($uploadDir) ? 'yes' : 'no'));
    sendResponse(500, "error", "File upload failed - unable to move file to destination.");
}

// Set proper file permissions
chmod($filePath, 0644);

// Insert record with prepared statement
try {
    // Check if table has uploaded_by and uploaded_at columns
    $checkColumns = $conn->query("SHOW COLUMNS FROM {$table}");
    $hasUploadedBy = false;
    $hasUploadedAt = false;
    
    while ($column = $checkColumns->fetch_assoc()) {
        if ($column['Field'] === 'uploaded_by') {
            $hasUploadedBy = true;
        }
        if ($column['Field'] === 'uploaded_at') {
            $hasUploadedAt = true;
        }
    }
    
    // Build query based on available columns
    if ($hasUploadedBy && $hasUploadedAt) {
        $stmt = $conn->prepare("INSERT INTO {$table} (ord_id, url, filename, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
        $uploadedBy = $_SESSION['user_id'];
        $stmt->bind_param("issi", $order_id, $fileUrl, $secureFileName, $uploadedBy);
    } else {
        $stmt = $conn->prepare("INSERT INTO {$table} (ord_id, url, filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $order_id, $fileUrl, $secureFileName);
    }
    
    if (!$stmt) {
        throw new Exception("Database query preparation failed");
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        sendResponse(200, "success", "File uploaded successfully.", [
            'file' => [
                'url' => $fileUrl, 
                'filename' => $secureFileName,
                'size' => $file['size'],
                'type' => $detectedMimeType
            ]
        ]);
    } else {
        // Clean up uploaded file if database insert fails
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        sendResponse(500, "error", "Database insert failed.");
    }
    
} catch (Exception $e) {
    // Clean up uploaded file on error
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    error_log("File upload error: " . $e->getMessage());
    error_log("File upload error details - Table: {$table}, Order ID: {$order_id}, Type: {$type}");
    sendResponse(500, "error", "File upload failed: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
