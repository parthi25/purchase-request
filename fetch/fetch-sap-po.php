<?php
session_start();

include '../config/db.php';
include '../config/response.php';
include '../config/signature.php';

$PoNumber = $_POST['PoNumber'] ?? null;

if (!$PoNumber || empty(trim($PoNumber))) {
    sendResponse(400, "error", "PoNumber parameter is required", ['provided_value' => $PoNumber]);
}

try {
    $env = require __DIR__ . '/../config/env.php';

    $proxyApi = "http://49.207.186.21:49501/sap" ?? null;
    $backupApi = "http://49.207.186.21:49501/sap" ?? null;
    $apiUser = $env['API_USER'] ?? null;  // Fixed: use API_USER instead of SAP_USERNAME
    $apiPass = $env['API_PASS'] ?? null;  // Fixed: use API_PASS instead of SAP_PASSWORD
    $signSecret = "ok1p9a35u7v2z6rlhf8ytjd4nmeibcsgwvo" ?? null;

    // if (!$proxyApi || !$apiUser || !$apiPass || !$signSecret) {
    //     sendResponse(500, "error", "Required environment variables missing. PROXY_API: " . ($proxyApi ? 'set' : 'missing') . ", API_USER: " . ($apiUser ? 'set' : 'missing') . ", API_PASS: " . ($apiPass ? 'set' : 'missing') . ", SIGN_SECRET: " . ($signSecret ? 'set' : 'missing'));
    // }

    // Build payload
    $payload = [
        'PoNumber' => $PoNumber,
        'url' => "https://10.10.10.38:44300/sap/opu/odata/sap/ZAPI_VENDOR_PORTAL_SRV/PoHeaderSet?\$filter=Po eq '$PoNumber'&\$expand=navHeaderToItem,navHeaderToGRN&\$format=json"
    ];

    function signPayload($data, $secret) {
        if (!$secret) throw new Exception('SIGN_SECRET not set.');
        return hash_hmac('sha256', $data, $secret);
    }

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout to 60 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Connection timeout
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

    function processSAPResponse($response, $poNumber) {
        // Process the SAP response to match expected frontend structure
        if (isset($response['raw_data']['d']['results'][0])) {
            $poData = $response['raw_data']['d']['results'][0];
            
            // Extract po_date from the response
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
        
        // If structure is different, return as-is but add po_date
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

    try {
        $response = callSAPWithResponse($payload, $proxyApi, null, null, $signSecret);
        $processedData = processSAPResponse($response, $PoNumber);
        
        sendResponse(200, "success", "Primary SAP API call succeeded", [
            'po_number' => $PoNumber,
            'api_used' => 'primary',
            'data' => $processedData
        ]);
    } catch (Exception $primaryError) {
        error_log("Primary SAP API failed: " . $primaryError->getMessage());

        try {
            $response = callSAPWithResponse($payload, $backupApi, $apiUser, $apiPass, $signSecret);
            $processedData = processSAPResponse($response, $PoNumber);
            
            sendResponse(200, "success", "Backup SAP API call succeeded", [
                'po_number' => $PoNumber,
                'api_used' => 'backup',
                'data' => $processedData
            ]);
        } catch (Exception $backupError) {
            error_log("Backup SAP API also failed: " . $backupError->getMessage());
            sendResponse(500, "error", "Both primary and backup SAP API calls failed. Primary: " . $primaryError->getMessage() . " Backup: " . $backupError->getMessage());
        }
    }

} catch (Exception $e) {
    error_log("Error in SAP API handler: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error: " . $e->getMessage());
}
?>
