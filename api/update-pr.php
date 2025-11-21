<?php
require '../config/db.php';
require '../config/response.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

// Check if user has permission to edit PR from database
$userRole = $_SESSION['role'] ?? '';
$checkPermission = $conn->prepare("SELECT can_edit, can_edit_status FROM role_pr_permissions WHERE role = ? AND is_active = 1");
$allowedEditStatus = null;
if ($checkPermission) {
    $checkPermission->bind_param("s", $userRole);
    $checkPermission->execute();
    $permissionResult = $checkPermission->get_result();
    $permission = $permissionResult->fetch_assoc();
    $checkPermission->close();
    
    if (!$permission || $permission['can_edit'] != 1) {
        // Fallback to hardcoded check if table doesn't exist or no permission found
        $allowedRoles = ['admin', 'buyer', 'B_Head', 'super_admin', 'master'];
        if (!in_array($userRole, $allowedRoles)) {
            sendResponse(403, "error", "You do not have permission to edit PR");
        }
        $allowedEditStatus = 1; // Default: only status 1
    } else {
        $allowedEditStatus = $permission['can_edit_status']; // NULL means any status
    }
} else {
    // Fallback to hardcoded check if table doesn't exist
    $allowedRoles = ['admin', 'buyer', 'B_Head', 'super_admin', 'master'];
    if (!in_array($userRole, $allowedRoles)) {
        sendResponse(403, "error", "You do not have permission to edit PR");
    }
    $allowedEditStatus = 1; // Default: only status 1
}

if (!isset($_POST['id'])) {
    sendResponse(400, "error", "Missing PO ID.");
}

$id = (int) $_POST['id'];
$supplier_id = trim($_POST['supplier_id'] ?? $_POST['supplierId'] ?? $_POST['supplierInput'] ?? '');
$buyer_head_id = isset($_POST['buyerId']) ? (int) $_POST['buyerId'] : null;
$buyer_id = isset($_POST['buyer']) && $_POST['buyer'] !== '' ? (int) $_POST['buyer'] : null;
$po_team = isset($_POST['po_team']) && $_POST['po_team'] !== '' ? (int) $_POST['po_team'] : null;
$po_status = isset($_POST['po_status']) && $_POST['po_status'] !== '' ? (int) $_POST['po_status'] : null;
$po_team_member = isset($_POST['po_team_member']) && $_POST['po_team_member'] !== '' ? (int) $_POST['po_team_member'] : null;
$po_number = trim($_POST['po_number'] ?? '');
$buyer_name = trim($_POST['buyer_name'] ?? '');
$quantity = isset($_POST['qty']) ? (int) $_POST['qty'] : (isset($_POST['qtyInput']) ? (int) $_POST['qtyInput'] : 0);
$uom = trim($_POST['uom'] ?? $_POST['uomInput'] ?? '');
$remark = trim($_POST['remark'] ?? $_POST['remarkInput'] ?? '');
$b_remark = trim($_POST['b_remark'] ?? '');
$po_team_rm = trim($_POST['po_team_rm'] ?? '');
$rrm = trim($_POST['rrm'] ?? '');
$to_bh_rm = trim($_POST['to_bh_rm'] ?? '');
$cat = trim($_POST['category'] ?? $_POST['categoryInput'] ?? '');
$purchtype = trim($_POST['purchtype'] ?? $_POST['purchInput'] ?? '');
$created_by = $_SESSION['user_id'];

// Basic validation
$errors = [];
if ($id <= 0)
    $errors[] = 'Invalid PO ID';
if ($supplier_id <= 0)
    $errors[] = 'Invalid supplier ID';
if ($quantity <= 0)
    $errors[] = 'Quantity must be greater than 0';
if (empty($uom))
    $errors[] = 'UOM is required';
if (empty($cat))
    $errors[] = 'Category is required';
if ($created_by <= 0)
    $errors[] = 'Invalid creator ID';

if (!empty($errors)) {
    sendResponse(400, "error", "Validation failed", ['errors' => $errors]);
}

// Validate buyer_id exists in users table if provided
if ($buyer_id !== null && $buyer_id > 0) {
    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $userStmt->bind_param("i", $buyer_id);
    $userStmt->execute();
    $userStmt->store_result();
    if ($userStmt->num_rows === 0) {
        $buyer_id = null; // invalidate buyer if not found
    }
    $userStmt->close();
}

// Validate po_team exists if provided
if ($po_team !== null && $po_team > 0) {
    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'PO_Team'");
    $userStmt->bind_param("i", $po_team);
    $userStmt->execute();
    $userStmt->store_result();
    if ($userStmt->num_rows === 0) {
        $po_team = null;
    }
    $userStmt->close();
}

// Validate po_team_member exists if provided
if ($po_team_member !== null && $po_team_member > 0) {
    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'PO_Team_Member'");
    $userStmt->bind_param("i", $po_team_member);
    $userStmt->execute();
    $userStmt->store_result();
    if ($userStmt->num_rows === 0) {
        $po_team_member = null;
    }
    $userStmt->close();
}

// Validate po_status exists if provided
if ($po_status !== null && $po_status > 0) {
    $statusStmt = $conn->prepare("SELECT id FROM pr_statuses WHERE id = ?");
    $statusStmt->bind_param("i", $po_status);
    $statusStmt->execute();
    $statusStmt->store_result();
    if ($statusStmt->num_rows === 0) {
        $po_status = null;
    }
    $statusStmt->close();
}

// Get category_id from 'categories' table
$category_id = null;
$catStmt = $conn->prepare("SELECT id FROM categories WHERE maincat = ?");
$catStmt->bind_param("s", $cat);
$catStmt->execute();
$catStmt->bind_result($category_id);
$catStmt->fetch();
$catStmt->close();

if ($category_id === null) {
    sendResponse(400, "error", "Category '{$cat}' not found.");
}

// Check if PR exists and is in editable status (status 1 = Open)
$checkStmt = $conn->prepare("SELECT po_status, created_by FROM purchase_requests WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$prData = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$prData) {
    sendResponse(404, "error", "PR not found");
}

// Check if PR can be edited based on status restriction
// Super admin and master can edit any PR regardless of status
if ($userRole !== 'super_admin' && $userRole !== 'master') {
    if ($allowedEditStatus !== null && $prData['po_status'] != $allowedEditStatus) {
        sendResponse(403, "error", "PR can only be edited when status is " . $allowedEditStatus);
    }
}

// Start transaction
$conn->autocommit(false);

try {
    // Build dynamic update query for purchase_requests
    $updateFields = [];
    $updateParams = [];
    $updateTypes = '';
    
    $updateFields[] = "supplier_id = ?";
    $updateParams[] = $supplier_id;
    $updateTypes .= 'i';
    
    if ($buyer_head_id !== null) {
        $updateFields[] = "b_head = ?";
        $updateParams[] = $buyer_head_id;
        $updateTypes .= 'i';
    }
    
    if ($buyer_id !== null) {
        $updateFields[] = "buyer = ?";
        $updateParams[] = $buyer_id;
        $updateTypes .= 'i';
    }
    
    if ($po_team !== null) {
        $updateFields[] = "po_team = ?";
        $updateParams[] = $po_team;
        $updateTypes .= 'i';
    }
    
    if ($po_status !== null) {
        $updateFields[] = "po_status = ?";
        $updateParams[] = $po_status;
        $updateTypes .= 'i';
    }
    
    $updateFields[] = "qty = ?";
    $updateParams[] = $quantity;
    $updateTypes .= 'i';
    
    $updateFields[] = "uom = ?";
    $updateParams[] = $uom;
    $updateTypes .= 's';
    
    $updateFields[] = "remark = ?";
    $updateParams[] = $remark;
    $updateTypes .= 's';
    
    if ($b_remark !== '') {
        $updateFields[] = "b_remark = ?";
        $updateParams[] = $b_remark;
        $updateTypes .= 's';
    }
    
    if ($po_team_rm !== '') {
        $updateFields[] = "po_team_rm = ?";
        $updateParams[] = $po_team_rm;
        $updateTypes .= 's';
    }
    
    if ($rrm !== '') {
        $updateFields[] = "rrm = ?";
        $updateParams[] = $rrm;
        $updateTypes .= 's';
    }
    
    if ($to_bh_rm !== '') {
        $updateFields[] = "to_bh_rm = ?";
        $updateParams[] = $to_bh_rm;
        $updateTypes .= 's';
    }
    
    $updateFields[] = "category_id = ?";
    $updateParams[] = $category_id;
    $updateTypes .= 'i';
    
    $updateFields[] = "purch_id = ?";
    $updateParams[] = $purchtype;
    $updateTypes .= 'i';
    
    $updateParams[] = $id;
    $updateTypes .= 'i';
    
    $updateQuery = "UPDATE purchase_requests SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $stmt->bind_param($updateTypes, ...$updateParams);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update purchase_requests: " . $stmt->error);
    }
    $stmt->close();
    
    // Handle pr_assignments table
    if ($po_team_member !== null || $po_number !== '' || $buyer_name !== '') {
        // Check if assignment exists
        $checkAssign = $conn->prepare("SELECT id FROM pr_assignments WHERE ord_id = ?");
        $checkAssign->bind_param("i", $id);
        $checkAssign->execute();
        $assignResult = $checkAssign->get_result();
        $assignExists = $assignResult->num_rows > 0;
        $checkAssign->close();
        
        if ($assignExists) {
            // Update existing assignment
            $assignFields = [];
            $assignParams = [];
            $assignTypes = '';
            
            if ($po_team_member !== null) {
                $assignFields[] = "po_team_member = ?";
                $assignParams[] = $po_team_member;
                $assignTypes .= 'i';
            }
            
            if ($po_number !== '') {
                $assignFields[] = "po_number = ?";
                $assignParams[] = $po_number;
                $assignTypes .= 's';
            }
            
            if ($buyer_name !== '') {
                $assignFields[] = "buyername = ?";
                $assignParams[] = $buyer_name;
                $assignTypes .= 's';
            }
            
            if (!empty($assignFields)) {
                $assignFields[] = "updated_at = CURRENT_TIMESTAMP";
                $assignParams[] = $id;
                $assignTypes .= 'i';
                
                $assignQuery = "UPDATE pr_assignments SET " . implode(", ", $assignFields) . " WHERE ord_id = ?";
                $assignStmt = $conn->prepare($assignQuery);
                if (!$assignStmt) {
                    throw new Exception("Failed to prepare pr_assignments update: " . $conn->error);
                }
                $assignStmt->bind_param($assignTypes, ...$assignParams);
                if (!$assignStmt->execute()) {
                    throw new Exception("Failed to update pr_assignments: " . $assignStmt->error);
                }
                $assignStmt->close();
            }
        } else if ($po_team_member !== null) {
            // Insert new assignment
            $insertAssign = $conn->prepare("INSERT INTO pr_assignments (ord_id, po_team_member, po_number, buyername, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            if (!$insertAssign) {
                throw new Exception("Failed to prepare pr_assignments insert: " . $conn->error);
            }
            $insertAssign->bind_param("iisss", $id, $po_team_member, $po_number, $buyer_name, $created_by);
            if (!$insertAssign->execute()) {
                throw new Exception("Failed to insert pr_assignments: " . $insertAssign->error);
            }
            $insertAssign->close();
        }
    }
    
    // Commit transaction
    $conn->commit();
    sendResponse(200, "success", "PR updated successfully", ['po_id' => $id]);
    
} catch (Exception $e) {
    $conn->rollback();
    sendResponse(500, "error", "Database error: " . $e->getMessage());
} finally {
    $conn->autocommit(true);
    $conn->close();
}
?>
