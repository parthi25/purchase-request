<?php
session_start();
include '../config/db.php';
include '../config/response.php';

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not logged in");
}

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

try {
    if ($role === "B_Head") {
        // 🔹 Get buyers linked to this B_Head
        $buyerQuery = $conn->prepare("SELECT buyer FROM buyers_info WHERE b_head = ?");
        if (!$buyerQuery) {
            sendResponse(500, "error", "Database query preparation failed (buyerQuery)");
        }

        $buyerQuery->bind_param("i", $user_id);
        $buyerQuery->execute();
        $buyerResult = $buyerQuery->get_result();

        $buyerIds = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $buyerIds[] = $row["buyer"];
        }

        // 🔹 If no buyers found
        if (empty($buyerIds)) {
            sendResponse(200, "success", "No buyers found for this B_Head", []);
        }

        // 🔹 Fetch buyer names from users table
        $placeholders = implode(",", array_fill(0, count($buyerIds), "?"));
        $sql = "SELECT id, username FROM users WHERE id IN ($placeholders)";

        $userQuery = $conn->prepare($sql);
        if (!$userQuery) {
            sendResponse(500, "error", "Database query preparation failed (userQuery)");
        }

        $types = str_repeat("i", count($buyerIds));
        $userQuery->bind_param($types, ...$buyerIds);
        $userQuery->execute();
        $userResult = $userQuery->get_result();

        $buyers = [];
        while ($row = $userResult->fetch_assoc()) {
            $buyers[] = [
                "id" => $row["id"],
                "username" => $row["username"]
            ];
        }

        sendResponse(200, "success", "Buyers retrieved successfully", $buyers);
    } else {
        // 🔹 For non–B_Head users, return all active buyers
        $query = "SELECT id, username 
                  FROM users 
                  WHERE role = 'buyer' AND is_active = 1";

        $result = $conn->query($query);
        if (!$result) {
            sendResponse(500, "error", "Database query failed");
        }

        $buyers = [];
        while ($row = $result->fetch_assoc()) {
            $buyers[] = [
                "id" => $row["id"],
                "username" => $row["username"]
            ];
        }

        sendResponse(200, "success", "Buyers retrieved successfully", $buyers);
    }
} catch (Exception $e) {
    error_log("Error in fetch-buyer.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
