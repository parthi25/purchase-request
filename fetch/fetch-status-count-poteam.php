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
    if ($role === 'PO_Head') {
        // PO Head: counts for all team members
        if ($id) {
            $statusQuery = "
                SELECT s.id, s.status, COUNT(p.po_status) AS count
                FROM status s
                LEFT JOIN po_tracking p ON s.id = p.po_status
                LEFT JOIN po_team_member ptm ON ptm.ord_id = p.id
                WHERE ptm.po_team_member = ?
                GROUP BY s.id
                ORDER BY s.id
            ";
            $params = [$id];
        } else {
            $statusQuery = "
                SELECT s.id, s.status, COUNT(p.po_status) AS count
                FROM status s
                LEFT JOIN po_tracking p ON s.id = p.po_status
                WHERE p.po_team = ?
                GROUP BY s.id
                ORDER BY s.id
            ";
            $params = [$user_id];
        }
    } else {
        // PO_Team_Member or others: only own counts
        $statusQuery = "
            SELECT s.id, s.status, COUNT(p.po_status) AS count
            FROM status s
            LEFT JOIN po_tracking p ON s.id = p.po_status
            LEFT JOIN po_team_member ptm ON ptm.ord_id = p.id
            WHERE ptm.po_team_member = ?
            GROUP BY s.id
            ORDER BY s.id
        ";
        $params = [$id > 0 ? $id : $user_id];
    }

    $types = "i";
    $stmt = $conn->prepare($statusQuery);
    if (!$stmt) sendResponse(500, "error", "Database query preparation failed");

    $stmt->bind_param($types, ...$params);
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

    // Apply custom order
    $customOrder = [1,2,3,4,5,6,9,7,8];
    usort($statusCounts, function($a, $b) use ($customOrder) {
        $posA = array_search($a['status_id'], $customOrder);
        $posB = array_search($b['status_id'], $customOrder);

        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;

        return $posA <=> $posB;
    });

    sendResponse(200, "success", "Status counts retrieved successfully", $statusCounts);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetch_status_count_poteam.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
