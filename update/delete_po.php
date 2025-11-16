<?php
ini_set('display_errors', 0);
error_reporting(0);

require '../config/db.php';
require '../config/response.php'; // assuming sendResponse() is in this file

// Get and validate POST parameters
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$file_url = isset($_POST['file_url']) ? trim($_POST['file_url']) : '';

if ($order_id === 0 || empty($file_url)) {
    sendResponse(400, "error", "Invalid parameters.");
}

try {
    // Delete file from server if it exists
    $file_path = '../' . $file_url;
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            sendResponse(500, "error", "Failed to delete file from server.");
        }
    }

    // Delete record from database
    $stmt = $conn->prepare("DELETE FROM po_documents WHERE ord_id = ? AND url = ?");
    $stmt->bind_param("is", $order_id, $file_url);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        sendResponse(200, "success", "File and record deleted successfully.");
    } else {
        sendResponse(404, "error", "Record not found.");
    }

    $stmt->close();
} catch (Exception $e) {
    sendResponse(500, "error", "Error: " . $e->getMessage());
}

$conn->close();
