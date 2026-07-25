<?php
// api/paystack-webhook.php
// Handle Paystack webhook events securely

date_default_timezone_set('Africa/Lagos');
// Stepping back 2 levels to accurately hit core files from the api directory
require_once __DIR__ . "/../../core/config.php";
require_once __DIR__ . "/../../core/database.php";

// Stepping back 2 levels to find the config directory in the root
$settings = include __DIR__ . '/../../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;

// Verify webhook signature
$input = @file_get_contents("php://input");
$event = json_decode($input, true);

// Get signature from header
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$hash = hash_hmac('sha512', $input, $paystack_secret);

// Ensure the logs folder exists or fall back to native error logging
$logPath = __DIR__ . '/../../logs/paystack.log';
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

if ($hash !== $signature) {
    http_response_code(401);
    @file_put_contents($logPath, date('Y-m-d H:i:s') . ' - Invalid signature' . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => false, 'message' => 'Invalid signature']);
    exit;
}

// Log all inbound events
@file_put_contents(
    $logPath,
    date('Y-m-d H:i:s') . ' - Event: ' . ($event['event'] ?? 'unknown') . ' | Reference: ' . ($event['data']['reference'] ?? 'N/A') . PHP_EOL,
    FILE_APPEND
);

if (isset($event['event']) && $event['event'] === 'charge.success') {
    $reference = $event['data']['reference'];
    $amount = floatval($event['data']['amount'] / 100); // Convert from kobo to Naira
    $metadata = $event['data']['metadata'] ?? [];
    $internal_ref = $metadata['internal_reference'] ?? 'no_ref';

    try {
        $pdo->beginTransaction();

        // 1. Find the existing transaction record initialized by the user and lock the row
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ? AND type = 'deposit' AND status = 'pending' FOR UPDATE");
        $stmt->execute([$reference]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($transaction) {
            $user_id = $transaction['user_id'];

            // 2. Look up user directly from the users table (where balance resides)
            $userStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$user_id]);
            $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userRow) {
                $old_balance = floatval($userRow['balance']);
                $new_balance = $old_balance + $amount;

                // 3. Update the existing transaction status instead of inserting a duplicate row
                $historyMsg = sprintf(
                    "Paystack deposit - Internal Ref: %s (Processed successfully)",
                    $internal_ref
                );
                
                $updateStmt = $pdo->prepare("UPDATE transactions SET status = 'completed', description = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$historyMsg, $transaction['id']]);

                // 4. Credit user balance inside the users table
                $creditStmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $creditStmt->execute([$new_balance, $user_id]);

                $pdo->commit();

                @file_put_contents(
                    $logPath,
                    date('Y-m-d H:i:s') . ' - SUCCESS: User ' . $user_id . ' credited ₦' . number_format($amount, 2) . ' | New balance: ₦' . number_format($new_balance, 2) . PHP_EOL,
                    FILE_APPEND
                );

                http_response_code(200);
                echo json_encode(['status' => true]);
                exit;
            } else {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'User account not found']);
                exit;
            }
        } else {
            // Already processed or non-existent transaction
            $pdo->rollBack();
            http_response_code(200); // Send 200 to Paystack so it stops retrying an already processed or invalid event
            echo json_encode(['status' => true, 'message' => 'Transaction already processed or not found']);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        @file_put_contents(
            $logPath,
            date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Error processing payment']);
        exit;
    }
} else {
    // Return 200 OK for unhandled events so Paystack doesn't keep hammering your server
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Event ignored']);
    exit;
}