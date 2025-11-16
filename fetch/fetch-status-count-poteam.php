<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // unified sendResponse

if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not authenticated");
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$id = isset($_GET['buyer_id']) ? (int) $_GET['buyer_id'] : 0;

try {
    $params = [];
    $types = "";
    
    if ($role === 'PO_Team') {
        // PO Head: counts for all team members
        if ($id && $id !== $user_id) {
            // Specific PO member selected - show counts for that member
            $statusQuery = "
                SELECT s.id, s.status, COUNT(p.po_status) AS count
                FROM pr_statuses s
                LEFT JOIN purchase_requests p ON s.id = p.po_status
                LEFT JOIN pr_assignments ptm ON ptm.ord_id = p.id
                WHERE ptm.po_team_member = ?
                GROUP BY s.id
                ORDER BY s.id
            ";
            $params = [$id];
            $types = "i";
        } else {
            // buyer_id = 0 or PO Head's own ID - show only status 6, 9, 7
            $statusQuery = "
                SELECT s.id, s.status, COUNT(p.po_status) AS count
                FROM pr_statuses s
                LEFT JOIN purchase_requests p ON s.id = p.po_status AND s.id IN (6, 9, 7)
                WHERE s.id IN (6, 9, 7)
                GROUP BY s.id
                ORDER BY FIELD(s.id, 6, 9, 7)
            ";
            $params = [];
            $types = "";
        }
    } else {
        // PO_Team_Member or others: only own counts
        $statusQuery = "
            SELECT s.id, s.status, COUNT(p.po_status) AS count
            FROM pr_statuses s
            LEFT JOIN purchase_requests p ON s.id = p.po_status
            LEFT JOIN pr_assignments ptm ON ptm.ord_id = p.id
            WHERE ptm.po_team_member = ?
            GROUP BY s.id
            ORDER BY s.id
        ";
        $params = [$id > 0 ? $id : $user_id];
        $types = "i";
    }

    $stmt = $conn->prepare($statusQuery);
    if (!$stmt) sendResponse(500, "error", "Database query preparation failed");

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $statusCounts = [];
    while ($row = $result->fetch_assoc()) {
        $statusCounts[] = [
            'status_id' => (int) $row['id'],
            'status_key' => $row['status'],
            'count' => (int) $row['count'],
            'label' => $row['status']
        ];
    }

    // Apply custom order - for PO Head with buyer_id=0, only show 6,9,7
    if ($role === 'PO_Team' && ($id == 0 || $id == $user_id)) {
        $customOrder = [6, 9, 7];
    } else {
        $customOrder = [1,2,3,4,5,6,9,7,8];
    }
    
    usort($statusCounts, function($a, $b) use ($customOrder) {
        $posA = array_search($a['status_id'], $customOrder);
        $posB = array_search($b['status_id'], $customOrder);

        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;

        return $posA <=> $posB;
    });

    // Build executed query with actual parameter values
    $executedQuery = $statusQuery;
    if (!empty($params)) {
        foreach ($params as $param) {
            $executedQuery = preg_replace('/\?/', is_numeric($param) ? $param : "'" . addslashes($param) . "'", $executedQuery, 1);
        }
    }
    
    // Clean the query string for frontend (remove newlines, tabs, and extra spaces)
    $cleanedQuery = preg_replace('/\s+/', ' ', trim($executedQuery));
    $cleanedQuery = str_replace(["\n", "\r", "\t"], ' ', $cleanedQuery);
    $cleanedQuery = preg_replace('/\s+/', ' ', $cleanedQuery);
    
    sendResponse(200, "success", "Status counts retrieved successfully", [
        'counts' => $statusCounts,
        'query' => $cleanedQuery
    ]);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetch_status_count_poteam.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
