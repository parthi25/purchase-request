<?php
// Load environment values
$env = require __DIR__ . '/env.php';

function sign($data)
{
    global $env; // use the loaded env array
    try {
        if (empty($env['SIGN_SECRET'])) {
            throw new Exception('SIGN_SECRET not set in env.php');
        }
        $secret = $env['SIGN_SECRET'];

        // Convert data to JSON string if it’s an array or object
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        // Generate HMAC-SHA256 signature in hex
        return hash_hmac('sha256', $data, $secret);
    } catch (Exception $e) {
        throw $e;
    }
}

function attestation()
{
    global $env;

    $headers = getallheaders();
    $signature = $headers['jcrc-webhook-signature'] ?? null;

    if (!$signature) {
        http_response_code(400);
        echo json_encode(['error' => 'No signature provided']);
        exit;
    }

    $rawBody = file_get_contents('php://input');
    $generatedSignature = sign($rawBody);

    if ($generatedSignature !== $signature) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    // Signature verified — continue request
    return true;
}
?>
