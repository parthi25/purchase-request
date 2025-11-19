<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../config/response.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    // Check for NULL purch_id
    $sqlNull = "SELECT COUNT(*) as count FROM purchase_requests WHERE purch_id IS NULL";
    $resultNull = $conn->query($sqlNull);
    $nullCount = $resultNull->fetch_assoc()['count'];
    
    // Check for purch_id that don't exist in purchase_types
    $sqlInvalid = "SELECT COUNT(*) as count 
                   FROM purchase_requests pt 
                   LEFT JOIN purchase_types pm ON pm.id = pt.purch_id 
                   WHERE pt.purch_id IS NOT NULL AND pm.id IS NULL";
    $resultInvalid = $conn->query($sqlInvalid);
    $invalidCount = $resultInvalid->fetch_assoc()['count'];
    
    // Get sample records with NULL purch_id
    $sqlSamples = "SELECT id, purch_id, created_at 
                   FROM purchase_requests 
                   WHERE purch_id IS NULL 
                   LIMIT 10";
    $resultSamples = $conn->query($sqlSamples);
    $samples = [];
    while ($row = $resultSamples->fetch_assoc()) {
        $samples[] = $row;
    }
    
    // Get sample records with invalid purch_id
    $sqlInvalidSamples = "SELECT pt.id, pt.purch_id, pt.created_at 
                          FROM purchase_requests pt 
                          LEFT JOIN purchase_types pm ON pm.id = pt.purch_id 
                          WHERE pt.purch_id IS NOT NULL AND pm.id IS NULL 
                          LIMIT 10";
    $resultInvalidSamples = $conn->query($sqlInvalidSamples);
    $invalidSamples = [];
    while ($row = $resultInvalidSamples->fetch_assoc()) {
        $invalidSamples[] = $row;
    }
    
    // Check for NULL buyer_id
    $sqlNullBuyer = "SELECT COUNT(*) as count FROM purchase_requests WHERE buyer IS NULL";
    $resultNullBuyer = $conn->query($sqlNullBuyer);
    $nullBuyerCount = $resultNullBuyer->fetch_assoc()['count'];
    
    // Check for buyer_id that don't exist in users
    $sqlInvalidBuyer = "SELECT COUNT(*) as count 
                        FROM purchase_requests pt 
                        LEFT JOIN users b ON b.id = pt.buyer 
                        WHERE pt.buyer IS NOT NULL AND b.id IS NULL";
    $resultInvalidBuyer = $conn->query($sqlInvalidBuyer);
    $invalidBuyerCount = $resultInvalidBuyer->fetch_assoc()['count'];
    
    // Get sample records with NULL buyer_id
    $sqlBuyerSamples = "SELECT id, buyer, created_at 
                        FROM purchase_requests 
                        WHERE buyer IS NULL 
                        LIMIT 10";
    $resultBuyerSamples = $conn->query($sqlBuyerSamples);
    $buyerSamples = [];
    while ($row = $resultBuyerSamples->fetch_assoc()) {
        $buyerSamples[] = $row;
    }
    
    // Get sample records with invalid buyer_id
    $sqlInvalidBuyerSamples = "SELECT pt.id, pt.buyer, pt.created_at 
                               FROM purchase_requests pt 
                               LEFT JOIN users b ON b.id = pt.buyer 
                               WHERE pt.buyer IS NOT NULL AND b.id IS NULL 
                               LIMIT 10";
    $resultInvalidBuyerSamples = $conn->query($sqlInvalidBuyerSamples);
    $invalidBuyerSamples = [];
    while ($row = $resultInvalidBuyerSamples->fetch_assoc()) {
        $invalidBuyerSamples[] = $row;
    }
    
    sendResponse(200, "success", "Check completed", [
        'null_purch_id_count' => (int)$nullCount,
        'invalid_purch_id_count' => (int)$invalidCount,
        'total_unknown_purch' => (int)$nullCount + (int)$invalidCount,
        'null_purch_samples' => $samples,
        'invalid_purch_samples' => $invalidSamples,
        'null_buyer_id_count' => (int)$nullBuyerCount,
        'invalid_buyer_id_count' => (int)$invalidBuyerCount,
        'total_unknown_buyer' => (int)$nullBuyerCount + (int)$invalidBuyerCount,
        'null_buyer_samples' => $buyerSamples,
        'invalid_buyer_samples' => $invalidBuyerSamples
    ]);
    
} catch (Exception $e) {
    sendResponse(500, "error", "Error: " . $e->getMessage());
}
?>

