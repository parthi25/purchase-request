<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';
include '../../config/security.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

// Create or Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        sendResponse(403, "error", "Invalid CSRF token");
    }
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = trim($_POST['status']);
        
        // Validate input
        if (empty($status)) {
            sendResponse(400, "error", "Status name cannot be empty");
        }
        
        if ($id > 0) {
            // Update existing status
            $stmt = $conn->prepare("UPDATE pr_statuses SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or status not found");
            }
            
            sendResponse(200, "success", "Status updated successfully");
        } else {
            // Check if status already exists
            $checkStmt = $conn->prepare("SELECT id FROM pr_statuses WHERE status = ?");
            $checkStmt->bind_param("s", $status);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(400, "error", "Status name already exists");
            }
            
            // Create new status
            $stmt = $conn->prepare("INSERT INTO pr_statuses (status) VALUES (?)");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            
            sendResponse(200, "success", "Status added successfully");
        }
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Delete Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        sendResponse(403, "error", "Invalid CSRF token");
    }
    try {
        $id = intval($_POST['delete_id']);
        
        // Check if status is being used in purchase_requests
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_requests WHERE po_status = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            sendResponse(400, "error", "Cannot delete status. It is being used in purchase requests.");
        }
        
        // Check if status is being used in status_transitions
        $checkStmt2 = $conn->prepare("SELECT COUNT(*) as count FROM status_transitions WHERE from_status_id = ? OR to_status_id = ?");
        $checkStmt2->bind_param("ii", $id, $id);
        $checkStmt2->execute();
        $result2 = $checkStmt2->get_result();
        $row2 = $result2->fetch_assoc();
        
        if ($row2['count'] > 0) {
            sendResponse(400, "error", "Cannot delete status. It is being used in status flow transitions.");
        }
        
        // Check if status is being used in role_status_permissions
        $checkStmt3 = $conn->prepare("SELECT COUNT(*) as count FROM role_status_permissions WHERE status_id = ?");
        $checkStmt3->bind_param("i", $id);
        $checkStmt3->execute();
        $result3 = $checkStmt3->get_result();
        $row3 = $result3->fetch_assoc();
        
        if ($row3['count'] > 0) {
            sendResponse(400, "error", "Cannot delete status. It is being used in role status permissions.");
        }
        
        // Check if status is being used in status_modal_fields
        $checkStmt4 = $conn->prepare("SELECT COUNT(*) as count FROM status_modal_fields WHERE status_id = ?");
        $checkStmt4->bind_param("i", $id);
        $checkStmt4->execute();
        $result4 = $checkStmt4->get_result();
        $row4 = $result4->fetch_assoc();
        
        if ($row4['count'] > 0) {
            sendResponse(400, "error", "Cannot delete status. It is being used in status modal fields.");
        }
        
        $stmt = $conn->prepare("DELETE FROM pr_statuses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            sendResponse(400, "error", "Status not found or already deleted");
        }
        
        sendResponse(200, "success", "Status deleted successfully");
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Get all statuses with pagination and search
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search
        $whereClause = '';
        if (!empty($search)) {
            $searchParam = '%' . $conn->real_escape_string($search) . '%';
            $whereClause = "WHERE status LIKE ?";
        }
        
        // Get total count
        if (!empty($search)) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM pr_statuses WHERE status LIKE ?");
            $countStmt->bind_param("s", $searchParam);
            $countStmt->execute();
        } else {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM pr_statuses");
            $countStmt->execute();
        }
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated results
        if (!empty($search)) {
            $sql = "SELECT * FROM pr_statuses WHERE status LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $searchParam, $limit, $offset);
        } else {
            $sql = "SELECT * FROM pr_statuses ORDER BY id ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $statuses = [];
        
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row;
        }
        
        $totalPages = ceil($totalCount / $limit);
        
        $responseData = [
            'data' => $statuses,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalCount,
                'items_per_page' => $limit
            ]
        ];
        
        sendResponse(200, "success", "Statuses retrieved successfully", $responseData);
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

