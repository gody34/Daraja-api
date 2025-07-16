<?php
// ========== CONFIGURATION ==========
$consumerKey    = 'YOUR_CONSUMER_KEY';
$consumerSecret = 'YOUR_CONSUMER_SECRET';
$shortcode      = '174379';
$passkey        = 'YOUR_PASSKEY';
$phone          = '254720763456'; // Must start with 2547...
$amount         = 10;
$callbackUrl    = 'https://yourdomain.com/callback.php';
$logFile        = __DIR__ . '/stkpush_log.txt';

// ========== LOGGING FUNCTION ==========
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// ========== VALIDATION ==========
if (!preg_match('/^2547\d{8}$/', $phone)) {
    logMessage("Invalid phone number format: $phone");
    die(json_encode(['error' => 'Invalid phone number format']));
}

// ========== GET ACCESS TOKEN ==========
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);
$tokenUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$token_response) {
    logMessage("Failed to get access token: $token_response");
    die(json_encode(['error' => 'Failed to get access token']));
}

$access_token = json_decode($token_response)->access_token;

// ========== GENERATE PASSWORD ==========
$timestamp = date('YmdHis');
$password  = base64_encode($shortcode . $passkey . $timestamp);

// ========== STK PUSH REQUEST ==========
$stkPushData = [
    'BusinessShortCode' => $shortcode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => $shortcode,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'Invoice001',
    'TransactionDesc'   => 'Payment for order #001'
];

$stkPushUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$ch = curl_init($stkPushUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkPushData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ========== HANDLE RESPONSE ==========
if ($curlError) {
    logMessage("cURL error: $curlError");
    die(json_encode(['error' => 'Request failed: ' . $curlError]));
}

if ($httpCode !== 200) {
    logMessage("STK Push failed. HTTP $httpCode - $response");
    die(json_encode(['error' => 'STK push request failed']));
}

$responseData = json_decode($response, true);

if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0') {
    logMessage("STK Push successful: " . json_encode($responseData));
    echo json_encode(['success' => 'STK Push request sent successfully', 'data' => $responseData]);
} else {
    logMessage("STK Push error: " . json_encode($responseData));
    echo json_encode(['error' => 'Failed to initiate payment', 'data' => $responseData]);
}
?>
