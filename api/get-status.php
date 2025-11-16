<?php
require '../config/db.php';
include '../config/response.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}
// Get input values
$current_status = $_GET['current_status'] ?? '';
$pr_id = $_GET['pr_id'] ?? '';
$role = $_SESSION["role"] ?? '';

if (empty($current_status) || empty($role)) {
    // Return empty array instead of error
    sendResponse(200, "success", "Status options retrieved", []);
}

try {
    // Determine current_status ID
    if (is_numeric($current_status)) {
        $current_status_id = (int) $current_status;
    } else {
        $stmt = $conn->prepare("SELECT id FROM pr_statuses WHERE status = ?");
        if (!$stmt) {
            // Return empty array instead of error
            sendResponse(200, "success", "Status options retrieved", []);
        }

        $stmt->bind_param("s", $current_status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            // Invalid status - return empty array silently
            sendResponse(200, "success", "Status options retrieved", []);
        }
        $current_status_id = (int) $row['id'];
    }

    // Get allowed status flows from database
    // Check if status_transitions table exists, if not fall back to old logic
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'status_transitions'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
    }

    if ($tableExists) {
        // Use database-driven flow
        $query = "SELECT sf.to_status_id, s.id, s.status 
                  FROM status_transitions sf
                  INNER JOIN pr_statuses s ON sf.to_status_id = s.id
                  WHERE sf.from_status_id = ? 
                  AND sf.role = ? 
                  AND sf.is_active = 1
                  ORDER BY sf.priority DESC, sf.id ASC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            // Return empty array instead of error
            sendResponse(200, "success", "Status options retrieved", []);
        }
        
        $stmt->bind_param("is", $current_status_id, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $statuses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Filter by proforma requirement if needed
        if (!empty($pr_id)) {
            $filteredStatuses = [];
            foreach ($statuses as $status) {
                // Check if this flow requires proforma
                $checkFlow = $conn->prepare("SELECT requires_proforma FROM status_transitions 
                                            WHERE from_status_id = ? AND to_status_id = ? AND role = ? AND is_active = 1");
                $checkFlow->bind_param("iis", $current_status_id, $status['to_status_id'], $role);
                $checkFlow->execute();
                $flowResult = $checkFlow->get_result();
                $flowRow = $flowResult->fetch_assoc();
                $checkFlow->close();

                if ($flowRow && $flowRow['requires_proforma'] == 1) {
                    // Check if proforma exists
                    $proformaCheck = $conn->prepare("SELECT COUNT(*) as count FROM proforma WHERE ord_id = ? AND filename IS NOT NULL");
                    $proformaCheck->bind_param("i", $pr_id);
                    $proformaCheck->execute();
                    $proformaResult = $proformaCheck->get_result();
                    $proformaRow = $proformaResult->fetch_assoc();
                    $proformaCheck->close();

                    if ($proformaRow['count'] > 0) {
                        $filteredStatuses[] = $status;
                    }
                } else {
                    $filteredStatuses[] = $status;
                }
            }
            $statuses = $filteredStatuses;
        }

        // Always return success, even if empty - don't show errors
        sendResponse(200, "success", "Status options retrieved", $statuses ?? []);
    } else {
        // Fallback to old hardcoded logic if table doesn't exist
        $statusAccess = [
            "admin" => [1],
            "buyer" => [3, 4, 5],
            "B_Head" => [2, 6, 8],
            "PO_Team" => [9],
            "PO_Team_Member" => [7]
        ];

        if (!isset($statusAccess[$role])) {
            // Invalid role - return empty array silently
            sendResponse(200, "success", "Status options retrieved", []);
        }

        // Special handling for status 1 (Forwarded to Buyer)
        if ($current_status_id == 1) {
            $statuses = [];
            $stmt = $conn->prepare("SELECT id, status FROM pr_statuses WHERE id in (2)");
            if (!$stmt) {
                // Return empty array instead of error
                sendResponse(200, "success", "Status options retrieved", []);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $statuses = array_merge($statuses, $result->fetch_all(MYSQLI_ASSOC));
            $stmt->close();

            if (!empty($pr_id)) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proforma WHERE ord_id = ? AND filename IS NOT NULL");
                if (!$stmt) {
                    // Return empty array instead of error
                    sendResponse(200, "success", "Status options retrieved", []);
                }
                $stmt->bind_param("i", $pr_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if ($row['count'] > 0) {
                    $stmt = $conn->prepare("SELECT id, status FROM pr_statuses WHERE id in (6)");
                    if (!$stmt) {
                        // Return empty array instead of error
                        sendResponse(200, "success", "Status options retrieved", []);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $statuses = array_merge($statuses, $result->fetch_all(MYSQLI_ASSOC));
                    $stmt->close();
                }
            }

            // Always return success, even if empty - don't show errors
            sendResponse(200, "success", "Status options retrieved", $statuses ?? []);
        } else {
            $next_status_id = $role == 'PO_Team' ? $current_status_id + 3 : $current_status_id + 1;

            if (!in_array($next_status_id, $statusAccess[$role])) {
                // No status available - return empty array silently
                sendResponse(200, "success", "Status options retrieved", []);
            } else {
                $stmt = $conn->prepare("SELECT id, status FROM pr_statuses WHERE id = ?");
                if (!$stmt) {
                    // Return empty array instead of error
                    sendResponse(200, "success", "Status options retrieved", []);
                }

                $stmt->bind_param("i", $next_status_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $statuses = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Always return success, even if empty - don't show errors
                sendResponse(200, "success", "Status options retrieved", $statuses ?? []);
            }
        }
    }

} catch (Exception $e) {
    error_log("Error in get_status.php: " . $e->getMessage());
    // Return empty array instead of error - don't show errors to users
    sendResponse(200, "success", "Status options retrieved", []);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
