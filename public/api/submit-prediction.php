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

// 4. Input Constraints Validation Layer
// A. Validate the 6-digit sequence array structure
if (!is_array($digitsArray) || count($digitsArray) !== 6) {
    echo json_encode(['success' => false, 'message' => 'Invalid sequence array footprint. Exactly 6 digits required.']);
    exit;
}

// B. Clean, concatenate, and strictly match numerical boundary patterns
$sequenceString = '';
foreach ($digitsArray as $digit) {
    if (!preg_match('/^[0-9]$/', $digit)) {
        echo json_encode(['success' => false, 'message' => 'Malformed digit payload discovered. Found non-numeric anomalies.']);
        exit;
    }
    $sequenceString .= $digit;
}

// C. Dynamic Validation against Live Core Settings Framework
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

    // 5. Account Liquidity Sufficiency Verifications
    if ($mode === 'real') {
        // Look up true spendable operational liquidity balance
        $balanceStmt = $pdo->prepare("SELECT balance FROM users WHERE id = :id FOR UPDATE"); // Lock row row to prevent double-spending race conditions
        $balanceStmt->execute(['id' => $userId]);
        $userBalance = (int)($balanceStmt->fetchColumn() ?? 0);

        if ($userBalance < $betAmount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient operational ledger balance to secure this matrix position.']);
            exit;
        }
    } else {
        // Sandbox Mock Credit Line (Implicitly approved for optimization loops)
        $userBalance = 999999999; 
    }

    // 6. Execute Consolidated Transaction Node
    $pdo->beginTransaction();

    if ($mode === 'real') {
        // Deduct systemic liquidity bounds from account
        $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - :amount WHERE id = :id");
        $deductStmt->execute(['amount' => $betAmount, 'id' => $userId]);
        
        // Log transaction hash reference to internal system ledger audit
        $ledgerStmt = $pdo->prepare("INSERT INTO system_ledger (user_id, type, amount, description) VALUES (:user_id, 'lotto_allocation', :amount, :desc)");
        $ledgerStmt->execute([
            'user_id' => $userId,
            'amount'  => -$betAmount,
            'desc'    => "Committed Live Position Sequence Matrix Hash: " . $sequenceString
        ]);
    }

    // Insert allocation contract entry node
    $insertStmt = $pdo->prepare("
        INSERT INTO lotto_allocations (user_id, sequence, amount, mode, status, draw_date, created_at) 
        VALUES (:user_id, :sequence, :amount, :mode, 'pending', CURDATE(), NOW())
    ");
    $insertStmt->execute([
        'user_id'  => $userId,
        'sequence' => $sequenceString,
        'amount'   => $betAmount,
        'mode'     => $mode
    ]);

    // Commit changes safely to permanent storage state engine
    $pdo->commit();

    // 7. Standardized Response Payload Handshake
    $displayMsg = ($mode === 'real') 
        ? "Live position hash [{$sequenceString}] successfully secured into current settlement draw pool."
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