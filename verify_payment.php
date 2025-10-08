<?php
require_once 'includes/config.php'; // Your database configuration

// Set Paystack secret key
$paystackSecretKey = 'sk_test_5f1720558a802e25f9c4c26d844f69dd6dbd1c1c';

// Get the reference from POST data
$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';
$plan = $input['plan'] ?? '';
$amount = $input['amount'] ?? 0;
$student_id = $_SESSION['user_id'] ?? 0;

if (empty($reference)) {
    echo json_encode(['status' => false, 'message' => 'No reference supplied']);
    exit;
}

// Verify with Paystack API
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $paystackSecretKey
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (!$result || !$result['status']) {
    echo json_encode(value: ['status' => false, 'message' => 'Invalid Paystack response']);
    exit;
}

if ($result['data']['status'] !== 'success') {
    echo json_encode(['status' => false, 'message' => 'Payment not successful']);
    exit;
}

// Payment was successful - update your database
try {
    // Note: Paystack amounts are in the smallest currency unit (cents for KES)
    // Convert amount from cents to Kenyan Shillings
    $amount_in_kes = $amount;

    // Insert payment record
    $stmt = $pdo->prepare("INSERT INTO payments 
                          (student_id, method, transaction_code, amount, plan, date_paid)
                          VALUES 
                          (:student_id, 'mobile', :transaction_code, :amount, :plan, NOW())");

    $stmt->execute([
        ':student_id' => $student_id,
        ':transaction_code' => $reference,
        ':amount' => $amount_in_kes,
        ':plan' => $plan
    ]);

    // Update user subscription status if you have such a table
    // This is optional depending on your system

    $updateStmt = $pdo->prepare("UPDATE users SET 
                                    subscription_plan = :plan,
                                    subscription_status = 'active',
                                    is_active='1'
                                    WHERE id = :user_id");

    $updateStmt->execute([
        ':plan' => $plan,
        ':user_id' => $student_id
    ]);


    echo json_encode([
        'status' => true,
        'message' => 'Payment verified and recorded'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
