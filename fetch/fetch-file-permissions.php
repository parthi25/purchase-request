<?php
session_start();
include '../config/db.php';
include '../config/response.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

$userRole = $_SESSION['role'] ?? '';

try {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'file_upload_permissions'");
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist, return empty permissions (will use fallback in frontend)
        sendResponse(200, "success", "File upload permissions retrieved successfully", []);
    }
    
    // Fetch all file upload permissions for the current user's role
    $query = "SELECT file_type, status_id, can_upload, can_delete 
              FROM file_upload_permissions 
              WHERE role = ? AND is_active = 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Database query preparation failed: " . $conn->error);
        // Return empty permissions on error (will use fallback)
        sendResponse(200, "success", "File upload permissions retrieved successfully", []);
    }
    
    $stmt->bind_param("s", $userRole);
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        $stmt->close();
        // Return empty permissions on error (will use fallback)
        sendResponse(200, "success", "File upload permissions retrieved successfully", []);
    }
    
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $fileType = $row['file_type'];
        $statusId = $row['status_id'];
        
        if (!isset($permissions[$fileType])) {
            $permissions[$fileType] = [
                'upload_statuses' => [],
                'delete_statuses' => []
            ];
        }
        
        if ($row['can_upload']) {
            $permissions[$fileType]['upload_statuses'][] = (int)$statusId;
        }
        
        if ($row['can_delete']) {
            $permissions[$fileType]['delete_statuses'][] = (int)$statusId;
        }
    }
    
    $stmt->close();
    
    sendResponse(200, "success", "File upload permissions retrieved successfully", $permissions);
    
} catch (Exception $e) {
    error_log("Error in fetch-file-permissions.php: " . $e->getMessage());
    // Return empty permissions on error (will use fallback in frontend)
    sendResponse(200, "success", "File upload permissions retrieved successfully", []);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>

