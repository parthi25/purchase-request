<?php
declare(strict_types=1);

require '../../config/db.php';
require '../../config/response.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'error', 'Only POST method allowed');
}

$input = json_decode(file_get_contents('php://input'), true);
$gstNo = trim($input['gst_no'] ?? '');

if (empty($gstNo)) {
    sendResponse(400, 'error', 'GST number is required');
}

try {
    // Check if GST number matches tax_number_3 in suppliers table
    $stmt = $conn->prepare("
        SELECT 
            id, 
            supplier, 
            agent, 
            city,
            tax_number_3,
            permanent_account_number,
            address,
            street,
            postal_code,
            region
        FROM suppliers 
        WHERE tax_number_3 = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        sendResponse(500, 'error', 'Database error: Failed to prepare query');
    }
    
    $stmt->bind_param("s", $gstNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    
    if ($supplier) {
        sendResponse(200, 'success', 'Supplier found', [
            'found' => true,
            'supplier' => $supplier
        ]);
    } else {
        sendResponse(200, 'success', 'No supplier found with this GST number', [
            'found' => false
        ]);
    }
} catch (Exception $e) {
    sendResponse(500, 'error', 'Database error: ' . $e->getMessage());
} finally {
    $conn->close();
}

