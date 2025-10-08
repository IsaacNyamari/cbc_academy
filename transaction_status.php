<?php
// Verify Payment with Paystack Live API
header('Content-Type: application/json');

// Set your Paystack live secret key (consider storing this in environment variables)
$paystackSecretKey = 'sk_test_5f1720558a802e25f9c4c26d844f69dd6dbd1c1c';

// Validate and sanitize input
$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'];
if (empty($reference)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Payment reference is required',
        'code' => 'MISSING_REFERENCE'
    ]);
    exit;
}

// Verify transaction with Paystack
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $paystackSecretKey",
        "Cache-Control: no-cache"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle cURL errors
if ($error) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Network error while verifying payment',
        'error' => $error,
        'code' => 'NETWORK_ERROR'
    ]);
    exit;
}

// Handle non-200 responses
if ($httpCode !== 200) {
    $responseData = json_decode($response, true) ?? [];
    http_response_code($httpCode);
    echo json_encode([
        'status' => false,
        'message' => $responseData['message'] ?? 'Payment verification failed',
        'paystack_code' => $responseData['data']['code'] ?? 'UNKNOWN_ERROR',
        'http_status' => $httpCode
    ]);
    exit;
}

// Decode and validate response
$result = json_decode($response, true);
if (!isset($result['data']['status'])) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Invalid response from Paystack',
        'code' => 'INVALID_RESPONSE'
    ]);
    exit;
}

// Check payment status
if ($result['data']['status'] !== 'success') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Payment not completed successfully',
        'transaction_status' => $result['data']['status'],
        'gateway_response' => $result['data']['gateway_response'] ?? '',
        'code' => 'PAYMENT_FAILED'
    ]);
    exit;
}

// Prepare successful response
$transaction = $result['data'];
$responseData = [
    'status' => true,
    'message' => 'Payment verified successfully',
    'transaction' => [
        'reference' => $transaction['reference'],
        'amount' => $transaction['amount'], // Convert from kobo to Naira
        'currency' => $transaction['currency'],
        'paid_at' => $transaction['paid_at'],
        'channel' => $transaction['channel'],
        'ip_address' => $transaction['ip_address'] ?? null,
        'fees' => isset($transaction['fees']) ? $transaction['fees'] / 100 : null,
        'customer' => [
            'email' => $transaction['customer']['email'],
            'name' => $transaction['customer']['name'] ?? '',
            'phone' => $transaction['customer']['phone'] ?? null
        ],
        'metadata' => $transaction['metadata'] ?? null
    ],
    'verification_time' => date('c')
];

// Add additional security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

echo json_encode($responseData, JSON_PRETTY_PRINT);