<?php
header("Content-Type: application/json");
require "../config/db.php";
require "../config/sendResponse.php"; // unified response helper

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    // ✅ READ (list all)
    if ($method === "GET" && $action === "read") {
        $sql = "SELECT pcm.id, pcm.sub_cat_id, pcm.cat_id, ps.name AS sub_cat_name, pc.name AS cat_name,
                       pcm.created_at, pcm.updated_at, pcm.is_active
                FROM pr_cat_sub_map pcm
                JOIN pr_sub_cat ps ON pcm.sub_cat_id = ps.id
                JOIN pr_cat pc ON pcm.cat_id = pc.id
                ORDER BY pcm.id DESC";
        $result = $conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        sendResponse(200, "success", "Records fetched successfully", $data);
    }

    // ✅ CREATE
    elseif ($method === "POST" && $action === "create") {
        $sub_cat_id = $_POST['sub_cat_id'] ?? null;
        $cat_id = $_POST['cat_id'] ?? null;
        $created_by = $_POST['created_by'] ?? 0;

        if ($sub_cat_id && $cat_id) {
            $stmt = $conn->prepare("INSERT INTO pr_cat_sub_map (sub_cat_id, cat_id, created_at, created_by, is_active) VALUES (?, ?, NOW(), ?, 1)");
            $stmt->bind_param("iii", $sub_cat_id, $cat_id, $created_by);
            $stmt->execute();

            sendResponse(201, "success", "Record created", ["id" => $stmt->insert_id]);
        } else {
            sendResponse(400, "error", "Missing parameters");
        }
    }

    // ✅ DYNAMIC UPDATE
    elseif ($method === "POST" && $action === "update") {
        $id = $_POST['id'] ?? null;
        if (!$id)
            sendResponse(400, "error", "Missing id");

        $fields = [];
        $params = [];
        $types = "";

        // dynamic fields
        if (isset($_POST['sub_cat_id'])) {
            $fields[] = "sub_cat_id=?";
            $params[] = $_POST['sub_cat_id'];
            $types .= "i";
        }
        if (isset($_POST['cat_id'])) {
            $fields[] = "cat_id=?";
            $params[] = $_POST['cat_id'];
            $types .= "i";
        }
        if (isset($_POST['is_active'])) {
            $fields[] = "is_active=?";
            $params[] = $_POST['is_active'];
            $types .= "i";
        }
        if (isset($_POST['updated_by'])) {
            $fields[] = "updated_by=?";
            $params[] = $_POST['updated_by'];
            $types .= "i";
        }

        $fields[] = "updated_at=NOW()"; // always update timestamp

        if (count($params) > 0) {
            $sql = "UPDATE pr_cat_sub_map SET " . implode(", ", $fields) . " WHERE id=?";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            sendResponse(200, "success", "Record updated");
        } else {
            sendResponse(400, "error", "No fields provided to update");
        }
    }

    // ✅ SOFT DELETE
    elseif ($method === "POST" && $action === "disable") {
        $id = $_POST['id'] ?? null;
        $updated_by = $_POST['updated_by'] ?? 0;
        if (!$id)
            sendResponse(400, "error", "Missing id");

        $stmt = $conn->prepare("UPDATE pr_cat_sub_map SET is_active=0, updated_at=NOW(), updated_by=? WHERE id=?");
        $stmt->bind_param("ii", $updated_by, $id);
        $stmt->execute();

        sendResponse(200, "success", "Record disabled");
    }

    // ✅ ENABLE
    elseif ($method === "POST" && $action === "enable") {
        $id = $_POST['id'] ?? null;
        $updated_by = $_POST['updated_by'] ?? 0;
        if (!$id)
            sendResponse(400, "error", "Missing id");

        $stmt = $conn->prepare("UPDATE pr_cat_sub_map SET is_active=1, updated_at=NOW(), updated_by=? WHERE id=?");
        $stmt->bind_param("ii", $updated_by, $id);
        $stmt->execute();

        sendResponse(200, "success", "Record enabled");
    } else {
        sendResponse(400, "error", "Invalid action or method");
    }

} catch (Exception $e) {
    error_log("Error in pr_cat_sub_map.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
