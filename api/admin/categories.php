<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}


// Create or Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maincat'])) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $maincat = trim($_POST['maincat']);
        
        // Validate input
        if (empty($maincat)) {
            sendResponse(400, "error", "Category name cannot be empty");
        }
        
        if ($id > 0) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET maincat = ? WHERE id = ?");
            $stmt->bind_param("si", $maincat, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or category not found");
            }
            
            sendResponse(200, "success", "Category updated successfully");
        } else {
            // Check if category already exists
            $checkStmt = $conn->prepare("SELECT id FROM categories WHERE maincat = ?");
            $checkStmt->bind_param("s", $maincat);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(400, "error", "Category name already exists");
            }
            
            // Create new category
            $stmt = $conn->prepare("INSERT INTO categories (maincat) VALUES (?)");
            $stmt->bind_param("s", $maincat);
            $stmt->execute();
            
            sendResponse(200, "success", "Category added successfully");
        }
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $id = intval($_POST['delete_id']);
        
        // Check if category is being used
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_requests WHERE category_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            sendResponse(400, "error", "Cannot delete category. It is being used in purchase requests.");
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            sendResponse(400, "error", "Category not found or already deleted");
        }
        
        sendResponse(200, "success", "Category deleted successfully");
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Get all categories with pagination and search
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
            $whereClause = "WHERE maincat LIKE ?";
        }
        
        // Get total count
        if (!empty($search)) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM categories WHERE maincat LIKE ?");
            $countStmt->bind_param("s", $searchParam);
            $countStmt->execute();
        } else {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM categories");
            $countStmt->execute();
        }
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated results
        if (!empty($search)) {
            $sql = "SELECT * FROM categories WHERE maincat LIKE ? ORDER BY maincat ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $searchParam, $limit, $offset);
        } else {
            $sql = "SELECT * FROM categories ORDER BY maincat ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $totalPages = ceil($totalCount / $limit);
        
        $responseData = [
            'data' => $categories,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalCount,
                'items_per_page' => $limit
            ]
        ];
        
        sendResponse(200, "success", "Categories retrieved successfully", $responseData);
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

