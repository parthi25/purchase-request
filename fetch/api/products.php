<?php
include '../../config/db.php';
include '../../config/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $perPage;

    // Build query
    $where = "1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where .= " AND name LIKE ?";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $types .= 's';
    }

    // Count total
    $countSql = "SELECT COUNT(*) as total FROM pr_product WHERE $where";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Get results
    $sql = "SELECT id, name FROM pr_product WHERE $where ORDER BY name ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();

    // Return in Select2 format if q parameter exists (AJAX request)
    if (isset($_GET['q'])) {
        $results = ['results' => [], 'pagination' => ['more' => false]];
        foreach ($products as $product) {
            $results['results'][] = [
                'id' => $product['id'],
                'text' => $product['name']
            ];
        }
        $results['pagination']['more'] = ($offset + $perPage) < $total;
        echo json_encode($results);
    } else {
        // Return in original format for backward compatibility
        sendResponse(200, "success", "Products retrieved successfully", $products);
    }
} catch (Throwable $th) {
    error_log("Error in products.php: " . $th->getMessage());
    if (isset($_GET['q'])) {
        echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    } else {
        sendResponse(500, "error", "Internal server error");
    }
}
