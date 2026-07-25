<?php
// public/api/submit-prediction.php
header('Content-Type: application/json');

// Block direct URL browsing; accept only XMLHttpRequests (AJAX/Fetch)
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Direct structural access forbidden.']);
    exit;
}

session_start();
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';

// 1. Authentication Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please re-authenticate.']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

// 2. Comprehensive CSRF Protection Validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Transaction aborted.']);
    exit;
}

// 3. Harvest and Normalize Payload Inputs
$mode = isset($_POST['lotto_mode']) && $_POST['lotto_mode'] === 'demo' ? 'demo' : 'real';
$betAmount = isset($_POST['bet_amount']) ? (int)$_POST['bet_amount'] : 0;
$digitsArray = $_POST['sequence_digits'] ?? [];

// Dynamic Length Strategy Matrix Validation
$length = isset($_POST['prediction_length']) ? (int)$_POST['prediction_length'] : 6;
if (!in_array($length, [4, 6, 8])) {
    echo json_encode(['success' => false, 'message' => 'Unsupported option parameter configurations selected.']);
    exit;
}

// 4. Input Constraints Validation Layer
// Validate sequence array structure against selected length parameter
if (!is_array($digitsArray) || count($digitsArray) !== $length) {
    echo json_encode(['success' => false, 'message' => "Invalid sequence array footprint. Exactly {$length} digits required."]);
    exit;
}

// Clean, concatenate, and strictly match numerical boundary patterns
$sequenceString = '';
foreach ($digitsArray as $digit) {
    if (!preg_match('/^[0-9]$/', $digit)) {
        echo json_encode(['success' => false, 'message' => 'Malformed digit payload discovered. Found non-numeric anomalies.']);
        exit;
    }
    $sequenceString .= $digit;
}

// Dynamic Validation against Live Core Settings Framework
try {
    $minAllocation = 200; // Hard fallback minimum threshold configuration
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_lotto_allocation'");
    if ($settingsStmt) {
        $dbMin = $settingsStmt->fetchColumn();
        if ($dbMin !== false) $minAllocation = (int)$dbMin;
    }

    if ($betAmount < $minAllocation) {
        echo json_encode(['success' => false, 'message' => "Insufficient position allocation. Minimum required: ₦" . number_format($minAllocation)]);
        exit;
    }

    // Begin Consolidated Database Transaction Block
    $pdo->beginTransaction();

    // 5. Account Liquidity Sufficiency Verifications (With Row Locking)
    if ($mode === 'real') {
        // Look up true spendable operational liquidity balance inside user_wallets table
        $balanceStmt = $pdo->prepare("SELECT balance FROM user_wallets WHERE user_id = :id FOR UPDATE");
        $balanceStmt->execute(['id' => $userId]);
        $userBalance = (float)($balanceStmt->fetchColumn() ?? 0.00);

        if ($userBalance < $betAmount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient operational ledger balance to secure this matrix position.']);
            exit;
        }

        // 6. Execute Consolidated Transaction Node (Real Mode)
        // Deduct systemic liquidity bounds from the correct user_wallets table layout
        $deductStmt = $pdo->prepare("UPDATE user_wallets SET balance = balance - :amount WHERE user_id = :id");
        $deductStmt->execute(['amount' => $betAmount, 'id' => $userId]);
        
        // Log transaction hash reference to internal system ledger audit
        $ledgerStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, reference) VALUES (:user_id, :amount, 'debit', 'lotto_pool_entry')");
        $ledgerStmt->execute([
            'user_id' => $userId,
            'amount'  => $betAmount
        ]);
        
    } else {
        // Look up simulation sandbox credit lines inside user_wallets table
        $demoStmt = $pdo->prepare("SELECT demo_balance FROM user_wallets WHERE user_id = :id FOR UPDATE");
        $demoStmt->execute(['id' => $userId]);
        $demoBalance = (float)($demoStmt->fetchColumn() ?? 0.00);

        if ($demoBalance < $betAmount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient demonstration environment sandbox credits.']);
            exit;
        }

        // Deduct systemic simulation credits from sandbox balance fields
        $deductDemoStmt = $pdo->prepare("UPDATE user_wallets SET demo_balance = demo_balance - :amount WHERE user_id = :id");
        $deductDemoStmt->execute(['amount' => $betAmount, 'id' => $userId]);
    }

    // 7. Insert allocation contract entry node with dynamic length values
    $insertStmt = $pdo->prepare("
        INSERT INTO lotto_allocations (user_id, sequence, prediction_length, amount, mode, status, draw_date, created_at) 
        VALUES (:user_id, :sequence, :len, :amount, :mode, 'pending', CURDATE(), NOW())
    ");
    $insertStmt->execute([
        'user_id'           => $userId,
        'sequence'          => $sequenceString,
        'len'               => $length,
        'amount'            => $betAmount,
        'mode'              => $mode
    ]);

    // Commit changes safely to permanent storage state engine
    $pdo->commit();

    // 8. Standardized Response Payload Handshake
    $displayMsg = ($mode === 'real') 
        ? "Live position sequence [{$sequenceString}] successfully secured into current settlement draw pool."
        : "Sandbox compilation successful. Analytical demo matrix vector [{$sequenceString}] loaded.";

    echo json_encode([
        'success' => true,
        'message' => $displayMsg
    ]);

} catch (Exception $e) {
    // Structural rollback strategy protects account balances from systemic corruptions
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log exception context securely on server side
    error_log("Lotto Matrix Execution Fault: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Critical framework transaction exception occurred during position compilation.'
    ]);
}