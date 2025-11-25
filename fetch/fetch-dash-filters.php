<?php
header('Content-Type: application/json');
include '../config/db.php';

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    session_start();
    $userid = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';

    if (!$userid) {
        throw new Exception("User not authenticated");
    }

    // Get status options from database
    $statusResult = $conn->query("SELECT id, status FROM pr_statuses ORDER BY status ASC");
    $statusOptions = [];
    if ($statusResult) {
        while ($row = $statusResult->fetch_assoc()) {
            $statusOptions[] = [
                'id' => $row['id'],
                'status' => $row['status']
            ];
        }
        $statusResult->free();
    }

    // Get filter options based on user role
    $options = [];

    // Suppliers (available to all roles)
    $supplierResult = $conn->query("SELECT id, supplier FROM suppliers ORDER BY supplier ASC");
    $options['supplier_options'] = [];
    while ($row = $supplierResult->fetch_assoc()) {
        $options['supplier_options'][] = $row;
    }

    $purchFilterresult = $conn->query("SELECT id, name FROM purchase_types ORDER BY name ASC");
    $options['purch_options'] = [];
    if ($purchFilterresult && $purchFilterresult->num_rows > 0) {
        while ($row = $purchFilterresult->fetch_assoc()) {
            $options['purch_options'][] = $row;
        }
    }

    if ($role == 'buyer') {
        $bheadQuery = "SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1";
        $bheadStmt = $conn->prepare($bheadQuery);
        $bheadStmt->bind_param("i", $userid);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadId = $bheadRow['b_head'] ?? 0;
        $bheadStmt->close();

        // Categories mapped to this buyer through buyer_head_categories
        $catQuery = "SELECT c.id, c.maincat FROM buyer_head_categories cb
                     JOIN categories c ON c.id = cb.cat_id 
                     WHERE cb.user_id = ?
                     ORDER BY c.maincat ASC";
        $catStmt = $conn->prepare($catQuery);
        $catStmt->bind_param("i", $bheadId);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }
        $catStmt->close();

        // Get buyer head details using the ID we found
        $options['buyer_head_options'] = [];
        if ($bheadId > 0) {
            $bheadDetailsQuery = "SELECT id, username FROM users WHERE id = ?";
            $bheadDetailsStmt = $conn->prepare($bheadDetailsQuery);
            $bheadDetailsStmt->bind_param("i", $bheadId);
            $bheadDetailsStmt->execute();
            $bheadDetailsResult = $bheadDetailsStmt->get_result();
            while ($row = $bheadDetailsResult->fetch_assoc()) {
                $options['buyer_head_options'][] = $row;
            }
            $bheadDetailsStmt->close();
        }

        // PO Team Members (all)
        $poTeamResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    } elseif ($role == 'B_Head') {
        // All categories assigned to this buyer head
        $catQuery = "SELECT c.id, c.maincat FROM buyer_head_categories cb
                     JOIN categories c ON c.id = cb.cat_id 
                     WHERE cb.user_id = ?
                     ORDER BY c.maincat ASC";
        $catStmt = $conn->prepare($catQuery);
        $catStmt->bind_param("i", $userid);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }
        $catStmt->close();

        // Buyers under this buyer head (from buyers_info table)
        $buyerQuery = "SELECT u.username, u.id FROM `buyers_info` bi 
                       LEFT JOIN users u on u.id = bi.buyer 
                       WHERE bi.b_head = ?
                       ORDER BY u.username ASC";
        $buyerStmt = $conn->prepare($buyerQuery);
        $buyerStmt->bind_param("i", $userid);
        $buyerStmt->execute();
        $buyerResult = $buyerStmt->get_result();
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }
        $buyerStmt->close();

        // Show only self in buyer heads
        $selfQuery = "SELECT id, username FROM users WHERE id = ?";
        $selfStmt = $conn->prepare($selfQuery);
        $selfStmt->bind_param("i", $userid);
        $selfStmt->execute();
        $selfResult = $selfStmt->get_result();
        $options['buyer_head_options'] = [];
        while ($row = $selfResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }
        $selfStmt->close();

        // PO Team Members (all)
        $poTeamResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    } elseif ($role == 'PO_Team_Member') {
        // All categories
        $catResult = $conn->query("SELECT id, maincat FROM categories ORDER BY maincat ASC");
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        // All buyers
        $buyerResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'buyer' ORDER BY u.username ASC");
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }

        // All buyer heads
        $bheadResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'B_Head' ORDER BY u.username ASC");
        $options['buyer_head_options'] = [];
        while ($row = $bheadResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }

        // PO Team Members (only self)
        $selfQuery = "SELECT id, username FROM users WHERE id = ?";
        $selfStmt = $conn->prepare($selfQuery);
        $selfStmt->bind_param("i", $userid);
        $selfStmt->execute();
        $selfResult = $selfStmt->get_result();
        $options['po_team_member_options'] = [];
        while ($row = $selfResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
        $selfStmt->close();
    } elseif ($role == 'admin' || $role == 'PO_Team') {
        // For admin, show everything
        $catResult = $conn->query("SELECT id, maincat FROM categories ORDER BY maincat ASC");
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        $buyerResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'buyer' ORDER BY u.username ASC");
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }

        $bheadResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'B_Head' ORDER BY u.username ASC");
        $options['buyer_head_options'] = [];
        while ($row = $bheadResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }

        $poTeamResult = $conn->query("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    }

    $options['status_options'] = $statusOptions;

    // Response
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

