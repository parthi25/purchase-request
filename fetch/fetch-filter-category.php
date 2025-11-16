<?php
header('Content-Type: application/json');
include '../config/db.php';

try {
    session_start();
    $userid = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';
    
    if (!$userid) {
        throw new Exception("User not authenticated");
    }

    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 4;
    $offset = ($page - 1) * $perPage;

    $results = ['results' => [], 'pagination' => ['more' => false]];

    // Build query based on role
    $join = "";
    $where = "1=1";
    $params = [];
    $types = "";

    if ($role == 'buyer') {
        // Get buyer head first
        $bheadStmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
        $bheadStmt->bind_param("i", $userid);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadId = $bheadRow['b_head'] ?? 0;
        $bheadStmt->close();

        if ($bheadId > 0) {
            $join = "JOIN catbasbh cb ON c.maincat = cb.cat";
            $where .= " AND cb.user_id = ?";
            $params[] = $bheadId;
            $types .= 'i';
        } else {
            echo json_encode($results);
            exit;
        }
    } elseif ($role == 'B_Head') {
        $join = "JOIN catbasbh cb ON c.maincat = cb.cat";
        $where .= " AND cb.user_id = ?";
        $params[] = $userid;
        $types .= 'i';
    }

    if (!empty($search)) {
        $where .= " AND c.maincat LIKE ?";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $types .= 's';
    }

    // Count total
    $countSql = "SELECT COUNT(DISTINCT c.id) as total FROM cat c $join WHERE $where";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Get results
    $sql = "SELECT DISTINCT c.id, c.maincat FROM cat c $join WHERE $where ORDER BY c.maincat ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results['results'][] = [
            'id' => $row['id'],
            'text' => $row['maincat']
        ];
    }
    $stmt->close();

    $results['pagination']['more'] = ($offset + $perPage) < $total;

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>

