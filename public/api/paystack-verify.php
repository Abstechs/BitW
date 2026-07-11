<?php
// api/paystack-verify.php
// Verify Paystack payment and update wallet

session_start();
date_default_timezone_set('Africa/Lagos');
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$reference = $_GET['reference'] ?? null;

if (!$reference) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid reference']);
    exit;
}

$settings = include __DIR__ . '/../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;

// Verify with Paystack
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$reference}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer {$paystack_secret}",
        "Content-Type: application/json",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Verification error']);
    exit;
}

$result = json_decode($response, true);

if ($result['status'] === true && $result['data']['status'] === 'success') {
    $amount = floatval($result['data']['amount'] / 100);
    $transaction_reference = $result['data']['reference'];
    $metadata = $result['data']['metadata'] ?? [];
    $user_id = $metadata['user_id'] ?? null;

    if (!$user_id || $user_id !== $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'User mismatch']);
        exit;
    }

    try {
        // Check if transaction already processed
        $checkStmt = $pdo->prepare("SELECT status FROM transactions WHERE reference = ? AND type = 'deposit'");
        $checkStmt->execute([$transaction_reference]);
        $transactionCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($transactionCheck && $transactionCheck['status'] === 'completed') {
            // Already processed - get current balance
            $walletStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $walletStmt->execute([$user_id]);
            $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Payment already processed',
                'new_balance' => number_format($wallet['balance'] ?? 0, 2)
            ]);
            exit;
        }

        // Get current wallet balance
        $walletStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $walletStmt->execute([$user_id]);
        $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Wallet not found']);
            exit;
        }

        $old_balance = floatval($wallet['balance']);
        $new_balance = $old_balance + $amount;

        $pdo->beginTransaction();

        try {
            // Update transaction status
            $updateStmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ? AND type = 'deposit'");
            $updateStmt->execute([$transaction_reference]);

            // Credit wallet
            $creditStmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $creditStmt->execute([$new_balance, $user_id]);

            $pdo->commit();

            http_response_code(200);
            echo json_encode([
                'status' => true,
                'message' => 'Payment verified successfully',
                'new_balance' => number_format($new_balance, 2)
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Error updating wallet']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Payment verification failed']);
}