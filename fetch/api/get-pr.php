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
               u.username as bhead_name,
               pt.b_head as bhead_id,
               bu.username as buyer_name,
               po.username as po_team_name,
               st.status as status_name,
               ptm.po_team_member,
               ptm.buyername as po_team_member_buyername,
               ptm.po_number,
               upo.username as po_team_member_name
        FROM purchase_requests pt
        LEFT JOIN suppliers s ON pt.supplier_id = s.id
        LEFT JOIN categories c ON pt.category_id = c.id
        LEFT JOIN users u ON pt.b_head = u.id
        LEFT JOIN users bu ON pt.buyer = bu.id
        LEFT JOIN users po ON pt.po_team = po.id
        LEFT JOIN pr_statuses st ON pt.po_status = st.id
        LEFT JOIN pr_assignments ptm ON pt.id = ptm.ord_id
        LEFT JOIN users upo ON ptm.po_team_member = upo.id
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
