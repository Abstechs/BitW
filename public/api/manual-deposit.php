<?php
// api/manual-deposit.php
// Handle manual deposit with proof upload

session_start();
date_default_timezone_set('Africa/Lagos');
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";

// Validate AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$amount = isset($_POST['amount']) && is_numeric($_POST['amount']) ? floatval($_POST['amount']) : 0;

if ($amount <= 0 || $amount > 999999999) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid amount']);
    exit;
}

$settings = include __DIR__ . '/../config/settings.php';

if (!$settings['MANUAL_DEPOSIT_ENABLED']) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Manual deposit is not enabled']);
    exit;
}

// Handle proof upload
$proof_file = null;

if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../public/assets/deposit-proofs/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    if (!in_array($file_extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, PDF allowed']);
        exit;
    }

    // Check file size (max 5MB)
    if ($_FILES['proof']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'File too large. Maximum 5MB allowed']);
        exit;
    }

    $proof_file = 'proof_' . $user_id . '_' . time() . '.' . $file_extension;
    $proof_path = $upload_dir . $proof_file;

    if (!move_uploaded_file($_FILES['proof']['tmp_name'], $proof_path)) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Failed to upload proof']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Proof file is required']);
    exit;
}

// Create pending transaction with internal reference
$reference = 'manual_' . time() . '_' . $user_id . '_' . substr(md5(uniqid()), 0, 6);
$internal_reference = 'ref_' . $user_id . '_' . date('dmYhis', time());
$description = 'Manual deposit [Proof: ' . $proof_file . '] - Internal Ref: ' . $internal_reference;

try {
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, type, amount, reference, status, description, created_at)
        VALUES (?, 'deposit', ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $amount,
        $reference,
        $description
    ]);

    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'Manual deposit request submitted for admin approval',
        'reference' => $reference,
        'internal_reference' => $internal_reference
    ]);
} catch (Exception $e) {
    // Clean up uploaded file on error
    if ($proof_file && file_exists($upload_dir . $proof_file)) {
        unlink($upload_dir . $proof_file);
    }
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

