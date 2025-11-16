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
        $cat_name = $_POST['cat_name'] ?? '';

        // Get category name if not provided
        if (empty($cat_name)) {
            $catStmt = $conn->prepare("SELECT maincat FROM categories WHERE id = ?");
            $catStmt->bind_param("i", $cat_id);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            if ($catRow = $catResult->fetch_assoc()) {
                $cat_name = $catRow['maincat'];
            }
        }

        if ($id) {
            // Check if assignment already exists (excluding current)
            $checkStmt = $conn->prepare("SELECT id FROM catbasbh WHERE user_id = ? AND cat = ? AND id != ?");
            $checkStmt->bind_param("isi", $b_head, $cat_name, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This assignment already exists");
            }
            
            $stmt = $conn->prepare("UPDATE catbasbh SET user_id = ?, cat = ? WHERE id = ?");
            $stmt->bind_param("isi", $b_head, $cat_name, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or assignment not found");
            }
            
            sendResponse(200, "success", "Updated successfully");
        } else {
            // Check if assignment already exists
            $checkStmt = $conn->prepare("SELECT id FROM catbasbh WHERE user_id = ? AND cat = ?");
            $checkStmt->bind_param("is", $b_head, $cat_name);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This assignment already exists");
            }
            
            // Get buyer head name
            $userStmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
            $userStmt->bind_param("i", $b_head);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userRow = $userResult->fetch_assoc();
            $buyerName = $userRow['fullname'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO catbasbh (user_id, Name, cat) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $b_head, $buyerName, $cat_name);
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
        $stmt = $conn->prepare("DELETE FROM catbasbh WHERE id = ?");
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

// FETCH ALL
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT a.id, a.user_id, a.cat AS cat_id, u.fullname AS buyer_name, c.maincat AS cat_name
                FROM catbasbh a
                JOIN users u ON a.user_id = u.id
                JOIN categories c ON a.cat = c.maincat
                ORDER BY a.id DESC";
        
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        sendResponse(200, "success", "Assignments retrieved successfully", $data);
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

