<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}


// INSERT or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['b_head'], $_POST['cat_id'])) {
    try {
        $id = $_POST['id'] ?? '';
        $b_head = intval($_POST['b_head']);
        $cat_id = intval($_POST['cat_id']);

        if ($id) {
            // Check if assignment already exists (excluding current)
            $checkStmt = $conn->prepare("SELECT id FROM buyer_head_categories WHERE user_id = ? AND cat_id = ? AND id != ?");
            $checkStmt->bind_param("iii", $b_head, $cat_id, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This assignment already exists");
            }
            
            $stmt = $conn->prepare("UPDATE buyer_head_categories SET user_id = ?, cat_id = ? WHERE id = ?");
            $stmt->bind_param("iii", $b_head, $cat_id, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or assignment not found");
            }
            
            sendResponse(200, "success", "Updated successfully");
        } else {
            // Check if assignment already exists
            $checkStmt = $conn->prepare("SELECT id FROM buyer_head_categories WHERE user_id = ? AND cat_id = ?");
            $checkStmt->bind_param("ii", $b_head, $cat_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This assignment already exists");
            }
            
            $stmt = $conn->prepare("INSERT INTO buyer_head_categories (user_id, cat_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $b_head, $cat_id);
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
    try {
        $id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM buyer_head_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            sendResponse(400, "error", "Assignment not found or already deleted");
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
            $countSql = "SELECT COUNT(*) as total FROM buyer_head_categories a
                         JOIN users u ON a.user_id = u.id
                         JOIN categories c ON a.cat_id = c.id
                         WHERE u.fullname LIKE ? OR c.maincat LIKE ?";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("ss", $searchParam, $searchParam);
            $countStmt->execute();
        } else {
            $countSql = "SELECT COUNT(*) as total FROM buyer_head_categories";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute();
        }
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated results
        if (!empty($search)) {
            $sql = "SELECT a.id, a.user_id, a.cat_id, u.fullname AS buyer_name, c.maincat AS cat_name
                    FROM buyer_head_categories a
                    JOIN users u ON a.user_id = u.id
                    JOIN categories c ON a.cat_id = c.id
                    WHERE u.fullname LIKE ? OR c.maincat LIKE ?
                    ORDER BY a.id DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
        } else {
            $sql = "SELECT a.id, a.user_id, a.cat_id, u.fullname AS buyer_name, c.maincat AS cat_name
                    FROM buyer_head_categories a
                    JOIN users u ON a.user_id = u.id
                    JOIN categories c ON a.cat_id = c.id
                    ORDER BY a.id DESC LIMIT ? OFFSET ?";
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
        
        sendResponse(200, "success", "Assignments retrieved successfully", $responseData);
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

