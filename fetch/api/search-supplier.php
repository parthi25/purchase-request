<?php
include '../../config/db.php';
include '..//..//config/response.php';

$search = isset($_POST['search']) ? trim($_POST['search']) . '%' : null;
if (!$search) {
    sendResponse(400, "error", "Search parameter is required");
}

try {
    $stmt = $conn->prepare("SELECT id, supplier, agent, city FROM suppliers WHERE supplier LIKE ? LIMIT 5");
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();

    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row; // Keep keys consistent with DB
    }

    sendResponse(200, "success", count($suppliers) ? "Suppliers found" : "No suppliers found", $suppliers);
    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetch_names.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
