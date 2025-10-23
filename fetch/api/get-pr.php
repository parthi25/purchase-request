<?php
// Include the database connection
require '../../config/db.php';
require "../../config/response.php"; // your unified response helper

// Validate the ID parameter
if (!isset($_GET['id'])) {
    sendResponse(400, "error", "ID parameter is required");
}

$id = (int) $_GET['id'];
if ($id <= 0) {
    sendResponse(400, "error", "Invalid ID provided");
}

try {
    $query = "
        SELECT pt.*, 
               s.supplier, s.agent, s.city, 
               c.maincat AS category,
               u.username as bhead_name
        FROM po_tracking pt
        LEFT JOIN suppliers s ON pt.supplier_id = s.id
        LEFT JOIN cat c ON pt.category_id = c.id
        LEFT JOIN users u ON pt.b_head = u.id
        WHERE pt.id = ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        sendResponse(200, "success", "Record found successfully", $row);
    } else {
        sendResponse(404, "error", "Record not found");
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in get_record.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
