<?php
require '../config/db.php';
require '../config/response.php';
require '../config/security.php';
require '../config/validator.php';

session_start();

$userid = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if (!$userid || !$role) {
    sendResponse(401, "error", "User not authenticated or role not set");
}

try {
    $response = [
        'suppliers' => [],
        'categories' => [],
        'buyers' => [],
        'buyer_heads' => [],
        'po_team_members' => [],
        'purchFilter' => []
    ];

    // Suppliers - Using prepared statement
    $stmt = $conn->prepare("SELECT id, supplier FROM suppliers ORDER BY supplier ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['suppliers'][] = $row;
    }
    $stmt->close();

    // Purchase filters - Using prepared statement
    $stmt = $conn->prepare("SELECT id, name FROM purchase_types ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['purchFilter'][] = $row;
    }
    $stmt->close();

    // Role-based data
    switch ($role) {
        case 'buyer':
            // Buyer head ID - Using prepared statement
            $stmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $result = $stmt->get_result();
            $bheadRow = $result->fetch_assoc();
            $bheadId = $bheadRow['b_head'] ?? 0;
            $stmt->close();

            // Categories mapped to this buyer - Using prepared statement
            if ($bheadId > 0) {
                $stmt = $conn->prepare("
                    SELECT c.id, c.maincat 
                    FROM catbasbh cb
                    JOIN categories c ON c.maincat = cb.cat
                    WHERE cb.user_id = ?
                    ORDER BY c.maincat ASC
                ");
                $stmt->bind_param("i", $bheadId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $response['categories'][] = $row;
                }
                $stmt->close();
            }

            // Buyer head details - Using prepared statement
            if ($bheadId > 0) {
                $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
                $stmt->bind_param("i", $bheadId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $response['buyer_heads'][] = $row;
                }
                $stmt->close();
            }

            // PO Team Members - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['po_team_members'][] = $row;
            }
            $stmt->close();
            break;

        case 'B_Head':
            // Categories assigned to buyer head - Using prepared statement
            $stmt = $conn->prepare("
                SELECT c.id, c.maincat 
                FROM catbasbh cb
                JOIN categories c ON c.maincat = cb.cat
                WHERE cb.user_id = ?
                ORDER BY c.maincat ASC
            ");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['categories'][] = $row;
            }
            $stmt->close();

            // Buyers under this buyer head - Using prepared statement
            $stmt = $conn->prepare("
                SELECT u.id, u.username 
                FROM buyers_info bi
                LEFT JOIN users u ON u.id = bi.buyer
                WHERE bi.b_head = ?
                ORDER BY u.username ASC
            ");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyers'][] = $row;
            }
            $stmt->close();

            // Self as buyer head - Using prepared statement
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyer_heads'][] = $row;
            }
            $stmt->close();

            // PO Team Members - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['po_team_members'][] = $row;
            }
            $stmt->close();
            break;

        case 'PO_Team_Member':
            // All categories - Using prepared statement
            $stmt = $conn->prepare("SELECT id, maincat FROM categories ORDER BY maincat ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['categories'][] = $row;
            }
            $stmt->close();

            // All buyers - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'buyer' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyers'][] = $row;
            }
            $stmt->close();

            // All buyer heads - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'B_Head' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyer_heads'][] = $row;
            }
            $stmt->close();
            break;

        case 'admin':
        case 'PO_Team':
            // All categories - Using prepared statement
            $stmt = $conn->prepare("SELECT id, maincat FROM categories ORDER BY maincat ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['categories'][] = $row;
            }
            $stmt->close();

            // All buyers - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'buyer' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyers'][] = $row;
            }
            $stmt->close();

            // All buyer heads - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'B_Head' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['buyer_heads'][] = $row;
            }
            $stmt->close();

            // PO Team Members - Using prepared statement
            $stmt = $conn->prepare("SELECT u.id, u.username FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_code = 'PO_Team_Member' ORDER BY u.username ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['po_team_members'][] = $row;
            }
            $stmt->close();
            break;
    }

    sendResponse(200, "success", "Filters retrieved successfully", $response);

} catch (Exception $e) {
    error_log("Error in filters.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
