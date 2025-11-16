<?php
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
if (!Security::checkRateLimit($clientIP, 'status_update', 50, 3600)) { // 50 updates per hour
    sendResponse(429, "error", "Too many status update attempts. Please try again later.");
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
    sendResponse(403, "error", "Invalid CSRF token");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        sendResponse(405, "error", "Invalid request method");
    }

    if (!isset($_POST['ids'], $_POST['status'])) {
        sendResponse(400, "error", "Missing required parameters");
    }

    // Input validation
    $validator = new Validator();
    $validationData = [
        'ids' => $_POST['ids'],
        'status' => $_POST['status'],
        'buyerInput' => $_POST['buyerInput'] ?? null,
        'poHeadInput' => $_POST['poHeadInput'] ?? null,
        'qtyInput' => $_POST['qtyInput'] ?? null,
        'remarkInput' => $_POST['remarkInput'] ?? null
    ];
    
    if (!$validator->validateStatusUpdate($validationData)) {
        sendResponse(400, "error", $validator->getFirstError());
    }

    $idsArray = is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [intval($_POST['ids'])];
    $status = intval($_POST['status']);
    $statusDate = (new DateTime())->format('Y-m-d H:i:s');

    $buyer = Security::sanitizeInput($_POST['buyerInput'] ?? '');
    $po_team = Security::sanitizeInput($_POST['poHeadInput'] ?? '');
    $qtyInput = Security::sanitizeInput($_POST['qtyInput'] ?? '');
    $remark = Security::sanitizeInput($_POST['remarkInput'] ?? '');

    // File upload with enhanced security
    $fileUrl = null;
    if (isset($_FILES['files']) && $_FILES['files']['error'][0] === UPLOAD_ERR_OK) {
        $uploadConfig = getUploadConfig();
        $uploadDir = __DIR__ . '/../' . $uploadConfig['dir'] . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Create file array for Security validation
        $fileArray = [
            'name' => $_FILES['files']['name'][0],
            'type' => $_FILES['files']['type'][0],
            'tmp_name' => $_FILES['files']['tmp_name'][0],
            'error' => $_FILES['files']['error'][0],
            'size' => $_FILES['files']['size'][0]
        ];

        // Validate file using Security class
        $allowedTypes = $uploadConfig['allowed_types'];
        $maxFileSize = $uploadConfig['max_size'];

        $fileErrors = Security::validateFile($fileArray, $allowedTypes, $maxFileSize);
        if (!empty($fileErrors)) {
            sendResponse(400, "error", "File validation failed: " . implode(', ', $fileErrors));
        }

        // Generate secure filename
        $secureFileName = Security::generateSecureFilename($_FILES['files']['name'][0]);
        $filePath = $uploadDir . $secureFileName;

        if (move_uploaded_file($_FILES['files']['tmp_name'][0], $filePath)) {
            // Set proper file permissions
            chmod($filePath, 0644);
            $fileUrl = '/' . $uploadConfig['dir'] . '/' . $secureFileName;
        } else {
            sendResponse(500, "error", "File upload failed");
        }
    }

$statusMapping = [
    2 => 'status_1',
    3 => 'status_2',
    4 => 'status_3',
    5 => 'status_4',
    6 => 'status_5',
    7 => 'status_6',
    8 => 'status_7'
];

$updateFields = ["updated_at = CURRENT_TIMESTAMP", "po_status = ?"];
$updateValues = [$status];
$updateTypes = "i";

if (isset($statusMapping[$status])) {
    $updateFields[] = "{$statusMapping[$status]} = ?";
    $updateValues[] = $statusDate;
    $updateTypes .= "s";
}

if ($remark) {
    if ($status === 2)
        $updateFields[] = "b_remark = ?";
    if ($status === 5)
        $updateFields[] = "to_bh_rm = ?";
    if ($status === 6)
        $updateFields[] = "po_team_rm = ?";
    if ($status === 8)
        $updateFields[] = "rrm = ?";
    $updateValues[] = $remark;
    $updateTypes .= "s";
}

if ($buyer) {
    $updateFields[] = "buyer = ?";
    $updateValues[] = intval($buyer);
    $updateTypes .= "i";
}

if ($po_team) {
    $updateFields[] = "po_team = ?";
    $updateValues[] = intval($po_team);
    $updateTypes .= "i";
}

if ($qtyInput !== null && $qtyInput !== '') {
    $updateFields[] = "qty = ?";
    $updateValues[] = $qtyInput;
    $updateTypes .= "s";
}

$placeholders = implode(',', array_fill(0, count($idsArray), '?'));
$query = "UPDATE purchase_requests SET " . implode(", ", $updateFields) . " WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->bind_param($updateTypes . str_repeat('i', count($idsArray)), ...$updateValues, ...$idsArray);
$stmt->execute();
$stmt->close();

if ($fileUrl) {
    $insertQuery = "INSERT INTO proforma (ord_id, url, filename) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $filename = basename($fileUrl);
    foreach ($idsArray as $id) {
        $stmt->bind_param("iss", $id, $fileUrl, $filename);
        $stmt->execute();
    }
    $stmt->close();
}

sendResponse(200, "success", "PO status updated successfully");
}
catch (Exception $e) {
    sendResponse(500, $e->getMessage(),$updateFields,$placeholders);
}
?>
