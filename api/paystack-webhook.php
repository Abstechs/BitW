<?php
// api/paystack-webhook.php
// Handle Paystack webhook events

require_once "../core/config.php";
require_once "../core/database.php";
require_once "../core/wallet.php";

$settings = include __DIR__ . '/../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;

// Verify webhook signature
$input = @file_get_contents("php://input");
$event = json_decode($input, true);

// Get signature from header
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$hash = hash_hmac('sha512', $input, $paystack_secret);

if ($hash !== $signature) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Log event
file_put_contents(__DIR__ . '/../logs/paystack.log', date('Y-m-d H:i:s') . ' - Event: ' . json_encode($event) . PHP_EOL, FILE_APPEND);

if ($event['event'] === 'charge.success') {
    $reference = $event['data']['reference'];
    $amount = floatval($event['data']['amount'] / 100); // Convert from kobo to naira

    // Find the transaction record
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ? AND type = 'deposit' AND status = 'pending'");
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        $user_id = $transaction['user_id'];

        // Update transaction status
        $updateStmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ?");
        $updateStmt->execute([$reference]);

        // Credit wallet
        creditWallet($user_id, $amount, 'deposit', 'Paystack deposit - ' . $reference);

        echo json_encode(['status' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Transaction not found']);
    }
} else {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Event received']);
}

