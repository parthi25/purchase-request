<?php
/**
 * Standalone SAP PO Fetch API
 * Just provide a PO number and get the data
 * 
 * Usage:
 *   POST: PoNumber=4500000123
 *   GET:  ?PoNumber=4500000123
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Helper function to send JSON response
function sendResponse($statusCode, $status, $message, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

// Get PO Number from POST or GET
$PoNumber = $_POST['PoNumber'] ?? $_GET['PoNumber'] ?? null;

if (!$PoNumber || empty(trim($PoNumber))) {
    sendResponse(400, "error", "PoNumber parameter is required", ['provided_value' => $PoNumber]);
}

$PoNumber = trim($PoNumber);

// Configuration - Update these as needed
$proxyApi = "http://49.207.186.21:49501/sap";
$backupApi = "http://49.207.186.21:49501/sap";
$apiUser = null; // Set if needed: "your_username"
$apiPass = null; // Set if needed: "your_password"
$signSecret = "ok1p9a35u7v2z6rlhf8ytjd4nmeibcsgwvo";

try {
    // Build payload
    $payload = [
        'PoNumber' => $PoNumber,
        'url' => "https://10.10.10.38:44300/sap/opu/odata/sap/ZAPI_VENDOR_PORTAL_SRV/PoHeaderSet?\$filter=Po eq '$PoNumber'&\$expand=navHeaderToItem,navHeaderToGRN&\$format=json"
    ];

    // Sign payload function
    function signPayload($data, $secret) {
        if (!$secret) throw new Exception('SIGN_SECRET not set.');
        return hash_hmac('sha256', $data, $secret);
    }

    // Call SAP API function
    function callSAPWithResponse(array $payload, string $apiUrl, ?string $apiUser = null, ?string $apiPass = null, string $signSecret = '') {
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($payloadString === false) throw new Exception('Failed to encode payload');

        $headers = ['Content-Type: application/json'];

        if ($apiUser && $apiPass) {
            $auth = base64_encode("$apiUser:$apiPass");
            $headers[] = 'Authorization: Basic ' . $auth;
        } else {
            $headers[] = 'jcrc-webhook-signature: ' . signPayload($payloadString, $signSecret);
        }

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        $errorMsg = "API call failed: $curlErr, HTTP code: $httpCode";
        if ($curlInfo['total_time']) {
            $errorMsg .= ", Time taken: " . round($curlInfo['total_time'], 2) . "s";
        }
        throw new Exception($errorMsg);
    }

    // Process SAP response
    function processSAPResponse($response, $poNumber) {
        if (isset($response['raw_data']['d']['results'][0])) {
            $poData = $response['raw_data']['d']['results'][0];
            
            $poDate = '';
            if (isset($poData['docDate'])) {
                $poDate = date('Y-m-d', strtotime($poData['docDate']));
            }
            
            return [
                'raw_data' => $response['raw_data'],
                'po_date' => $poDate,
                'po_number' => $poNumber
            ];
        }
        
        $poDate = '';
        if (isset($response['docDate'])) {
            $poDate = date('Y-m-d', strtotime($response['docDate']));
        }
        
        return [
            'raw_data' => $response,
            'po_date' => $poDate,
            'po_number' => $poNumber
        ];
    }

    // Try primary API first
    try {
        $response = callSAPWithResponse($payload, $proxyApi, null, null, $signSecret);
        $processedData = processSAPResponse($response, $PoNumber);
        
        sendResponse(200, "success", "SAP API call succeeded", [
            'po_number' => $PoNumber,
            'api_used' => 'primary',
            'data' => $processedData
        ]);
    } catch (Exception $primaryError) {
        // Try backup API if primary fails
        try {
            $response = callSAPWithResponse($payload, $backupApi, $apiUser, $apiPass, $signSecret);
            $processedData = processSAPResponse($response, $PoNumber);
            
            sendResponse(200, "success", "SAP API call succeeded (backup)", [
                'po_number' => $PoNumber,
                'api_used' => 'backup',
                'data' => $processedData
            ]);
        } catch (Exception $backupError) {
            sendResponse(500, "error", "Both API calls failed. Primary: " . $primaryError->getMessage() . " | Backup: " . $backupError->getMessage());
        }
    }

} catch (Exception $e) {
    sendResponse(500, "error", "Internal server error: " . $e->getMessage());
}
?>

