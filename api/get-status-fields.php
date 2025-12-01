<?php
/**
 * Get Status Modal Fields Configuration
 * Returns which fields should be shown for a given status
 */

session_start();
require_once '../config/db.php';
require_once '../config/response.php';

// Check if status_id is provided
$status_id = isset($_GET['status_id']) ? (int) $_GET['status_id'] : 0;

if ($status_id <= 0) {
    sendResponse(200, "success", "No fields configured", []);
    exit;
}

try {
    // Check if the status_modal_fields table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'status_modal_fields'");
    if ($checkTable->num_rows == 0) {
        // Table doesn't exist, return empty array (fallback to old behavior)
        sendResponse(200, "success", "Table not found, using default behavior", []);
        exit;
    }

    // Fetch field configuration for the given status
    $stmt = $conn->prepare("
        SELECT field_name, is_required, field_order, db_column_name
        FROM status_modal_fields
        WHERE status_id = ?
        ORDER BY field_order ASC
    ");
    
    if (!$stmt) {
        sendResponse(200, "success", "Query preparation failed", []);
        exit;
    }
    
    $stmt->bind_param("i", $status_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        $fields[] = [
            'field_name' => $row['field_name'],
            'is_required' => (bool) $row['is_required'],
            'field_order' => (int) $row['field_order'],
            'db_column_name' => $row['db_column_name'] ?? null
        ];
    }
    
    $stmt->close();
    
    sendResponse(200, "success", "Fields fetched successfully", $fields);
    
} catch (Exception $e) {
    // Silently handle errors - return empty array
    error_log("Error fetching status fields: " . $e->getMessage());
    sendResponse(200, "success", "Error occurred, using default behavior", []);
}

$conn->close();
?>

