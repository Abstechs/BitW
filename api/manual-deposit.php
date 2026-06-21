<?php
// api/manual-deposit.php
// Handle manual deposit with proof upload

session_start();
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

if ($amount <= 0) {
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
    $upload_dir = __DIR__ . '/../assets/deposit-proofs/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    if (!in_array($file_extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Invalid file type']);
        exit;
    }

    $proof_file = 'proof_' . $user_id . '_' . time() . '.' . $file_extension;
    $proof_path = $upload_dir . $proof_file;

    if (!move_uploaded_file($_FILES['proof']['tmp_name'], $proof_path)) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Failed to upload proof']);
        exit;
    }
}

// Create pending transaction
$reference = 'manual_' . time() . '_' . $user_id;
$description = 'Manual deposit';

if ($proof_file) {
    $description .= ' [Proof: ' . $proof_file . ']';
}

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
    'reference' => $reference
]);
