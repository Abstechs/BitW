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

// Enable detailed PDO error reporting during debugging
if (isset($pdo)) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// 1. Authentication Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please re-authenticate.']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

// 2. CSRF Protection Validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Transaction aborted.']);
    exit;
}

// 3. Harvest and Normalize Payload Inputs
$mode = isset($_POST['lotto_mode']) && $_POST['lotto_mode'] === 'demo' ? 'demo' : 'real';

// Read amount across expected POST variable names
$rawAmount = $_POST['amount'] ?? $_POST['bet_amount'] ?? $_POST['bet-amount'] ?? 0;
$betAmount = (float)$rawAmount;

$digitsArray = $_POST['sequence_digits'] ?? [];

// Dynamic Length Strategy Matrix Validation
$length = isset($_POST['prediction_length']) ? (int)$_POST['prediction_length'] : 6;
if (!in_array($length, [4, 6, 8])) {
    echo json_encode(['success' => false, 'message' => 'Unsupported option parameter configurations selected.']);
    exit;
}

// 4. Input Constraints Validation Layer
if (!is_array($digitsArray) || count($digitsArray) !== $length) {
    echo json_encode(['success' => false, 'message' => "Invalid sequence array footprint. Exactly {$length} digits required."]);
    exit;
}

// Clean and concatenate sequence digits
$sequenceString = '';
foreach ($digitsArray as $digit) {
    if (!preg_match('/^[0-9]$/', $digit)) {
        echo json_encode(['success' => false, 'message' => 'Malformed digit payload discovered. Found non-numeric anomalies.']);
        exit;
    }
    $sequenceString .= $digit;
}

// Dynamic Validation against Core System Settings
try {
    $minAllocation = 200; // Hard fallback minimum threshold configuration
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_lotto_allocation'");
    if ($settingsStmt) {
        $dbMin = $settingsStmt->fetchColumn();
        if ($dbMin !== false) $minAllocation = (float)$dbMin;
    }

    if ($betAmount < $minAllocation) {
        echo json_encode(['success' => false, 'message' => "Insufficient position allocation. Minimum required: ₦" . number_format($minAllocation, 2)]);
        exit;
    }

    // Begin Consolidated Database Transaction Block
    $pdo->beginTransaction();

    $newBalance = 0;

    // 5. Account Liquidity Verifications (Checking 'wallets' table)
    if ($mode === 'real') {
        $balanceStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = :id FOR UPDATE");
        $balanceStmt->execute(['id' => $userId]);
        $userBalance = (float)($balanceStmt->fetchColumn() ?? 0.00);

        if ($userBalance < $betAmount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient operational ledger balance to secure this matrix position.']);
            exit;
        }

        // Deduct balance from 'wallets' table
        $deductStmt = $pdo->prepare("UPDATE wallets SET balance = balance - :amount WHERE user_id = :id");
        $deductStmt->execute(['amount' => $betAmount, 'id' => $userId]);
        
        $newBalance = $userBalance - $betAmount;

        // Insert record into 'transactions' table using ENUM value 'buy'
        $ledgerStmt = $pdo->prepare("
            INSERT INTO transactions (user_id, method_id, type, amount, description, reference, gateway, status, created_at) 
            VALUES (:user_id, NULL, 'buy', :amount, 'Lotto matrix position entry', :reference, 'internal', 'completed', NOW())
        ");
        
        $txReference = 'LOTTO_' . strtoupper(uniqid());
        
        $ledgerStmt->execute([
            'user_id'   => $userId,
            'amount'    => $betAmount,
            'reference' => $txReference
        ]);
        
    } else {
        // Checking demo balance in 'wallets' table
        $demoStmt = $pdo->prepare("SELECT demo_balance FROM wallets WHERE user_id = :id FOR UPDATE");
        $demoStmt->execute(['id' => $userId]);
        $demoBalance = (float)($demoStmt->fetchColumn() ?? 0.00);

        if ($demoBalance < $betAmount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient demonstration environment sandbox credits.']);
            exit;
        }

        // Deduct demo balance from 'wallets' table
        $deductDemoStmt = $pdo->prepare("UPDATE wallets SET demo_balance = demo_balance - :amount WHERE user_id = :id");
        $deductDemoStmt->execute(['amount' => $betAmount, 'id' => $userId]);

        $newBalance = $demoBalance - $betAmount;
    }

    // 6. Insert allocation entry into 'lotto_allocations'
    $insertStmt = $pdo->prepare("
        INSERT INTO lotto_allocations (user_id, sequence, prediction_length, amount, mode, status, draw_date, created_at) 
        VALUES (:user_id, :sequence, :len, :amount, :mode, 'pending', CURDATE(), NOW())
    ");
    $insertStmt->execute([
        'user_id'  => $userId,
        'sequence' => $sequenceString,
        'len'      => $length,
        'amount'   => $betAmount,
        'mode'     => $mode
    ]);

    // Commit transaction
    $pdo->commit();

    // 7. Standardized Response Payload
    $displayMsg = ($mode === 'real') 
        ? "Live position sequence [{$sequenceString}] successfully secured into current settlement draw pool."
        : "Sandbox compilation successful. Analytical demo matrix vector [{$sequenceString}] loaded.";

    echo json_encode([
        'success'     => true,
        'message'     => $displayMsg,
        'new_balance' => $newBalance
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Lotto Matrix Execution Fault: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'SQL Execution Error: ' . $e->getMessage()
    ]);
}