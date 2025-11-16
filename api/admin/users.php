<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}

// Helper: get ENUM values for roles
function getEnumValues($table, $column, $conn) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    $row = $result->fetch_assoc();
    preg_match("/^enum\((.*)\)$/", $row['Type'], $matches);
    return str_getcsv($matches[1], ',', "'");
}

// Handle API actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $res = $conn->query("SELECT id, fullname, email, phone, username, role, is_active FROM users ORDER BY id DESC");
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        sendResponse(200, "success", "Users retrieved successfully", $data);
        break;

    case 'add':
        // Only super_admin can create super_admin
        $requestedRole = $_POST['role'] ?? '';
        $currentRole = $_SESSION['role'] ?? '';
        
        if ($requestedRole === 'super_admin' && $currentRole !== 'super_admin') {
            sendResponse(403, "error", "Only super_admin can create super_admin users");
        }
        
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, username, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt->bind_param("ssssssi", $_POST['fullname'], $_POST['email'], $_POST['phone'], $_POST['username'], $hashed_password, $_POST['role'], $is_active);
        if ($stmt->execute()) {
            sendResponse(200, "success", "User added successfully");
        } else {
            sendResponse(500, "error", "Failed to add user: " . $stmt->error);
        }
        break;

    case 'update':
        $id = $_POST['id'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $username = $_POST['username'];
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? null;
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
    
        // Only super_admin can update role to super_admin
        if ($_SESSION['role'] === 'super_admin' && $role !== null) {
            $fields .= ", role=?";
            $types .= "s";
            $params[] = $role;
        }
    
        $types .= "i";
        $params[] = $id;
    
        $stmt = $conn->prepare("UPDATE users SET $fields WHERE id=?");
        $stmt->bind_param($types, ...$params);
    
        if ($stmt->execute()) {
            sendResponse(200, "success", "User updated successfully");
        } else {
            sendResponse(500, "error", "Failed to update user: " . $stmt->error);
        }
        break;

    case 'delete':
        $delete_id = $_POST['id'];
        
        // Fetch the role of the user being deleted
        $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            sendResponse(404, "error", "User not found");
            break;
        }
    
        $row = $result->fetch_assoc();
        $delete_role = $row['role'];
        $current_role = $_SESSION['role'];
    
        $allowed = false;
    
        // Rule logic
        if ($current_role === 'super_admin') {
            $allowed = true;
        } elseif ($current_role === 'admin' && $delete_role !== 'super_admin') {
            $allowed = true;
        }
    
        if ($allowed) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                sendResponse(200, "success", "User deleted successfully");
            } else {
                sendResponse(500, "error", "Failed to delete user: " . $stmt->error);
            }
        } else {
            sendResponse(403, "error", "Unauthorized to delete this user");
        }
        break;

    case 'toggle_status':
        $id = intval($_POST['id']);
        $is_active = intval($_POST['is_active']);
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $id);
        
        if ($stmt->execute()) {
            sendResponse(200, "success", "User status updated successfully");
        } else {
            sendResponse(500, "error", "Failed to update user status: " . $stmt->error);
        }
        break;

    case 'roles':
        $roles = getEnumValues('users', 'role', $conn);
        sendResponse(200, "success", "Roles retrieved successfully", $roles);
        break;
        
    default:
        sendResponse(400, "error", "Invalid action");
}
?>

