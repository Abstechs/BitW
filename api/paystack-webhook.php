<?php
// api/paystack-webhook.php
// Handle Paystack webhook events

date_default_timezone_set('Africa/Lagos');
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
    file_put_contents(__DIR__ . '/../logs/paystack.log', date('Y-m-d H:i:s') . ' - Invalid signature' . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => false, 'message' => 'Invalid signature']);
    exit;
}

// Log all events
file_put_contents(
    __DIR__ . '/../logs/paystack.log',
    date('Y-m-d H:i:s') . ' - Event: ' . $event['event'] . ' | Reference: ' . ($event['data']['reference'] ?? 'N/A') . PHP_EOL,
    FILE_APPEND
);

if ($event['event'] === 'charge.success') {
    $reference = $event['data']['reference'];
    $amount = floatval($event['data']['amount'] / 100); // Convert from kobo to naira
    $paystack_ref = $event['data']['reference'];
    $metadata = $event['data']['metadata'] ?? [];
    $internal_ref = $metadata['internal_reference'] ?? 'no_ref';

    try {
        // Find the transaction record
        $stmt = $pdo->prepare(\"SELECT * FROM transactions WHERE reference = ? AND type = 'deposit' AND status = 'pending'\");\n        $stmt->execute([$reference]);\n        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);\n\n        if ($transaction) {\n            $user_id = $transaction['user_id'];\n\n            // Get current balance\n            $walletStmt = $pdo->prepare(\"SELECT balance FROM wallets WHERE user_id = ?\");\n            $walletStmt->execute([$user_id]);\n            $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);\n\n            if ($wallet) {\n                $old_balance = floatval($wallet['balance']);\n                $new_balance = $old_balance + $amount;\n\n                $pdo->beginTransaction();\n\n                try {\n                    // Update transaction status\n                    $updateStmt = $pdo->prepare(\"UPDATE transactions SET status = 'completed' WHERE reference = ?\");\n                    $updateStmt->execute([$reference]);\n\n                    // Credit wallet\n                    $creditStmt = $pdo->prepare(\"UPDATE wallets SET balance = ? WHERE user_id = ?\");\n                    $creditStmt->execute([$new_balance, $user_id]);\n\n                    // Log transaction\n                    $historyMsg = sprintf(\n                        \"Successful deposit of ₦%s, your reference is %s, internal ref: %s, and new balance is: ₦%s On: %s\",\n                        number_format($amount, 2),\n                        $paystack_ref,\n                        $internal_ref,\n                        number_format($new_balance, 2),\n                        date('d-m-Y h:i:s A')\n                    );\n\n                    $logStmt = $pdo->prepare(\"\n                        INSERT INTO transactions (user_id, type, amount, reference, status, description, created_at)\n                        VALUES (?, 'deposit', ?, ?, 'completed', ?, NOW())\n                    \");\n                    $logStmt->execute([\n                        $user_id,\n                        $amount,\n                        $internal_ref,\n                        $historyMsg\n                    ]);\n\n                    $pdo->commit();\n\n                    file_put_contents(\n                        __DIR__ . '/../logs/paystack.log',\n                        date('Y-m-d H:i:s') . ' - SUCCESS: User ' . $user_id . ' credited ₦' . number_format($amount, 2) . ' | New balance: ₦' . number_format($new_balance, 2) . PHP_EOL,\n                        FILE_APPEND\n                    );\n\n                    http_response_code(200);\n                    echo json_encode(['status' => true]);\n\n                } catch (Exception $e) {\n                    $pdo->rollBack();\n                    throw $e;\n                }\n            } else {\n                http_response_code(400);\n                echo json_encode(['status' => false, 'message' => 'Wallet not found']);\n            }\n        } else {\n            http_response_code(404);\n            file_put_contents(\n                __DIR__ . '/../logs/paystack.log',\n                date('Y-m-d H:i:s') . ' - Transaction not found: ' . $reference . PHP_EOL,\n                FILE_APPEND\n            );\n            echo json_encode(['status' => false, 'message' => 'Transaction not found']);\n        }\n    } catch (Exception $e) {\n        http_response_code(500);\n        file_put_contents(\n            __DIR__ . '/../logs/paystack.log',\n            date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . PHP_EOL,\n            FILE_APPEND\n        );\n        echo json_encode(['status' => false, 'message' => 'Error processing payment']);\n    }\n} else {\n    http_response_code(200);\n    echo json_encode(['status' => true, 'message' => 'Event received']);\n}

