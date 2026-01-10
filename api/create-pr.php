<?php
declare(strict_types=1);

require '../config/db.php';
require '../config/response.php';
require '../config/security.php';
require '../config/validator.php';
require '../config/env.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

// Check if user has permission to create PR from database
$userRole = $_SESSION['role'] ?? '';
$checkPermission = $conn->prepare("SELECT can_create FROM role_pr_permissions WHERE role = ? AND is_active = 1");
if ($checkPermission) {
    $checkPermission->bind_param("s", $userRole);
    $checkPermission->execute();
    $permissionResult = $checkPermission->get_result();
    $permission = $permissionResult->fetch_assoc();
    $checkPermission->close();
    
    if (!$permission || $permission['can_create'] != 1) {
        // Fallback to hardcoded check if table doesn't exist or no permission found
        $allowedRoles = ['admin', 'buyer', 'B_Head'];
        if (!in_array($userRole, $allowedRoles)) {
            sendResponse(403, "error", "You do not have permission to create PR");
        }
    }
} else {
    // Fallback to hardcoded check if table doesn't exist
    $allowedRoles = ['admin', 'buyer', 'B_Head'];
    if (!in_array($userRole, $allowedRoles)) {
        sendResponse(403, "error", "You do not have permission to create PR");
    }
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!Security::checkRateLimit($clientIP, 'create_pr', 10, 3600)) { // 10 PRs per hour
    sendResponse(429, "error", "Too many PR creation attempts. Please try again later.");
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
    sendResponse(403, "error", "Invalid CSRF token");
}

$uploadConfig = getUploadConfig();
$uploadDir = '../' . $uploadConfig['dir'] . '/';  // filesystem path for move_uploaded_file
$uploadUrl = $uploadConfig['dir'] . '/';           // URL path to store in DB

// Ensure upload directory exists with secure permissions
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    sendResponse(500, 'error', 'Failed to create upload directory.');
}
if (!is_writable($uploadDir) && !chmod($uploadDir, 0755)) {
    sendResponse(500, 'error', 'Upload directory is not writable.');
}

$conn->begin_transaction();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, 'error', 'Invalid request method.');
    }

    // Sanitize inputs
    $supplier = trim($_POST['supplier_id'] ?? $_POST['supplierId'] ?? $_POST['supplierInput'] ?? '');
    // Convert to string for comparison, handle both string and numeric IDs
    $supplier = is_numeric($supplier) ? (string)$supplier : Security::sanitizeInput($supplier);
    $buyer = isset($_POST['buyer']) ? (int) $_POST['buyer'] : (isset($_POST['buyerId']) ? (int) $_POST['buyerId'] : null);
    $qty = isset($_POST['qty']) ? (int) $_POST['qty'] : (isset($_POST['qtyInput']) ? (int) $_POST['qtyInput'] : 0);
    $uom = Security::sanitizeInput(trim($_POST['uom'] ?? $_POST['uomInput'] ?? ''));
    $remark = Security::sanitizeInput(trim($_POST['remark'] ?? $_POST['remarkInput'] ?? ''));
    $categoryName = Security::sanitizeInput(trim($_POST['category'] ?? $_POST['categoryInput'] ?? ''));
    $purchType = Security::sanitizeInput(trim($_POST['purchtype'] ?? $_POST['purchInput'] ?? ''));
    $createdBy = $_SESSION['user_id'];
    $poStatus = 1;
    
    // Initialize buyer and b_head fields
    $buyerField = null;
    $bHeadField = null;
    
    // If PR is created by buyer role, always set buyer field to current user and fetch b_head
    if ($userRole === 'buyer') {
        // Always set buyer field to the buyer creating the PR
        $buyerField = (int)$createdBy;
        
        // Fetch and validate buyer head from buyers_info table
        $bheadStmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
        if (!$bheadStmt) {
            sendResponse(500, 'error', 'Database error: Failed to prepare buyer head query.');
        }
        $bheadStmt->bind_param("i", $createdBy);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadStmt->close();
        
        if ($bheadRow && isset($bheadRow['b_head'])) {
            $bHeadField = (int)$bheadRow['b_head'];
        } else {
            // Buyer not found in buyers_info table - this shouldn't happen but handle gracefully
            sendResponse(400, 'error', 'Buyer information not found. Please contact administrator.');
        }
    } else {
        // For other roles (B_Head, admin), use the buyer from POST data if provided
        // $buyer from POST will be used as b_head
        if ($buyer !== null) {
            $bHeadField = $buyer;
        }
    }

    // Validate input data
    $validator = new Validator();
    $validationData = [
        'supplier_id' => $supplier,
        'category' => $categoryName,
        'qty' => $qty,
        'uom' => $uom,
        'remark' => $remark,
        'purchtype' => $purchType,
        'buyer' => $buyer
    ];
    
    if (!$validator->validatePR($validationData)) {
        sendResponse(400, 'error', $validator->getFirstError());
    }

    // Get category_id
    $stmt = $conn->prepare("SELECT id FROM categories WHERE maincat = ? LIMIT 1");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$category) {
        sendResponse(400, 'error', "Category '{$categoryName}' not found.");
    }
    $categoryId = (int) $category['id'];

    // Handle NEW SUPPLIER with validation
    $newSupplierId = null;
    if ($supplier === 'NEW SUPPLIER' || $supplier === '99999' || (int)$supplier === 99999) {
        $newsupplier = Security::sanitizeInput(trim($_POST['newsupplier'] ?? $_POST['newSupplierInput'] ?? ''));
        $agent = Security::sanitizeInput(trim($_POST['agent'] ?? $_POST['agentInput'] ?? ''));
        $city = Security::sanitizeInput(trim($_POST['city'] ?? $_POST['cityInput'] ?? ''));
        $gstNo = Security::sanitizeInput(trim($_POST['gstNo'] ?? $_POST['gstNoInput'] ?? ''));
        $panNo = Security::sanitizeInput(trim($_POST['panNo'] ?? $_POST['panNoInput'] ?? ''));
        $mobile = Security::sanitizeInput(trim($_POST['mobile'] ?? $_POST['mobileInput'] ?? ''));
        $email = Security::sanitizeInput(trim($_POST['email'] ?? $_POST['emailInput'] ?? ''));
        
        // Validate new supplier - New Supplier Name, GST Number, and Mobile are required
        if (!$validator->validateNewSupplier([
            'supplier' => $newsupplier, 
            'gst_no' => $gstNo, 
            'mobile' => $mobile,
            'agent' => $agent, 
            'city' => $city
        ])) {
            sendResponse(400, 'error', $validator->getFirstError());
        }

        $stmt = $conn->prepare("INSERT INTO supplier_requests (supplier, created_by, created_at, agent, city, gst_no, pan_no, mobile, email) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sississs", $newsupplier, $createdBy, $agent, $city, $gstNo, $panNo, $mobile, $email);
        $stmt->execute();
        $newSupplierId = $stmt->insert_id;
        $stmt->close();
    }

    // Insert into purchase_requests
    // Determine final b_head value: use bHeadField if set (buyer creating PR), otherwise use $buyer (buyer head/admin creating PR)
    $finalBHead = $bHeadField !== null ? $bHeadField : ($buyer !== null ? $buyer : null);
    
    // Ensure buyer field is set when buyer role creates PR
    // For other roles, buyer field can be null or set from POST
    $finalBuyer = $buyerField !== null ? $buyerField : null;
    
    $stmt = $conn->prepare("
        INSERT INTO purchase_requests (
            supplier_id, new_supplier, b_head, buyer, qty, uom, remark, po_status,
            created_by, created_at, category_id, purch_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    $stmt->bind_param(
        "siiissssiii",
        $supplier,
        $newSupplierId,
        $finalBHead,
        $finalBuyer,
        $qty,
        $uom,
        $remark,
        $poStatus,
        $createdBy,
        $categoryId,
        $purchType
    );
    $stmt->execute();
    $poId = $stmt->insert_id;
    $stmt->close();

    // Handle file uploads
    if (!empty($_FILES['files']['name'][0])) {
        $allowedTypes = $uploadConfig['allowed_types'];
        $maxFileSize = $uploadConfig['max_size'];

        foreach ($_FILES['files']['name'] as $index => $originalName) {
            $tmpPath = $_FILES['files']['tmp_name'][$index];
            $errorCode = $_FILES['files']['error'][$index];

            if ($errorCode !== UPLOAD_ERR_OK) {
                sendResponse(400, 'error', "File upload error (code {$errorCode}) for '{$originalName}'.");
            }

            $fileArray = [
                'name' => $originalName,
                'type' => $_FILES['files']['type'][$index],
                'tmp_name' => $tmpPath,
                'error' => $errorCode,
                'size' => $_FILES['files']['size'][$index]
            ];

            $fileErrors = Security::validateFile($fileArray, $allowedTypes, $maxFileSize);
            if (!empty($fileErrors)) {
                sendResponse(400, 'error', "File validation failed for '{$originalName}': " . implode(', ', $fileErrors));
            }

            $secureFileName = Security::generateSecureFilename($originalName);
            $filePath = $uploadDir . $secureFileName;   // filesystem path
            $fileUrl = $uploadUrl . $secureFileName;    // store in DB

            if (!move_uploaded_file($tmpPath, $filePath)) {
                sendResponse(500, 'error', "Failed to move uploaded file '{$originalName}'.");
            }
            chmod($filePath, 0644);

            $stmt = $conn->prepare("INSERT INTO pr_attachments (ord_id, url, filename) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $poId, $fileUrl, $secureFileName);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    sendResponse(200, 'success', 'PR created successfully!', ['po_id' => $poId]);

} catch (Exception $e) {
    $conn->rollback();
    sendResponse(500, 'error', $e->getMessage());
} finally {
    $conn->close();
}
