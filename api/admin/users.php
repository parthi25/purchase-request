<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
$userRoleCode = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($userRoleCode, $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

// Handle API actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            // Get pagination and search parameters
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause for search
            $whereClause = '';
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $whereClause = "WHERE u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.username LIKE ? OR r.role_code LIKE ? OR r.role_name LIKE ?";
                $searchParam = "%{$search}%";
                $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
                $types = "ssssss";
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users u LEFT JOIN roles r ON u.role_id = r.id {$whereClause}";
            $countStmt = $conn->prepare($countSql);
            if (!empty($search)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalRecords = $countResult->fetch_assoc()['total'];
            $countResult->free();
            $countStmt->close();
            
            // Get paginated data with role information
            $sql = "SELECT u.id, u.fullname, u.email, u.phone, u.username, u.role_id, u.is_active, 
                           r.role_code, r.role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    {$whereClause} 
                    ORDER BY u.id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            
            if (!empty($search)) {
                $params[] = $limit;
                $params[] = $offset;
                $types .= "ii";
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt->bind_param("ii", $limit, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            
            while ($row = $result->fetch_assoc()) {
                // Add role_code as 'role' for backward compatibility with frontend
                $row['role'] = $row['role_code'] ?? '';
                $data[] = $row;
            }
            $result->free();
            $stmt->close();
            
            $totalPages = ceil($totalRecords / $limit);
            
            sendResponse(200, "success", "Users retrieved successfully", [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'per_page' => $limit
                ]
            ]);
        } catch (Exception $e) {
            sendResponse(500, "error", $e->getMessage());
        }
        break;

    case 'add':
        // Get role_id from role_code
        $roleCode = $_POST['role'] ?? '';
        if (empty($roleCode)) {
            sendResponse(400, "error", "Role is required");
        }
        
        // Only super_admin can create super_admin
        if ($roleCode === 'super_admin' && $userRoleCode !== 'super_admin') {
            sendResponse(403, "error", "Only super_admin can create super_admin users");
        }
        
        // Get role_id from role_code
        $roleStmt = $conn->prepare("SELECT id FROM roles WHERE role_code = ? AND is_active = 1");
        $roleStmt->bind_param("s", $roleCode);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        if ($roleResult->num_rows === 0) {
            $roleResult->free();
            $roleStmt->close();
            sendResponse(400, "error", "Invalid role");
        }
        $roleRow = $roleResult->fetch_assoc();
        $roleId = $roleRow['id'];
        $roleResult->free();
        $roleStmt->close();
        
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            sendResponse(400, "error", "Password is required");
        }
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, username, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("sssssii", $_POST['fullname'], $_POST['email'], $_POST['phone'], $_POST['username'], $hashed_password, $roleId, $is_active);
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(200, "success", "User added successfully");
        } else {
            $error = $stmt->error;
            $stmt->close();
            sendResponse(500, "error", "Failed to add user: " . $error);
        }
        break;

    case 'update':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            sendResponse(400, "error", "Invalid user ID");
        }
        
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $roleCode = $_POST['role'] ?? null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
    
        $fields = "fullname=?, email=?, phone=?, username=?, is_active=?";
        $types = "ssssi";
        $params = [$fullname, $email, $phone, $username, $is_active];
    
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $fields .= ", password=?";
            $types .= "s";
            $params[] = $hashed_password;
        }
    
        // Only super_admin can update role
        if ($userRoleCode === 'super_admin' && $roleCode !== null && !empty($roleCode)) {
            // Get role_id from role_code
            $roleStmt = $conn->prepare("SELECT id FROM roles WHERE role_code = ? AND is_active = 1");
            $roleStmt->bind_param("s", $roleCode);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            if ($roleResult->num_rows === 0) {
                $roleResult->free();
                $roleStmt->close();
                sendResponse(400, "error", "Invalid role");
            }
            $roleRow = $roleResult->fetch_assoc();
            $roleId = $roleRow['id'];
            $roleResult->free();
            $roleStmt->close();
            
            $fields .= ", role_id=?";
            $types .= "i";
            $params[] = $roleId;
        }
    
        $types .= "i";
        $params[] = $id;
    
        $stmt = $conn->prepare("UPDATE users SET $fields WHERE id=?");
        $stmt->bind_param($types, ...$params);
    
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(200, "success", "User updated successfully");
        } else {
            $error = $stmt->error;
            $stmt->close();
            sendResponse(500, "error", "Failed to update user: " . $error);
        }
        break;

    case 'delete':
        $delete_id = intval($_POST['id'] ?? 0);
        if ($delete_id <= 0) {
            sendResponse(400, "error", "Invalid user ID");
        }
        
        // Fetch the role_code of the user being deleted
        $stmt = $conn->prepare("SELECT r.role_code FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id=?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            $result->free();
            $stmt->close();
            sendResponse(404, "error", "User not found");
            break;
        }
    
        $row = $result->fetch_assoc();
        $delete_role = $row['role_code'] ?? '';
        $result->free();
        $stmt->close();
    
        $allowed = false;
    
        // Rule logic
        if ($userRoleCode === 'super_admin') {
            $allowed = true;
        } elseif ($userRoleCode === 'admin' && $delete_role !== 'super_admin') {
            $allowed = true;
        }
    
        if ($allowed) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $stmt->close();
                sendResponse(200, "success", "User deleted successfully");
            } else {
                $error = $stmt->error;
                $stmt->close();
                sendResponse(500, "error", "Failed to delete user: " . $error);
            }
        } else {
            sendResponse(403, "error", "Unauthorized to delete this user");
        }
        break;

    case 'toggle_status':
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        if ($id <= 0) {
            sendResponse(400, "error", "Invalid user ID");
        }
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(200, "success", "User status updated successfully");
        } else {
            $error = $stmt->error;
            $stmt->close();
            sendResponse(500, "error", "Failed to update user status: " . $error);
        }
        break;
        
    default:
        sendResponse(400, "error", "Invalid action");
}
?>


