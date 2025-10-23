<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';
include '../config/response.php';

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    // SQL query
    $sql = "
    SELECT DISTINCT 
        p.id AS REF_ID,
        ss.status,
        u.username AS created_by_name, 
        b.username AS b_head_name, 
        bu.username AS buyer_name, 
        pt.username AS PO_HEAD, 
        CASE WHEN p.supplier_id = 99999 THEN ns.supplier ELSE s.supplier END AS supplier_name,
        CASE WHEN p.supplier_id = 99999 THEN ns.city ELSE s.city END AS city,
        CASE WHEN p.supplier_id = 99999 THEN ns.agent ELSE s.agent END AS agent,
        c.maincat AS category,
        upo.username AS PO_TEAM_PERSON,
        ptm.po_number AS po_number,
        ptm.buyername AS buyername,
        pm.name as purch_type,
        p.remark AS PR_REMARK,
        p.b_remark AS BUYER_REMARK,
        p.po_team_rm AS PO_TEAM_REMARK,
        p.rrm AS PO_TEAM_PERSON_REMARK,
        p.to_bh_rm AS BUYER_HEAD_REMARK,
        p.uom AS UOM,
        p.qty AS QTY,
        p.created_at as CREATED_AT,
        p.po_date as PO_DATE
    FROM po_tracking p
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN purchase_master pm ON pm.id = p.purch_id
    LEFT JOIN users b ON p.b_head = b.id
    LEFT JOIN users bu ON p.buyer = bu.id
    LEFT JOIN users pt ON p.po_team = pt.id
    LEFT JOIN po_ po ON p.id = po.ord_id AND po.filename IS NOT NULL
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN new_supplier ns ON p.new_supplier = ns.id 
    LEFT JOIN cat c ON p.category_id = c.id
    LEFT JOIN po_team_member ptm ON p.id = ptm.ord_id
    LEFT JOIN users upo ON ptm.po_team_member = upo.id
    LEFT JOIN status ss ON ss.id = p.po_status
    ";

    $result = $conn->query($sql);

    if (!$result) {
        sendResponse(500, "error", "SQL Error: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // If no records found
    if (empty($data)) {
        sendResponse(200, "success", "No PR tracking records found", []);
    }

    sendResponse(200, "success", "PR tracking records retrieved successfully", $data);

} catch (Exception $e) {
    error_log("Error in pr_tracking_export.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
