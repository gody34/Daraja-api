<?php
// Daraja credentials
$consumerKey = 'GKOigS7N8O0xQA5IApmh2QUYhu9kkxRJrXZQyn5fiE2npbk1';
$consumerSecret = 'tUM8tBOLz8FKgAibFJYg8SNWt905Ali3FL4JjHR0mbE3ryT3ldDGAU9ezHgtPjv4';
$shortcode = '174379';
$passkey = 'YOUR_PASSKEY';
$phone = '254720763456';
$amount = 10;
$callbackUrl = 'https://yourdomain.com/callback.php';

// Step 1: Get access token
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);
$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($ch);
curl_close($ch);

$access_token = json_decode($token_response)->access_token;

// Step 2: Prepare STK Push Request
$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$stk_data = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl,
    'AccountReference' => 'Invoice001',
    'TransactionDesc' => 'Payment test'
];

$stk_push_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$ch = curl_init($stk_push_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_data));
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
