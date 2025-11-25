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

// INSERT or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buyer_id'], $_POST['category_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        sendResponse(403, "error", "Invalid CSRF token");
    }
    try {
        $id = $_POST['id'] ?? '';
        $buyer_id = intval($_POST['buyer_id']);
        $category_id = intval($_POST['category_id']);

        if ($id) {
            // Check if mapping already exists (excluding current)
            $checkStmt = $conn->prepare("SELECT id FROM buyer_category_mapping WHERE buyer_id = ? AND category_id = ? AND id != ?");
            $checkStmt->bind_param("iii", $buyer_id, $category_id, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This mapping already exists");
            }
            
            $stmt = $conn->prepare("UPDATE buyer_category_mapping SET buyer_id = ?, category_id = ? WHERE id = ?");
            $stmt->bind_param("iii", $buyer_id, $category_id, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or mapping not found");
            }
            
            sendResponse(200, "success", "Updated successfully");
        } else {
            // Check if mapping already exists
            $checkStmt = $conn->prepare("SELECT id FROM buyer_category_mapping WHERE buyer_id = ? AND category_id = ?");
            $checkStmt->bind_param("ii", $buyer_id, $category_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This mapping already exists");
            }
            
            $stmt = $conn->prepare("INSERT INTO buyer_category_mapping (buyer_id, category_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $buyer_id, $category_id);
            $stmt->execute();
            
            sendResponse(200, "success", "Inserted successfully");
        }
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        sendResponse(403, "error", "Invalid CSRF token");
    }
    try {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM buyer_category_mapping WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            sendResponse(400, "error", "Mapping not found or already deleted");
        }
        
        sendResponse(200, "success", "Deleted successfully");
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// FETCH ALL with pagination and search
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search
        $whereClause = '';
        $searchParam = '';
        if (!empty($search)) {
            $searchParam = '%' . $conn->real_escape_string($search) . '%';
            $whereClause = "WHERE u.fullname LIKE ? OR c.maincat LIKE ?";
        }
        
        // Get total count
        if (!empty($search)) {
            $countSql = "SELECT COUNT(*) as total FROM buyer_category_mapping bcm
                         JOIN users u ON bcm.buyer_id = u.id
                         JOIN categories c ON bcm.category_id = c.id
                         WHERE u.fullname LIKE ? OR c.maincat LIKE ?";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("ss", $searchParam, $searchParam);
            $countStmt->execute();
        } else {
            $countSql = "SELECT COUNT(*) as total FROM buyer_category_mapping";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute();
        }
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated results
        if (!empty($search)) {
            $sql = "SELECT bcm.id, bcm.buyer_id, bcm.category_id, u.fullname AS buyer_name, c.maincat AS category_name
                    FROM buyer_category_mapping bcm
                    JOIN users u ON bcm.buyer_id = u.id
                    JOIN categories c ON bcm.category_id = c.id
                    WHERE u.fullname LIKE ? OR c.maincat LIKE ?
                    ORDER BY bcm.id DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
        } else {
            $sql = "SELECT bcm.id, bcm.buyer_id, bcm.category_id, u.fullname AS buyer_name, c.maincat AS category_name
                    FROM buyer_category_mapping bcm
                    JOIN users u ON bcm.buyer_id = u.id
                    JOIN categories c ON bcm.category_id = c.id
                    ORDER BY bcm.id DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $totalPages = ceil($totalCount / $limit);
        
        $responseData = [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalCount,
                'items_per_page' => $limit
            ]
        ];
        
        sendResponse(200, "success", "Mappings retrieved successfully", $responseData);
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

