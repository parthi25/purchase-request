<?php
/**
 * Fetch Purchase Types - Purchase Type Fetcher
 * Handles purchase type data fetching with unified response format
 */

require_once '../../config/db.php';
require_once '../../config/response.php'; // Unified API response helper

// Ensure content type
header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT id, name FROM purchase_types ORDER BY name ASC";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $purchase_types = [];
    while ($row = $result->fetch_assoc()) {
        $purchase_types[] = [
            'id' => $row['id'],
            'text' => $row['name']
        ];
    }
    $stmt->close();

    sendResponse(200, "success", "Purchase types fetched successfully", $purchase_types);

} catch (mysqli_sql_exception $e) {
    error_log("Database error in fetch_purchtype.php: " . $e->getMessage());
    sendResponse(500, "error", "Database operation failed");
} catch (Exception $e) {
    error_log("General error in fetch_purchtype.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
