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
    $where = "r.role_code = 'PO_Team_Member'";
    $params = [];
    $types = "";

    if ($role == 'PO_Team_Member') {
        // Only show self
        $where .= " AND u.id = ?";
        $params[] = $userid;
        $types .= 'i';
    }

    if (!empty($search)) {
        $where .= " AND u.username LIKE ?";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $types .= 's';
    }

    // Count total
    $countSql = "SELECT COUNT(*) as total FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE $where";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Get results
    $sql = "SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE $where ORDER BY u.username ASC LIMIT ? OFFSET ?";
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
            'text' => $row['username']
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

