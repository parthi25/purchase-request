<?php
/**
 * Unified API Response Helper
 * 
 * Usage:
 *   sendResponse(200, "success", "Login successful", ["role" => "admin"]);
 *   sendResponse(400, "error", "Missing fields");
 */

function sendResponse(int $code = 200, string $status = "success", string $message = "", array $data = []): void
{
    http_response_code($code);
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=UTF-8");

    $response = [
        "status" => $status,
        "message" => $message
    ];

    if (!empty($data)) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
