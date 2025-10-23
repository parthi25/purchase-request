<?php
include '../config/db.php';
include '../config/response.php';
session_start();

$query = "
    SELECT 
        SUM(CASE WHEN po_status = '1' THEN 1 ELSE 0 END) AS open_status_count,
        SUM(CASE WHEN po_status in ('7','8') THEN 1 ELSE 0 END) AS close_status_count,
        SUM(CASE WHEN po_status NOT IN ('1', '7','8') THEN 1 ELSE 0 END) AS inprogress_status_count
    FROM po_tracking
";

$result = $conn->query($query);

if (!$result) {
    sendResponse(500, "error", "Database query failed");
}

$row = $result->fetch_assoc();

$data = [
    "open" => $row["open_status_count"] ?? 0,
    "close" => $row["close_status_count"] ?? 0,
    "inprogress" => $row["inprogress_status_count"] ?? 0,
    "username" => $_SESSION["username"] ?? "",
    "role" => $_SESSION["role"] ?? ""
];

$conn->close();

sendResponse(200, "success", "Status counts fetched successfully", $data);
